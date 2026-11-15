<?php
/**
 * api/audit/_metrics.php
 * ------------------------------------------------------------------
 * Host-scoped per-user engagement metrics, derived entirely from real
 * tables: ca_player_logs (activity), ca_gamejoin (joins), ca_events
 * (game outcome), ca_users, ca_player_club_status (membership).
 *
 * $HOST_ID is a hard (int) cast of the session user id in _bootstrap.php,
 * never request input, so it is interpolated directly. All genuine
 * request filters are bound / whitelisted by the caller.
 * ------------------------------------------------------------------
 */

/**
 * Build the metrics SELECT for the given host.
 * Returns SQL producing one row per Player in the host's "universe".
 *
 * Columns: ID, NAME, EMAIL, PROFILE_IMAGE, LOG_STATUS, CITY, joined_at,
 *          views, unique_games_viewed, list_views, joins, active_joins,
 *          leaves, completed, join_no_show, last_active, active_days, login_count
 */
function audit_metrics_sql(int $hostId): string
{
    $h = (int) $hostId;

    return "
    SELECT
        u.ID, u.NAME, u.EMAIL, u.PROFILE_IMAGE, u.LOG_STATUS, u.CITY,
        u.created_at AS joined_at,

        (SELECT COUNT(*) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND l.HOST_ID = $h AND l.ACTIVITY_TYPE = 'GAME_VIEWED') AS views,

        (SELECT COUNT(DISTINCT l.GAME_ID) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND l.HOST_ID = $h AND l.ACTIVITY_TYPE = 'GAME_VIEWED'
             AND l.GAME_ID IS NOT NULL) AS unique_games_viewed,

        (SELECT COUNT(*) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND l.HOST_ID = $h AND l.ACTIVITY_TYPE = 'GAME_LIST_VIEWED') AS list_views,

        (SELECT COUNT(DISTINCT cg.GAME_ID) FROM ca_gamejoin cg
           WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $h) AS joins_current,

        (SELECT COUNT(*) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND l.HOST_ID = $h AND l.ACTIVITY_TYPE = 'JOIN_GAME') AS join_events,

        (SELECT COUNT(*) FROM ca_gamejoin cg
           WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $h AND cg.STATUS = 'Y') AS active_joins,

        (SELECT COUNT(*) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND l.HOST_ID = $h AND l.ACTIVITY_TYPE = 'LEAVE_GAME') AS leaves,

        (SELECT COUNT(*) FROM ca_gamejoin cg JOIN ca_events e ON e.ID = cg.GAME_ID
           WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $h AND cg.CONFIRMED = 'Y' AND e.STATUS = 'Completed') AS completed,

        (SELECT COUNT(*) FROM ca_gamejoin cg JOIN ca_events e ON e.ID = cg.GAME_ID
           WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $h AND cg.CONFIRMED = 'N' AND e.STATUS = 'Completed') AS join_no_show,

        GREATEST(
          COALESCE((SELECT MAX(l.CREATED_AT) FROM ca_player_logs l
             WHERE l.USER_ID = u.ID AND (l.HOST_ID = $h OR l.ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))), '1000-01-01'),
          COALESCE((SELECT MAX(cg.CREATED_AT) FROM ca_gamejoin cg
             WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $h), '1000-01-01')
        ) AS last_active,

        (SELECT COUNT(DISTINCT DATE(l.CREATED_AT)) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND (l.HOST_ID = $h OR l.ACTIVITY_TYPE IN ('LOGIN','LOGOUT'))) AS log_days,

        (SELECT COUNT(DISTINCT DATE(cg.CREATED_AT)) FROM ca_gamejoin cg
           WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $h) AS join_days,

        (SELECT COUNT(*) FROM ca_player_logs l
           WHERE l.USER_ID = u.ID AND l.ACTIVITY_TYPE = 'LOGIN') AS login_count

    FROM ca_users u
    JOIN (
        SELECT USER_ID AS uid FROM ca_player_logs WHERE HOST_ID = $h
        UNION
        SELECT USER_ID FROM ca_gamejoin WHERE HOST_ID = $h
        UNION
        SELECT player_id FROM ca_player_club_status WHERE host_id = $h AND status = 'accepted'
    ) un ON un.uid = u.ID
    WHERE u.USERTYPE = 'Player' AND (u.DEL_STATUS IS NULL OR u.DEL_STATUS = 'N')
    ";
}

/**
 * Normalise a raw metrics row into the shape the UI + audit_behaviour() use.
 */
function audit_shape_row(array $r): array
{
    $views     = (int) $r['views'];
    $listViews = (int) $r['list_views'];
    $leaves    = (int) $r['leaves'];
    $noShow    = (int) $r['join_no_show'];
    // "joined" = games currently joined + games left since (leave deletes the
    // ca_gamejoin row, so it only survives in the activity log)
    $joins     = (int) $r['joins_current'] + $leaves;
    if ((int) $r['join_events'] > $joins) {
        $joins = (int) $r['join_events'];
    }
    $completed = (int) $r['completed'];
    $abandoned = $leaves + $noShow;
    $browse    = $views + $listViews;

    $last = $r['last_active'] ?? null;
    if ($last && strtotime($last) < strtotime('2005-01-01')) {
        $last = null;  // sentinel from GREATEST(...,'1000-01-01')
    }
    $sinceDays = null;
    if ($last) {
        $sinceDays = (int) floor((time() - strtotime($last)) / 86400);
    }
    $activeDays = (int) $r['log_days'] + (int) $r['join_days'];

    $metrics = [
        'views'             => $views,
        'unique_games_viewed' => (int) $r['unique_games_viewed'],
        'list_views'        => $listViews,
        'joins'             => $joins,
        'joins_current'     => (int) $r['joins_current'],
        'active_joins'      => (int) $r['active_joins'],
        'leaves'            => $leaves,
        'completed'         => $completed,
        'abandoned'         => $abandoned,
        'active_days'       => $activeDays,
        'login_count'       => (int) $r['login_count'],
        'days_since_active' => $sinceDays,
    ];

    $behaviour = audit_behaviour($metrics);

    // Engagement score 0-100: weighted, capped — a scannable single number.
    $score = 0;
    $score += min($browse, 20) * 1.0;                    // up to 20
    $score += min($joins, 10) * 4.0;                     // up to 40
    $score += min($completed, 10) * 3.0;                 // up to 30
    $score += min($activeDays, 10) * 1.0;                // up to 10
    $score -= min($abandoned, 10) * 2.0;                 // penalty
    $score = (int) max(0, min(100, round($score)));

    return [
        'id'         => (int) $r['ID'],
        'name'       => $r['NAME'] ?: ('User #' . $r['ID']),
        'email'      => $r['EMAIL'],
        'avatar'     => !empty($r['PROFILE_IMAGE']) ? 'profile_img/' . $r['PROFILE_IMAGE'] : null,
        'city'       => $r['CITY'],
        'account_active' => strcasecmp((string) $r['LOG_STATUS'], 'Y') === 0,
        'joined_at'  => $r['joined_at'],
        'last_active' => $last,
        'behaviour'  => $behaviour,
        'engagement' => $score,
        'view_join_rate' => $browse > 0 ? round($joins / $browse * 100) : 0,
        'metrics'    => $metrics,
    ];
}
