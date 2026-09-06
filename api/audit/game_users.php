<?php
/**
 * api/audit/game_users.php — users who interacted with one game, for the
 * "click a game → who did this?" drill-down.
 *
 * ?game_id=  (required, must belong to the host)
 * ?range= / ?from= ?to=   optional time window (applies to the view/join counts)
 * ?page= ?per_page= (5..100, default 20)
 * ?sort = views | joined_at | last  (default views)
 *
 * Universe = users who viewed the game (activity log) ∪ users who joined it (ca_gamejoin).
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_scope.php';

$H      = $HOST_ID;
$gameId = audit_int($_GET['game_id'] ?? 0, 0, 0, PHP_INT_MAX);
$page   = audit_int($_GET['page'] ?? 1, 1, 1, 100000);
$per    = audit_int($_GET['per_page'] ?? 20, 20, 5, 100);
$sort   = $_GET['sort'] ?? 'views';
if (!in_array($sort, ['views', 'joined_at', 'last'], true)) $sort = 'views';

if ($gameId <= 0) {
    json_out(['error' => 'bad_request', 'message' => 'game_id is required.'], 400);
}
$game = audit_game_in_scope($pdo, $H, $gameId);
if (!$game) {
    json_out(['error' => 'forbidden', 'message' => 'That game is not one of yours.'], 403);
}

$r    = audit_range();
$from = $r['from'];
$to   = $r['to'];

try {
    $idStmt = $pdo->prepare("
        SELECT uid FROM (
            SELECT DISTINCT USER_ID AS uid FROM ca_player_logs
              WHERE GAME_ID = :g1 AND ACTIVITY_TYPE = 'GAME_VIEWED'
            UNION
            SELECT DISTINCT USER_ID FROM ca_gamejoin WHERE GAME_ID = :g2
        ) x
    ");
    $idStmt->execute([':g1' => $gameId, ':g2' => $gameId]);
    $ids = array_map('intval', array_column($idStmt->fetchAll(), 'uid'));
    $total = count($ids);
    $pages = max(1, (int) ceil($total / $per));
    $page  = min($page, $pages);

    $data = [];
    if ($ids) {
        // pull full metrics for all, sort/paginate in PHP (bounded by one game's audience)
        $in = implode(',', $ids);
        $rows = $pdo->query("
            SELECT u.ID, u.NAME, u.EMAIL, u.PROFILE_IMAGE,
                (SELECT COUNT(*) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.GAME_ID = $gameId AND l.ACTIVITY_TYPE = 'GAME_VIEWED') AS views,
                (SELECT MIN(l.CREATED_AT) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.GAME_ID = $gameId AND l.ACTIVITY_TYPE = 'GAME_VIEWED') AS first_viewed,
                (SELECT MAX(l.CREATED_AT) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.GAME_ID = $gameId) AS last_activity,
                (SELECT COUNT(*) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.GAME_ID = $gameId AND l.ACTIVITY_TYPE = 'LEAVE_GAME') AS leaves,
                (SELECT cg.CREATED_AT FROM ca_gamejoin cg WHERE cg.USER_ID = u.ID AND cg.GAME_ID = $gameId LIMIT 1) AS joined_at,
                (SELECT cg.CONFIRMED  FROM ca_gamejoin cg WHERE cg.USER_ID = u.ID AND cg.GAME_ID = $gameId LIMIT 1) AS confirmed,
                (SELECT COUNT(*) FROM ca_gamejoin cg WHERE cg.USER_ID = u.ID AND cg.GAME_ID = $gameId) AS join_rows
            FROM ca_users u WHERE u.ID IN ($in)
        ")->fetchAll();

        foreach ($rows as $x) {
            $joined    = ((int) $x['join_rows']) > 0;
            $left      = ((int) $x['leaves']) > 0;
            $completed = $joined && strcasecmp((string) $x['confirmed'], 'Y') === 0 && strcasecmp((string) $game['STATUS'], 'Completed') === 0;
            $abandoned = $left
                || ($joined && strcasecmp((string) $game['STATUS'], 'Completed') === 0 && strcasecmp((string) $x['confirmed'], 'Y') !== 0)
                || ($joined && strcasecmp((string) $game['STATUS'], 'Cancelled') === 0);
            $data[] = [
                'id'           => (int) $x['ID'],
                'name'         => $x['NAME'] ?: ('User #' . $x['ID']),
                'email'        => $x['EMAIL'],
                'avatar'      => !empty($x['PROFILE_IMAGE']) ? 'profile_img/' . $x['PROFILE_IMAGE'] : null,
                'views'        => (int) $x['views'],
                'first_viewed' => $x['first_viewed'],
                'last_activity' => $x['last_activity'],
                'joined'       => $joined,
                'joined_at'    => $x['joined_at'],
                'left'         => $left,
                'completed'    => $completed,
                'abandoned'    => $abandoned,
            ];
        }
        usort($data, function ($a, $b) use ($sort) {
            if ($sort === 'joined_at') return strcmp((string) $b['joined_at'], (string) $a['joined_at']);
            if ($sort === 'last')      return strcmp((string) $b['last_activity'], (string) $a['last_activity']);
            return $b['views'] <=> $a['views'];
        });
        $data = array_slice($data, ($page - 1) * $per, $per);
    }

    // headline counts for the panel
    $head = $pdo->query("
        SELECT
          (SELECT COUNT(*) FROM ca_player_logs WHERE GAME_ID = $gameId AND ACTIVITY_TYPE = 'GAME_VIEWED')  AS views,
          (SELECT COUNT(DISTINCT USER_ID) FROM ca_player_logs WHERE GAME_ID = $gameId AND ACTIVITY_TYPE = 'GAME_VIEWED') AS unique_viewers,
          (SELECT COUNT(*) FROM ca_gamejoin WHERE GAME_ID = $gameId) AS joins,
          (SELECT COUNT(*) FROM ca_gamejoin cg JOIN ca_events e ON e.ID = cg.GAME_ID
             WHERE cg.GAME_ID = $gameId AND cg.CONFIRMED = 'Y' AND e.STATUS = 'Completed') AS completed
    ")->fetch() ?: [];

    json_out([
        'game' => [
            'id' => (int) $game['ID'],
            'name' => audit_game_name($game),
            'category' => $game['EVENT_CATEGORY'],
            'event_date' => $game['EVENT_DATE'],
            'status' => $game['STATUS'],
        ],
        'summary' => [
            'views'          => (int) ($head['views'] ?? 0),
            'unique_viewers' => (int) ($head['unique_viewers'] ?? 0),
            'joins'          => (int) ($head['joins'] ?? 0),
            'completed'      => (int) ($head['completed'] ?? 0),
        ],
        'data' => $data,
        'pagination' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => $pages],
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load game audience.', 'detail' => $e->getMessage()], 500);
}
