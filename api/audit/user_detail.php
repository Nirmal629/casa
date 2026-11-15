<?php
/**
 * api/audit/user_detail.php — everything the individual-user modal needs
 * except the paginated timeline (that stays in user_timeline.php).
 *
 * ?user_id=   (required)
 * ?range= / ?from= ?to=   date window for the trend + in-range counters
 * ?game_page= ?game_per_page=   pagination for the "Games" table
 *
 * Sections returned: user (profile + KPIs), trend, activity_breakdown,
 * engagement, games (paginated), behaviour_reasons.
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_metrics.php';
require __DIR__ . '/_scope.php';

$H       = $HOST_ID;
$userId  = audit_int($_GET['user_id'] ?? 0, 0, 0, PHP_INT_MAX);
$gPage   = audit_int($_GET['game_page'] ?? 1, 1, 1, 100000);
$gPer    = audit_int($_GET['game_per_page'] ?? 8, 8, 3, 50);

if ($userId <= 0) {
    json_out(['error' => 'bad_request', 'message' => 'user_id is required.'], 400);
}
if (!audit_user_in_scope($pdo, $H, $userId)) {
    json_out(['error' => 'forbidden', 'message' => 'This user is not part of your activity.'], 403);
}

$r    = audit_range();
$from = $r['from'];
$to   = $r['to'];

try {
    /* ---- profile + core metrics (all-time, host-scoped) --------------- */
    $uStmt = $pdo->prepare(audit_metrics_sql($H) . " AND u.ID = :uid ");
    $uStmt->execute([':uid' => $userId]);
    $mrow = $uStmt->fetch();
    if (!$mrow) {
        json_out(['error' => 'not_found', 'message' => 'User has no activity with your games.'], 404);
    }
    $profile = audit_shape_row($mrow);

    /* ---- first seen + sessions -------------------------------------- */
    $extra = $pdo->prepare("
        SELECT
          u.created_at AS registered_at,
          LEAST(
            COALESCE((SELECT MIN(CREATED_AT) FROM ca_player_logs WHERE USER_ID = :u1 AND (HOST_ID = $H OR ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))), '9999-01-01'),
            COALESCE((SELECT MIN(CREATED_AT) FROM ca_gamejoin WHERE USER_ID = :u2 AND HOST_ID = $H), '9999-01-01')
          ) AS first_seen,
          (SELECT COUNT(*) FROM ca_player_logs WHERE USER_ID = :u3 AND ACTIVITY_TYPE = 'LOGIN') AS sessions
        FROM ca_users u WHERE u.ID = :u4
    ");
    $extra->execute([':u1' => $userId, ':u2' => $userId, ':u3' => $userId, ':u4' => $userId]);
    $ex = $extra->fetch() ?: [];

    $sessQ = $pdo->prepare("
        SELECT COUNT(*) AS n, ROUND(SUM(sess)) AS total_min, ROUND(AVG(sess)) AS avg_min FROM (
            SELECT TIMESTAMPDIFF(MINUTE, l.CREATED_AT, (
                SELECT MIN(o.CREATED_AT) FROM ca_player_logs o
                WHERE o.USER_ID = l.USER_ID AND o.ACTIVITY_TYPE = 'LOGOUT'
                  AND o.CREATED_AT > l.CREATED_AT AND o.CREATED_AT < l.CREATED_AT + INTERVAL 6 HOUR
            )) AS sess
            FROM ca_player_logs l
            WHERE l.USER_ID = :u AND l.ACTIVITY_TYPE = 'LOGIN'
        ) t WHERE sess IS NOT NULL AND sess BETWEEN 1 AND 360
    ");
    $sessQ->execute([':u' => $userId]);
    $sess = $sessQ->fetch() ?: [];

    $firstSeen = (!empty($ex['first_seen']) && $ex['first_seen'] < '9990-01-01') ? $ex['first_seen'] : null;

    $m = $profile['metrics'];
    $profile['kpis'] = [
        'sessions'            => (int) ($ex['sessions'] ?? 0),
        'session_minutes'     => $sess['total_min'] !== null ? (int) $sess['total_min'] : 0,
        'avg_session_min'     => $sess['avg_min']   !== null ? (int) $sess['avg_min']   : null,
        'games_viewed'        => $m['views'],
        'unique_games_viewed' => $m['unique_games_viewed'],
        'games_joined'        => $m['joins'],
        'games_completed'     => $m['completed'],
        'games_abandoned'     => $m['abandoned'],
        'view_join_rate'      => ($m['views'] + $m['list_views']) >= max(1, $m['joins'])
                                    ? round($m['joins'] / ($m['views'] + $m['list_views']) * 100) : null,
        'last_active'         => $profile['last_active'],
        'first_seen'          => $firstSeen,
        'registered_at'       => $ex['registered_at'] ?? null,
        'active_days'         => $m['active_days'],
        'login_count'         => $m['login_count'],
    ];

    /* ---- behaviour "why" (transparent rules) ----------------------- */
    $profile['behaviour_reasons'] = audit_behaviour_reasons($m);

    /* ---- activity trend within range (log ∪ joins) --------------- */
    $trendFrom = $from;
    if ($r['key'] === 'all') {
        // start the chart at the user's first activity, capped to ~18 months
        $trendFrom = max($firstSeen ?: $from, date('Y-m-d 00:00:00', strtotime('-18 months')));
    }
    $spanDays = max(1, (int) ((strtotime($to) - strtotime($trendFrom)) / 86400) + 1);
    $granularity = $spanDays <= 2 ? 'hour' : ($spanDays > 92 ? 'week' : 'day');
    $trend = audit_user_trend($pdo, $H, $userId, $trendFrom, $to, $granularity);

    /* ---- activity-type breakdown (in range) --------------------- */
    $bdStmt = $pdo->prepare("
        SELECT ACTIVITY_TYPE AS t, COUNT(*) AS c
        FROM ca_player_logs
        WHERE USER_ID = :u AND (HOST_ID = $H OR ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))
          AND CREATED_AT BETWEEN :f AND :t
        GROUP BY ACTIVITY_TYPE
    ");
    $bdStmt->execute([':u' => $userId, ':f' => $from, ':t' => $to]);
    $breakdown = [];
    foreach ($bdStmt->fetchAll() as $b) {
        $lab = audit_type_label($b['t']);
        $breakdown[] = ['type' => $b['t'], 'label' => $lab['label'], 'tone' => $lab['tone'], 'value' => (int) $b['c']];
    }
    // fold in real joins made in range that never produced a log row
    $jr = $pdo->prepare("SELECT COUNT(*) FROM ca_gamejoin WHERE USER_ID = :u AND HOST_ID = $H AND CREATED_AT BETWEEN :f AND :t");
    $jr->execute([':u' => $userId, ':f' => $from, ':t' => $to]);
    $joinsInRange = (int) $jr->fetchColumn();
    if ($joinsInRange > 0) {
        $found = false;
        foreach ($breakdown as &$b) {
            if ($b['type'] === 'JOIN_GAME') { $b['value'] = max($b['value'], $joinsInRange); $found = true; }
        }
        unset($b);
        if (!$found) $breakdown[] = ['type' => 'JOIN_GAME', 'label' => 'Joined game', 'tone' => 'green', 'value' => $joinsInRange];
    }

    /* ---- engagement totals (all-time) -------------------------- */
    $engagement = [
        'viewed'    => $m['views'],
        'joined'    => $m['joins'],
        'completed' => $m['completed'],
        'abandoned' => $m['abandoned'],
    ];

    /* ---- games this user interacted with (paginated) --------- */
    $gameIdsStmt = $pdo->prepare("
        SELECT gid FROM (
            SELECT DISTINCT GAME_ID AS gid FROM ca_player_logs
              WHERE USER_ID = :u1 AND HOST_ID = $H AND GAME_ID IS NOT NULL
            UNION
            SELECT DISTINCT GAME_ID FROM ca_gamejoin WHERE USER_ID = :u2 AND HOST_ID = $H
        ) x
    ");
    $gameIdsStmt->execute([':u1' => $userId, ':u2' => $userId]);
    $allGameIds = array_map('intval', array_column($gameIdsStmt->fetchAll(), 'gid'));
    $gamesTotal = count($allGameIds);
    $gPages = max(1, (int) ceil($gamesTotal / $gPer));
    $gPage  = min($gPage, $gPages);

    $games = [];
    if ($allGameIds) {
        $pageIds = array_slice($allGameIds, ($gPage - 1) * $gPer, $gPer);
        $in = implode(',', $pageIds);
        $gStmt = $pdo->query("
            SELECT e.ID, e.CUP_NAME, e.HOST_NAME, e.EVENT_CATEGORY, e.EVENT_DATE, e.STATUS,
                (SELECT COUNT(*) FROM ca_player_logs l WHERE l.USER_ID = $userId AND l.GAME_ID = e.ID AND l.ACTIVITY_TYPE = 'GAME_VIEWED') AS views,
                (SELECT MIN(l.CREATED_AT) FROM ca_player_logs l WHERE l.USER_ID = $userId AND l.GAME_ID = e.ID AND l.ACTIVITY_TYPE = 'GAME_VIEWED') AS first_viewed,
                (SELECT COUNT(*) FROM ca_player_logs l WHERE l.USER_ID = $userId AND l.GAME_ID = e.ID AND l.ACTIVITY_TYPE = 'LEAVE_GAME') AS leaves,
                (SELECT MAX(l.CREATED_AT) FROM ca_player_logs l WHERE l.USER_ID = $userId AND l.GAME_ID = e.ID) AS last_log,
                (SELECT cg.CREATED_AT FROM ca_gamejoin cg WHERE cg.USER_ID = $userId AND cg.GAME_ID = e.ID LIMIT 1) AS joined_at,
                (SELECT cg.CONFIRMED FROM ca_gamejoin cg WHERE cg.USER_ID = $userId AND cg.GAME_ID = e.ID LIMIT 1) AS confirmed,
                (SELECT COUNT(*) FROM ca_gamejoin cg WHERE cg.USER_ID = $userId AND cg.GAME_ID = e.ID) AS join_rows
            FROM ca_events e WHERE e.ID IN ($in)
        ");
        $rowsById = [];
        foreach ($gStmt->fetchAll() as $g) { $rowsById[(int) $g['ID']] = $g; }
        foreach ($pageIds as $gid) {
            $g = $rowsById[$gid] ?? null;
            if (!$g) {
                $games[] = ['id' => $gid, 'name' => 'Game #' . $gid, 'category' => null, 'status' => null,
                            'views' => 0, 'joined' => false, 'completed' => false, 'abandoned' => false,
                            'first_viewed' => null, 'last_activity' => null];
                continue;
            }
            $joined    = ((int) $g['join_rows']) > 0;
            $left      = ((int) $g['leaves']) > 0;
            $completed = $joined && strcasecmp((string) $g['confirmed'], 'Y') === 0 && strcasecmp((string) $g['STATUS'], 'Completed') === 0;
            $abandoned = $left || ($joined && strcasecmp((string) $g['STATUS'], 'Completed') === 0 && strcasecmp((string) $g['confirmed'], 'Y') !== 0)
                              || ($joined && strcasecmp((string) $g['STATUS'], 'Cancelled') === 0);
            $lastAct = $g['last_log'];
            if ($g['joined_at'] && (!$lastAct || $g['joined_at'] > $lastAct)) $lastAct = $g['joined_at'];
            $games[] = [
                'id'           => (int) $g['ID'],
                'name'         => audit_game_name($g),
                'category'     => $g['EVENT_CATEGORY'],
                'event_date'   => $g['EVENT_DATE'],
                'status'       => $g['STATUS'],
                'views'        => (int) $g['views'],
                'joined'       => $joined,
                'joined_at'    => $g['joined_at'],
                'left'         => $left,
                'completed'    => $completed,
                'abandoned'    => $abandoned,
                'first_viewed' => $g['first_viewed'],
                'last_activity' => $lastAct,
            ];
        }
    }

    json_out([
        'range'      => ['key' => $r['key'], 'from' => substr($from, 0, 10), 'to' => substr($to, 0, 10), 'days' => $r['days'], 'granularity' => $granularity],
        'user'       => $profile,
        'trend'      => $trend,
        'activity_breakdown' => $breakdown,
        'engagement' => $engagement,
        'games'      => [
            'data' => $games,
            'pagination' => ['page' => $gPage, 'per_page' => $gPer, 'total' => $gamesTotal, 'pages' => $gPages],
        ],
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load user detail.', 'detail' => $e->getMessage()], 500);
}


/* ================================================================= */

/** Transparent, data-derived reasons for the behaviour label. */
function audit_behaviour_reasons(array $m): array
{
    $b   = audit_behaviour($m);
    $out = [];
    $browse = $m['views'] + $m['list_views'];
    switch ($b) {
        case 'Highly Engaged':
            $out[] = "{$m['joins']} joins with a " . round($m['completed'] / max($m['joins'], 1) * 100) . "% completion rate";
            break;
        case 'High Abandonment':
            $out[] = "{$m['abandoned']} of {$m['joins']} joined games abandoned or not confirmed";
            break;
        case 'Browsing Only':
            $out[] = "{$browse} game views / list opens but 0 joins";
            break;
        case 'Returning':
            $out[] = "active on {$m['active_days']} separate days";
            break;
        case 'Inactive':
            $out[] = $m['days_since_active'] === null ? "no recorded activity" : "last active {$m['days_since_active']} days ago";
            break;
        default:
            $out[] = "limited activity so far ({$browse} views, {$m['joins']} joins)";
    }
    return ['label' => $b, 'reasons' => $out];
}

/** Per-user activity series (hour | day | week) merging log events and joins. */
function audit_user_trend(PDO $pdo, int $H, int $userId, string $from, string $to, string $gran): array
{
    $fmt = $gran === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

    $lq = $pdo->prepare("
        SELECT DATE_FORMAT(CREATED_AT, '$fmt') AS b,
               SUM(ACTIVITY_TYPE = 'GAME_VIEWED')  AS views,
               SUM(ACTIVITY_TYPE = 'LOGIN')        AS logins,
               SUM(ACTIVITY_TYPE = 'LEAVE_GAME')   AS leaves,
               COUNT(*)                            AS total
        FROM ca_player_logs
        WHERE USER_ID = :u AND (HOST_ID = $H OR ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))
          AND CREATED_AT BETWEEN :f AND :t
        GROUP BY b
    ");
    $lq->execute([':u' => $userId, ':f' => $from, ':t' => $to]);
    $log = [];
    foreach ($lq->fetchAll() as $x) { $log[$x['b']] = $x; }

    $jq = $pdo->prepare("
        SELECT DATE_FORMAT(CREATED_AT, '$fmt') AS b, COUNT(*) AS joins
        FROM ca_gamejoin WHERE USER_ID = :u AND HOST_ID = $H AND CREATED_AT BETWEEN :f AND :t
        GROUP BY b
    ");
    $jq->execute([':u' => $userId, ':f' => $from, ':t' => $to]);
    $jn = [];
    foreach ($jq->fetchAll() as $x) { $jn[$x['b']] = (int) $x['joins']; }

    $out  = [];
    $cur  = new DateTime(substr($from, 0, ($gran === 'hour' ? 13 : 10)) . ($gran === 'hour' ? ':00:00' : ' 00:00:00'));
    $end  = new DateTime($to);
    $step = $gran === 'hour' ? 'PT1H' : ($gran === 'week' ? 'P7D' : 'P1D');
    $guard = 0;
    while ($cur <= $end && $guard++ < 900) {
        $key = $cur->format($gran === 'hour' ? 'Y-m-d H:00' : 'Y-m-d');
        $l = $log[$key] ?? [];
        $lbl = $gran === 'hour' ? $cur->format('M j, ga') : $cur->format('M j');
        $out[] = [
            'bucket' => $key,
            'label'  => $lbl,
            'views'  => (int) ($l['views'] ?? 0),
            'joins'  => $jn[$key] ?? 0,
            'logins' => (int) ($l['logins'] ?? 0),
            'total'  => (int) ($l['total'] ?? 0) + ($jn[$key] ?? 0),
        ];
        $cur->add(new DateInterval($step));
    }
    if ($gran !== 'week' && count($out) > 120) {
        // safety collapse for very long day ranges
        $wk = [];
        foreach ($out as $i => $d) {
            $g = intdiv($i, 7);
            if (!isset($wk[$g])) $wk[$g] = ['bucket' => $d['bucket'], 'label' => $d['label'], 'views' => 0, 'joins' => 0, 'logins' => 0, 'total' => 0];
            foreach (['views', 'joins', 'logins', 'total'] as $k) $wk[$g][$k] += $d[$k];
        }
        $out = array_values($wk);
    }
    return $out;
}
