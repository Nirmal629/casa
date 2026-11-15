<?php
/**
 * api/audit/event.php — detail for a single activity-log row.
 *
 * ?id=  (ca_player_logs.ID, required)
 *
 * Auth: the event's user must be in the host's universe, and (when the row
 * is host-scoped) its HOST_ID must be this host.
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_scope.php';

$H  = $HOST_ID;
$id = audit_int($_GET['id'] ?? 0, 0, 0, PHP_INT_MAX);
if ($id <= 0) {
    json_out(['error' => 'bad_request', 'message' => 'id is required.'], 400);
}

try {
    $st = $pdo->prepare("
        SELECT l.*, e.CUP_NAME, e.HOST_NAME, e.EVENT_CATEGORY, e.EVENT_DATE, e.EVENT_TIME,
               e.EVENT_VENUE, e.STATUS AS GAME_STATUS,
               u.NAME AS USER_NAME, u.EMAIL AS USER_EMAIL, u.PROFILE_IMAGE
        FROM ca_player_logs l
        LEFT JOIN ca_events e ON e.ID = l.GAME_ID
        LEFT JOIN ca_users  u ON u.ID = l.USER_ID
        WHERE l.ID = :id LIMIT 1
    ");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    if (!$row) {
        json_out(['error' => 'not_found', 'message' => 'Event not found.'], 404);
    }

    $eventUser = (int) $row['USER_ID'];
    if (!audit_user_in_scope($pdo, $H, $eventUser)) {
        json_out(['error' => 'forbidden', 'message' => 'Not permitted.'], 403);
    }
    if ($row['HOST_ID'] !== null && (int) $row['HOST_ID'] !== $H) {
        json_out(['error' => 'forbidden', 'message' => 'Not permitted.'], 403);
    }

    // previous / next action by the same user (host-scoped or global session events)
    $adjSql = "
        SELECT ID, ACTIVITY_TYPE, CREATED_AT, GAME_ID
        FROM ca_player_logs
        WHERE USER_ID = :u AND (HOST_ID = $H OR ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))
          AND %s
        ORDER BY CREATED_AT %s, ID %s LIMIT 1
    ";
    $prevSt = $pdo->prepare(sprintf($adjSql, "(CREATED_AT < :c OR (CREATED_AT = :c2 AND ID < :i))", 'DESC', 'DESC'));
    $prevSt->execute([':u' => $eventUser, ':c' => $row['CREATED_AT'], ':c2' => $row['CREATED_AT'], ':i' => $id]);
    $prev = $prevSt->fetch() ?: null;

    $nextSt = $pdo->prepare(sprintf($adjSql, "(CREATED_AT > :c OR (CREATED_AT = :c2 AND ID > :i))", 'ASC', 'ASC'));
    $nextSt->execute([':u' => $eventUser, ':c' => $row['CREATED_AT'], ':c2' => $row['CREATED_AT'], ':i' => $id]);
    $next = $nextSt->fetch() ?: null;

    $adj = function ($x) {
        if (!$x) return null;
        $lab = audit_type_label($x['ACTIVITY_TYPE']);
        return ['id' => (int) $x['ID'], 'type' => $x['ACTIVITY_TYPE'], 'label' => $lab['label'],
                'tone' => $lab['tone'], 'at' => $x['CREATED_AT'], 'game_id' => $x['GAME_ID'] ? (int) $x['GAME_ID'] : null];
    };

    $lab = audit_type_label($row['ACTIVITY_TYPE']);
    $meta = null;
    if (!empty($row['META'])) {
        $decoded = json_decode($row['META'], true);
        $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : $row['META'];
    }

    json_out([
        'event' => [
            'id'        => (int) $row['ID'],
            'type'      => $row['ACTIVITY_TYPE'],
            'label'     => $lab['label'],
            'tone'      => $lab['tone'],
            'at'        => $row['CREATED_AT'],
            'description' => $row['DESCRIPTION'],
            'meta'      => $meta,
            'ip'        => $row['IP_ADDRESS'] ?? null,
            'user'      => [
                'id'    => $eventUser,
                'name'  => $row['USER_NAME'] ?: ('User #' . $eventUser),
                'email' => $row['USER_EMAIL'],
                'avatar' => !empty($row['PROFILE_IMAGE']) ? 'profile_img/' . $row['PROFILE_IMAGE'] : null,
            ],
            'game'      => $row['GAME_ID'] ? [
                'id'       => (int) $row['GAME_ID'],
                'name'     => audit_game_name($row + ['ID' => $row['GAME_ID']]),
                'category' => $row['EVENT_CATEGORY'],
                'date'     => $row['EVENT_DATE'],
                'status'   => $row['GAME_STATUS'],
            ] : null,
        ],
        'previous' => $adj($prev),
        'next'     => $adj($next),
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load event.', 'detail' => $e->getMessage()], 500);
}
