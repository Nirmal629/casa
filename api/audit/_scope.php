<?php
/**
 * api/audit/_scope.php
 * ------------------------------------------------------------------
 * Reusable data-level authorization for the drill-down endpoints.
 * A host may only inspect:
 *   - a player who belongs to their "universe" (touched their games / club)
 *   - a game they own (ca_events.HOST_ID = host)
 * Every drill endpoint calls these before returning anything.
 * ------------------------------------------------------------------
 */

/** True if $userId is a player within $hostId's activity universe. */
function audit_user_in_scope(PDO $pdo, int $hostId, int $userId): bool
{
    if ($userId <= 0) return false;
    $st = $pdo->prepare("
        SELECT 1 FROM (
            SELECT USER_ID AS uid FROM ca_player_logs WHERE HOST_ID = $hostId
            UNION SELECT USER_ID FROM ca_gamejoin WHERE HOST_ID = $hostId
            UNION SELECT player_id FROM ca_player_club_status WHERE host_id = $hostId AND status = 'accepted'
        ) un WHERE un.uid = :u LIMIT 1
    ");
    $st->execute([':u' => $userId]);
    return (bool) $st->fetchColumn();
}

/** Return the game row (ID, CUP_NAME, EVENT_CATEGORY, EVENT_DATE, STATUS, HOST_NAME) if it belongs to the host, else null. */
function audit_game_in_scope(PDO $pdo, int $hostId, int $gameId): ?array
{
    if ($gameId <= 0) return null;
    $st = $pdo->prepare("
        SELECT ID, CUP_NAME, HOST_NAME, EVENT_CATEGORY, GENDER_CATEGORY, EVENT_TYPE,
               EVENT_DATE, EVENT_TIME, EVENT_VENUE, EVENT_COST, EVENT_CURRENCY, STATUS
        FROM ca_events WHERE ID = :g AND HOST_ID = $hostId LIMIT 1
    ");
    $st->execute([':g' => $gameId]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * Human game name. ca_events often has an empty CUP_NAME, so fall back to
 * "<category> · <date>" and finally "Game #<id>".
 * Accepts a row with keys CUP_NAME/HOST_NAME/EVENT_CATEGORY/EVENT_DATE/ID
 * (any casing subset).
 */
function audit_game_name($row): string
{
    $g = fn($k) => $row[$k] ?? $row[strtolower($k)] ?? null;
    $cup = trim((string) $g('CUP_NAME'));
    if ($cup !== '') return $cup;
    $cat  = trim((string) $g('EVENT_CATEGORY'));
    $date = $g('EVENT_DATE');
    if ($cat !== '' && $date) return $cat . ' - ' . date('j M Y', strtotime($date));
    if ($cat !== '') return $cat;
    $host = trim((string) $g('HOST_NAME'));
    if ($host !== '') return $host;
    return 'Game #' . ($g('ID') ?? '?');
}

/** Friendly label + tone for an activity type. */
function audit_type_label(string $type): array
{
    static $labels = [
        'LOGIN'            => ['Signed in',          'neutral', '→'],
        'LOGOUT'           => ['Signed out',         'neutral', '←'],
        'GAME_LIST_VIEWED' => ['Browsed game list',  'blue',    '≡'],
        'GAME_VIEWED'      => ['Viewed game',        'blue',    '👁'],
        'VIEW_PLAYERS'     => ['Viewed players',     'blue',    '👤'],
        'JOIN_GAME'        => ['Joined game',        'green',   '✔'],
        'LEAVE_GAME'       => ['Left game',          'red',     '✕'],
    ];
    if (isset($labels[$type])) {
        return ['label' => $labels[$type][0], 'tone' => $labels[$type][1], 'glyph' => $labels[$type][2]];
    }
    return ['label' => ucwords(strtolower(str_replace('_', ' ', $type))), 'tone' => 'neutral', 'glyph' => '•'];
}
