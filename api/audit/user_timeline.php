<?php
/**
 * api/audit/user_timeline.php — one player's activity journey for the host.
 *
 * ?user_id=  (required)
 * ?page= &per_page=   (paginated, newest first, grouped by day client-side)
 * ?activity=  optional single ACTIVITY_TYPE filter
 * ?game_id=   optional — only events tied to this game
 * ?range= / ?from= ?to=   optional date window
 *
 * Data-level authorization: the host may only inspect a user who belongs to
 * their universe (interacted with their games / is their club member).
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_metrics.php';
require __DIR__ . '/_scope.php';

$H       = $HOST_ID;
$userId  = audit_int($_GET['user_id'] ?? 0, 0, 0, PHP_INT_MAX);
$page    = audit_int($_GET['page'] ?? 1, 1, 1, 100000);
$perPage = audit_int($_GET['per_page'] ?? 40, 40, 10, 100);
$activity = trim((string) ($_GET['activity'] ?? ''));
$gameFilter = audit_int($_GET['game_id'] ?? 0, 0, 0, PHP_INT_MAX);
$hasRange = isset($_GET['range']) || isset($_GET['from']) || isset($_GET['to']);
$win = $hasRange ? audit_range() : null;

if ($userId <= 0) {
    json_out(['error' => 'bad_request', 'message' => 'user_id is required.'], 400);
}

try {
    /* ---- authorization: user must be in this host's universe ---------- */
    $chk = $pdo->prepare("
        SELECT 1 FROM (
            SELECT USER_ID AS uid FROM ca_player_logs WHERE HOST_ID = $H
            UNION SELECT USER_ID FROM ca_gamejoin WHERE HOST_ID = $H
            UNION SELECT player_id FROM ca_player_club_status WHERE host_id = $H AND status = 'accepted'
        ) un WHERE un.uid = :u LIMIT 1
    ");
    $chk->execute([':u' => $userId]);
    if (!$chk->fetchColumn()) {
        json_out(['error' => 'forbidden', 'message' => 'This user is not part of your activity.'], 403);
    }

    /* ---- user header + metrics -------------------------------------- */
    $uStmt = $pdo->prepare(audit_metrics_sql($H) . " AND u.ID = :uid ");
    $uStmt->execute([':uid' => $userId]);
    $mrow = $uStmt->fetch();
    $profile = $mrow ? audit_shape_row($mrow) : null;

    /* ---- activity feed (host-scoped rows + global session events) ---- */
    $where = "l.USER_ID = :u AND (l.HOST_ID = $H OR l.ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))";
    $bind  = [':u' => $userId];
    if ($activity !== '' && preg_match('/^[A-Z_]{2,40}$/', $activity)) {
        $where .= " AND l.ACTIVITY_TYPE = :act ";
        $bind[':act'] = $activity;
    }
    if ($gameFilter > 0) {
        $where .= " AND l.GAME_ID = :gf ";
        $bind[':gf'] = $gameFilter;
    }
    if ($win) {
        $where .= " AND l.CREATED_AT BETWEEN :wf AND :wt ";
        $bind[':wf'] = $win['from'];
        $bind[':wt'] = $win['to'];
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ca_player_logs l WHERE $where");
    $countStmt->execute($bind);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $feedStmt = $pdo->prepare("
        SELECT l.ID, l.ACTIVITY_TYPE, l.DESCRIPTION, l.META, l.GAME_ID, l.CREATED_AT,
               e.CUP_NAME, e.EVENT_CATEGORY, e.EVENT_DATE
        FROM ca_player_logs l
        LEFT JOIN ca_events e ON e.ID = l.GAME_ID
        WHERE $where
        ORDER BY l.CREATED_AT DESC, l.ID DESC
        LIMIT $perPage OFFSET $offset
    ");
    $feedStmt->execute($bind);

    $labels = [
        'LOGIN'            => 'Signed in',
        'LOGOUT'           => 'Signed out',
        'GAME_LIST_VIEWED' => 'Browsed game list',
        'GAME_VIEWED'      => 'Viewed game',
        'JOIN_GAME'        => 'Joined game',
        'LEAVE_GAME'       => 'Left game',
        'VIEW_PLAYERS'     => 'Viewed players',
    ];
    $tones = [
        'JOIN_GAME'   => 'green',
        'LEAVE_GAME'  => 'red',
        'GAME_VIEWED' => 'blue',
        'GAME_LIST_VIEWED' => 'blue',
        'LOGIN'       => 'neutral',
        'LOGOUT'      => 'neutral',
    ];

    $feed = [];
    foreach ($feedStmt->fetchAll() as $row) {
        $type = $row['ACTIVITY_TYPE'];
        $gameName = $row['GAME_ID'] ? audit_game_name($row + ['ID' => $row['GAME_ID']]) : null;
        $feed[] = [
            'id'        => (int) $row['ID'],
            'type'      => $type,
            'label'     => $labels[$type] ?? ucwords(strtolower(str_replace('_', ' ', $type))),
            'tone'      => $tones[$type] ?? 'neutral',
            'at'        => $row['CREATED_AT'],
            'game_id'   => $row['GAME_ID'] ? (int) $row['GAME_ID'] : null,
            'game_name' => $gameName,
            'game_category' => $row['EVENT_CATEGORY'],
            'detail'    => $row['DESCRIPTION'],
        ];
    }

    json_out([
        'user' => $profile,
        'feed' => $feed,
        'filters' => [
            'activity' => $activity ?: null,
            'game_id'  => $gameFilter ?: null,
            'range'    => $win ? $win['key'] : null,
        ],
        'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => $pages],
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load timeline.', 'detail' => $e->getMessage()], 500);
}
