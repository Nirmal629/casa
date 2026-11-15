<?php
/**
 * api/audit/top_games.php — games ranked by a metric, for KPI / chart drill-downs.
 *
 * ?metric = views | joins | completed | abandoned   (default views)
 * ?range= / ?from= ?to=     time window
 * ?limit = 5..50 (default 10)
 *
 * "views" comes from the GAME_VIEWED activity log (new tracking); the other
 * three are derived from ca_gamejoin ⋈ ca_events (full history).
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_scope.php';

$H      = $HOST_ID;
$metric = $_GET['metric'] ?? 'views';
if (!in_array($metric, ['views', 'joins', 'completed', 'abandoned'], true)) $metric = 'views';
$limit  = audit_int($_GET['limit'] ?? 10, 10, 5, 50);
$r      = audit_range();
$from   = $r['from'];
$to     = $r['to'];

try {
    if ($metric === 'views') {
        $stmt = $pdo->prepare("
            SELECT l.GAME_ID AS id,
                   COUNT(*)                        AS value,
                   COUNT(DISTINCT l.USER_ID)       AS unique_users,
                   MIN(l.CREATED_AT)               AS first_at,
                   MAX(l.CREATED_AT)               AS last_at
            FROM ca_player_logs l
            WHERE l.HOST_ID = $H AND l.ACTIVITY_TYPE = 'GAME_VIEWED'
              AND l.GAME_ID IS NOT NULL AND l.CREATED_AT BETWEEN :f AND :t
            GROUP BY l.GAME_ID
            ORDER BY value DESC, unique_users DESC
            LIMIT $limit
        ");
        $stmt->execute([':f' => $from, ':t' => $to]);
    } else {
        $cond = "1=1";
        if ($metric === 'completed') $cond = "cg.CONFIRMED = 'Y' AND e.STATUS = 'Completed'";
        if ($metric === 'abandoned') $cond = "(e.STATUS = 'Cancelled' OR (e.STATUS = 'Completed' AND cg.CONFIRMED = 'N'))";
        $stmt = $pdo->prepare("
            SELECT cg.GAME_ID AS id,
                   COUNT(*)                        AS value,
                   COUNT(DISTINCT cg.USER_ID)      AS unique_users,
                   MIN(cg.CREATED_AT)              AS first_at,
                   MAX(cg.CREATED_AT)              AS last_at
            FROM ca_gamejoin cg
            JOIN ca_events e ON e.ID = cg.GAME_ID
            WHERE cg.HOST_ID = $H AND cg.CREATED_AT BETWEEN :f AND :t AND ($cond)
            GROUP BY cg.GAME_ID
            ORDER BY value DESC, unique_users DESC
            LIMIT $limit
        ");
        $stmt->execute([':f' => $from, ':t' => $to]);
    }
    $rows = $stmt->fetchAll();

    $games = [];
    if ($rows) {
        $ids = implode(',', array_map(fn($x) => (int) $x['id'], $rows));
        $meta = [];
        foreach ($pdo->query("SELECT ID, CUP_NAME, HOST_NAME, EVENT_CATEGORY, EVENT_DATE, STATUS FROM ca_events WHERE ID IN ($ids)")->fetchAll() as $g) {
            $meta[(int) $g['ID']] = $g;
        }
        foreach ($rows as $x) {
            $g = $meta[(int) $x['id']] ?? null;
            $games[] = [
                'id'           => (int) $x['id'],
                'name'         => $g ? audit_game_name($g) : ('Game #' . $x['id']),
                'category'     => $g['EVENT_CATEGORY'] ?? null,
                'event_date'   => $g['EVENT_DATE'] ?? null,
                'status'       => $g['STATUS'] ?? null,
                'value'        => (int) $x['value'],
                'unique_users' => (int) $x['unique_users'],
                'first_at'     => $x['first_at'],
                'last_at'      => $x['last_at'],
            ];
        }
    }

    json_out([
        'metric' => $metric,
        'range'  => ['key' => $r['key'], 'from' => substr($from, 0, 10), 'to' => substr($to, 0, 10)],
        'games'  => $games,
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load top games.', 'detail' => $e->getMessage()], 500);
}
