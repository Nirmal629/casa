<?php
/**
 * api/audit/user_game.php — "what exactly did this user do with this game?"
 *
 * ?user_id=  ?game_id=   (both required)
 *
 * Auth: user must be in the host universe AND the game must belong to the host.
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_scope.php';

$H      = $HOST_ID;
$userId = audit_int($_GET['user_id'] ?? 0, 0, 0, PHP_INT_MAX);
$gameId = audit_int($_GET['game_id'] ?? 0, 0, 0, PHP_INT_MAX);

if ($userId <= 0 || $gameId <= 0) {
    json_out(['error' => 'bad_request', 'message' => 'user_id and game_id are required.'], 400);
}
if (!audit_user_in_scope($pdo, $H, $userId)) {
    json_out(['error' => 'forbidden', 'message' => 'This user is not part of your activity.'], 403);
}
$game = audit_game_in_scope($pdo, $H, $gameId);
if (!$game) {
    json_out(['error' => 'forbidden', 'message' => 'That game is not one of yours.'], 403);
}

try {
    $agg = $pdo->prepare("
        SELECT
          (SELECT COUNT(*)          FROM ca_player_logs WHERE USER_ID = :u1 AND GAME_ID = :g1 AND ACTIVITY_TYPE = 'GAME_VIEWED') AS view_count,
          (SELECT MIN(CREATED_AT)   FROM ca_player_logs WHERE USER_ID = :u2 AND GAME_ID = :g2 AND ACTIVITY_TYPE = 'GAME_VIEWED') AS first_viewed,
          (SELECT MAX(CREATED_AT)   FROM ca_player_logs WHERE USER_ID = :u3 AND GAME_ID = :g3 AND ACTIVITY_TYPE = 'GAME_VIEWED') AS last_viewed,
          (SELECT MAX(CREATED_AT)   FROM ca_player_logs WHERE USER_ID = :u4 AND GAME_ID = :g4 AND ACTIVITY_TYPE = 'LEAVE_GAME')  AS left_at,
          (SELECT MAX(CREATED_AT)   FROM ca_player_logs WHERE USER_ID = :u5 AND GAME_ID = :g5) AS last_activity,
          (SELECT COUNT(DISTINCT DATE(CREATED_AT)) FROM ca_player_logs WHERE USER_ID = :u6 AND GAME_ID = :g6) AS log_days,
          (SELECT cg.CREATED_AT FROM ca_gamejoin cg WHERE cg.USER_ID = :u7 AND cg.GAME_ID = :g7 LIMIT 1) AS joined_at,
          (SELECT cg.CONFIRMED  FROM ca_gamejoin cg WHERE cg.USER_ID = :u8 AND cg.GAME_ID = :g8 LIMIT 1) AS confirmed,
          (SELECT cg.STATUS     FROM ca_gamejoin cg WHERE cg.USER_ID = :u9 AND cg.GAME_ID = :g9 LIMIT 1) AS join_status,
          (SELECT COUNT(*)      FROM ca_gamejoin cg WHERE cg.USER_ID = :u10 AND cg.GAME_ID = :g10) AS join_rows
    ");
    $b = [];
    for ($i = 1; $i <= 10; $i++) { $b[":u$i"] = $userId; $b[":g$i"] = $gameId; }
    $agg->execute($b);
    $a = $agg->fetch() ?: [];

    // session count involving this game = distinct days with any activity on it
    // (view logs) + the join day if separate
    $joined    = ((int) $a['join_rows']) > 0;
    $left      = !empty($a['left_at']);
    $completed = $joined && strcasecmp((string) $a['confirmed'], 'Y') === 0 && strcasecmp((string) $game['STATUS'], 'Completed') === 0;
    $abandoned = $left
        || ($joined && strcasecmp((string) $game['STATUS'], 'Completed') === 0 && strcasecmp((string) $a['confirmed'], 'Y') !== 0)
        || ($joined && strcasecmp((string) $game['STATUS'], 'Cancelled') === 0);

    // rough "time spent": minutes between first view and last activity on this game, capped
    $spent = null;
    if ($a['first_viewed'] && $a['last_activity']) {
        $mins = max(0, (int) round((strtotime($a['last_activity']) - strtotime($a['first_viewed'])) / 60));
        $spent = $mins > 0 && $mins < 60 * 24 * 30 ? $mins : null;
    }

    // last 12 raw events for this user+game (mini feed inside the panel)
    $evStmt = $pdo->prepare("
        SELECT ID, ACTIVITY_TYPE, CREATED_AT, DESCRIPTION
        FROM ca_player_logs
        WHERE USER_ID = :u AND GAME_ID = :g
        ORDER BY CREATED_AT DESC, ID DESC LIMIT 12
    ");
    $evStmt->execute([':u' => $userId, ':g' => $gameId]);
    $events = [];
    foreach ($evStmt->fetchAll() as $e) {
        $lab = audit_type_label($e['ACTIVITY_TYPE']);
        $events[] = ['id' => (int) $e['ID'], 'type' => $e['ACTIVITY_TYPE'], 'label' => $lab['label'], 'tone' => $lab['tone'], 'at' => $e['CREATED_AT']];
    }

    $sessions = (int) $a['log_days'];
    if ($a['joined_at'] && (!$a['first_viewed'] || substr($a['joined_at'], 0, 10) !== substr((string) $a['first_viewed'], 0, 10))) {
        $sessions = max($sessions, $sessions + 1);
    }
    $sessions = max($sessions, ($joined ? 1 : 0), ((int) $a['view_count'] > 0 ? 1 : 0));

    json_out([
        'game' => [
            'id'       => (int) $game['ID'],
            'name'     => audit_game_name($game),
            'category' => $game['EVENT_CATEGORY'],
            'gender'   => $game['GENDER_CATEGORY'],
            'type'     => $game['EVENT_TYPE'],
            'venue'    => $game['EVENT_VENUE'],
            'date'     => $game['EVENT_DATE'],
            'time'     => $game['EVENT_TIME'],
            'cost'     => $game['EVENT_COST'],
            'currency' => $game['EVENT_CURRENCY'],
            'status'   => $game['STATUS'],
        ],
        'relationship' => [
            'view_count'    => (int) $a['view_count'],
            'first_viewed'  => $a['first_viewed'],
            'last_viewed'   => $a['last_viewed'],
            'joined'        => $joined,
            'joined_at'     => $a['joined_at'],
            'left'          => $left,
            'left_at'       => $a['left_at'],
            'completed'     => $completed,
            'abandoned'     => $abandoned,
            'sessions'      => $sessions,
            'time_spent_min' => $spent,
            'last_activity' => $a['last_activity'],
        ],
        'events' => $events,
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load user–game detail.', 'detail' => $e->getMessage()], 500);
}
