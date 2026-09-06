<?php
/**
 * api/audit/summary.php — KPI cards, view→join funnel, behaviour distribution.
 * All host-scoped. Activity counters respect ?range=; the user universe and
 * behaviour split are all-time (they describe the host's player base).
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_metrics.php';

$H = $HOST_ID;
$r = audit_range();
$from = $r['from'];
$to   = $r['to'];

try {
    /* ---- browsing + drop-off events in range (from the activity log) ---- */
    $ev = $pdo->prepare("
        SELECT
          SUM(ACTIVITY_TYPE = 'GAME_LIST_VIEWED')            AS list_views,
          SUM(ACTIVITY_TYPE = 'GAME_VIEWED')                 AS game_views,
          SUM(ACTIVITY_TYPE = 'LEAVE_GAME')                  AS leaves,
          COUNT(DISTINCT USER_ID)                            AS log_active_users,
          COUNT(DISTINCT CASE WHEN DATE(CREATED_AT) = CURDATE() THEN USER_ID END) AS log_active_today
        FROM ca_player_logs
        WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f AND :t
    ");
    $ev->execute([':f' => $from, ':t' => $to]);
    $a = $ev->fetch() ?: [];

    /* ---- joins / completions / no-shows in range (real join records) --- */
    $cg = $pdo->prepare("
        SELECT
          COUNT(*)                                              AS joins,
          COUNT(DISTINCT cg.USER_ID)                            AS join_users,
          COUNT(DISTINCT CASE WHEN DATE(cg.CREATED_AT) = CURDATE() THEN cg.USER_ID END) AS join_today,
          SUM(cg.CONFIRMED = 'Y' AND e.STATUS = 'Completed')    AS completed,
          SUM(cg.CONFIRMED = 'N' AND e.STATUS = 'Completed')    AS no_show,
          SUM(e.STATUS = 'Cancelled')                           AS on_cancelled
        FROM ca_gamejoin cg
        JOIN ca_events e ON e.ID = cg.GAME_ID
        WHERE cg.HOST_ID = $H AND cg.CREATED_AT BETWEEN :f AND :t
    ");
    $cg->execute([':f' => $from, ':t' => $to]);
    $c = $cg->fetch() ?: [];

    /* ---- total user universe + all-time active (30d) ------------------- */
    $uni = $pdo->query("
        SELECT COUNT(*) AS total,
          SUM(last_active IS NOT NULL AND last_active >= (NOW() - INTERVAL 7 DAY))  AS active_7d,
          SUM(last_active IS NOT NULL AND last_active >= (NOW() - INTERVAL 30 DAY)) AS active_30d
        FROM (
          SELECT un.uid,
            GREATEST(
              COALESCE((SELECT MAX(l.CREATED_AT) FROM ca_player_logs l
                 WHERE l.USER_ID = un.uid AND (l.HOST_ID = $H OR l.ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))), '1000-01-01'),
              COALESCE((SELECT MAX(cg.CREATED_AT) FROM ca_gamejoin cg
                 WHERE cg.USER_ID = un.uid AND cg.HOST_ID = $H), '1000-01-01')
            ) AS last_active
          FROM (
            SELECT USER_ID AS uid FROM ca_player_logs WHERE HOST_ID = $H
            UNION SELECT USER_ID FROM ca_gamejoin WHERE HOST_ID = $H
            UNION SELECT player_id FROM ca_player_club_status WHERE host_id = $H AND status = 'accepted'
          ) un
          JOIN ca_users u ON u.ID = un.uid
          WHERE u.USERTYPE = 'Player' AND (u.DEL_STATUS IS NULL OR u.DEL_STATUS = 'N')
        ) x
    ")->fetch() ?: [];

    /* ---- global avg session (all-time, capped 6h) --------------------- */
    $sess = $pdo->query("
        SELECT ROUND(AVG(sess)) AS avg_min FROM (
          SELECT TIMESTAMPDIFF(MINUTE, l.CREATED_AT, (
             SELECT MIN(o.CREATED_AT) FROM ca_player_logs o
             WHERE o.USER_ID = l.USER_ID AND o.ACTIVITY_TYPE = 'LOGOUT'
               AND o.CREATED_AT > l.CREATED_AT AND o.CREATED_AT < l.CREATED_AT + INTERVAL 6 HOUR
          )) AS sess
          FROM ca_player_logs l
          WHERE l.ACTIVITY_TYPE = 'LOGIN'
            AND l.USER_ID IN (
              SELECT USER_ID FROM ca_player_logs WHERE HOST_ID = $H
              UNION SELECT USER_ID FROM ca_gamejoin WHERE HOST_ID = $H
            )
        ) t WHERE sess BETWEEN 1 AND 360
    ")->fetch() ?: [];

    /* ---- behaviour distribution (all-time, host universe) ------------- */
    $rows = $pdo->query(audit_metrics_sql($H))->fetchAll();
    $dist = array_fill_keys(audit_behaviour_list(), 0);
    $totalUsers = 0;
    foreach ($rows as $row) {
        $shaped = audit_shape_row($row);
        $dist[$shaped['behaviour']] = ($dist[$shaped['behaviour']] ?? 0) + 1;
        $totalUsers++;
    }

    $listViews = (int) ($a['list_views'] ?? 0);
    $gameViews = (int) ($a['game_views'] ?? 0);
    $joins     = (int) ($c['joins'] ?? 0);
    $completed = (int) ($c['completed'] ?? 0);
    $leaves    = (int) ($a['leaves'] ?? 0);
    $abandoned = $leaves + (int) ($c['no_show'] ?? 0) + (int) ($c['on_cancelled'] ?? 0);
    $browse    = $listViews + $gameViews;
    // conversion: of games actually viewed, how many led to a join
    $viewJoinRate = $gameViews > 0 ? round(min($joins, $gameViews) / $gameViews * 100, 1) : null;

    $activeInRange = max(
        (int) ($a['log_active_users'] ?? 0),
        (int) ($c['join_users'] ?? 0)
    );
    $activeToday = max(
        (int) ($a['log_active_today'] ?? 0),
        (int) ($c['join_today'] ?? 0)
    );

    json_out([
        'range' => ['key' => $r['key'], 'from' => substr($from, 0, 10), 'to' => substr($to, 0, 10), 'days' => $r['days']],
        'kpis' => [
            'total_users'     => (int) ($uni['total'] ?? $totalUsers),
            'active_users_7d'  => (int) ($uni['active_7d'] ?? 0),
            'active_in_range' => $activeInRange,
            'active_today'    => $activeToday,
            'games_viewed'    => $gameViews,
            'games_joined'    => $joins,
            'games_completed' => $completed,
            'games_left'      => $leaves,
            'avg_session_min' => $sess['avg_min'] !== null ? (int) $sess['avg_min'] : null,
            'view_join_rate'  => $viewJoinRate,
        ],
        'funnel' => [
            ['stage' => 'Game list views', 'value' => $listViews],
            ['stage' => 'Game details viewed', 'value' => $gameViews],
            ['stage' => 'Joined', 'value' => $joins],
            ['stage' => 'Completed', 'value' => $completed],
        ],
        'behaviour_distribution' => array_map(
            fn($k) => ['label' => $k, 'value' => (int) $dist[$k]],
            audit_behaviour_list()
        ),
        'engagement_totals' => [
            'viewed'    => $gameViews,
            'joined'    => $joins,
            'completed' => $completed,
            'abandoned' => $abandoned,
        ],
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load summary.', 'detail' => $e->getMessage()], 500);
}
