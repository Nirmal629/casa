<?php

declare(strict_types=1);

/** Audit and Activity Logging Helpers - File: app/Helpers/audit.php */

if (!function_exists('auditResolveGameHost')) {
    /** Resolve owning HOST_ID for a specific game ID from ca_events (0 if unknown). */
    function auditResolveGameHost($conn, $game_id): int
    {
        if (!$conn || !is_numeric($game_id)) {
            return 0;
        }

        $gameIdInt = (int)$game_id;
        try {
            $result = mysqli_query($conn, "SELECT HOST_ID FROM ca_events WHERE ID = $gameIdInt LIMIT 1");
            $row = $result ? mysqli_fetch_assoc($result) : null;
            return $row ? (int)($row['HOST_ID'] ?? 0) : 0;
        } catch (\Throwable $e) {
            error_log("auditResolveGameHost error: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('auditResolveHostId')) {
    /** Resolve HOST_ID for a given user, game, or request/session context. */
    function auditResolveHostId($conn, $user_id = null, $game_id = null, $provided_host_id = null): int
    {
        // 1. Direct explicit Host ID provided
        if ($provided_host_id !== null && is_numeric($provided_host_id) && (int)$provided_host_id > 0) {
            return (int)$provided_host_id;
        }

        // 2. Resolve Host ID from Game/Event association
        if ($game_id !== null && is_numeric($game_id) && (int)$game_id > 0 && function_exists('auditResolveGameHost')) {
            $gameHost = auditResolveGameHost($conn, $game_id);
            if ($gameHost > 0) {
                return $gameHost;
            }
        }

        // 3. Current logged-in Host or Trainer session check
        if (isset($_SESSION['usertype']) && (strcasecmp((string)$_SESSION['usertype'], 'Host') === 0 || strcasecmp((string)$_SESSION['usertype'], 'Trainer') === 0)) {
            $hostUser = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : ($user_id ? (int)$user_id : 0);
            if ($hostUser > 0) {
                return $hostUser;
            }
        }

        // 4. Request & Session fallback key search
        $sessionRequestKeys = [
            'host_id', 'HOST_ID', 'mapped_host_id', 'user_host_id', 'current_host_id', 'selected_host_id'
        ];
        foreach ($sessionRequestKeys as $k) {
            if (isset($_REQUEST[$k]) && is_numeric($_REQUEST[$k]) && (int)$_REQUEST[$k] > 0) {
                return (int)$_REQUEST[$k];
            }
            if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k]) && (int)$_SESSION[$k] > 0) {
                return (int)$_SESSION[$k];
            }
        }

        // 5. Database lookup fallback via club membership or recent game joins
        if ($conn && $user_id !== null && is_numeric($user_id) && (int)$user_id > 0) {
            $uid = (int)$user_id;

            try {
                // Check accepted club status
                $r1 = mysqli_query($conn, "SELECT host_id FROM ca_player_club_status WHERE player_id = $uid AND (status = 'accepted' OR status = 'Y') ORDER BY id DESC LIMIT 1");
                if ($r1 && ($row1 = mysqli_fetch_assoc($r1))) {
                    if (!empty($row1['host_id']) && (int)$row1['host_id'] > 0) {
                        return (int)$row1['host_id'];
                    }
                }

                // Check recent joined game event host
                $r2 = mysqli_query($conn, "SELECT ce.HOST_ID FROM ca_gamejoin cg JOIN ca_events ce ON cg.GAME_ID = ce.ID WHERE cg.USER_ID = $uid AND ce.HOST_ID IS NOT NULL AND ce.HOST_ID > 0 ORDER BY cg.ID DESC LIMIT 1");
                if ($r2 && ($row2 = mysqli_fetch_assoc($r2))) {
                    if (!empty($row2['HOST_ID']) && (int)$row2['HOST_ID'] > 0) {
                        return (int)$row2['HOST_ID'];
                    }
                }
            } catch (\Throwable $e) {
                error_log("auditResolveHostId query error: " . $e->getMessage());
            }
        }

        return 0;
    }
}

if (!function_exists('logPlayerActivity')) {
    /** Record a business activity in ca_player_logs (audit log source of truth). */
    function logPlayerActivity($conn, $user_id, string $activity_type, ?string $description = null, $host_id = null, $game_id = null, mixed $meta = null): void
    {
        if (!$conn || !is_numeric($user_id)) {
            return;
        }

        // Cache ca_player_logs column detection static variable per request
        static $hasExtended = null;
        if ($hasExtended === null) {
            $hasExtended = false;
            try {
                $probe = mysqli_query($conn, "SHOW COLUMNS FROM ca_player_logs LIKE 'HOST_ID'");
                if ($probe && mysqli_num_rows($probe) > 0) {
                    $hasExtended = true;
                }
            } catch (\Throwable $e) {
                $hasExtended = false;
            }
        }

        $userIdInt = (int)$user_id;
        $type = "'" . mysqli_real_escape_string($conn, (string)$activity_type) . "'";
        $desc = ($description === null || $description === '')
            ? "NULL" : "'" . mysqli_real_escape_string($conn, (string)$description) . "'";

        if (!$hasExtended) {
            try {
                mysqli_query($conn, "INSERT INTO ca_player_logs (USER_ID, ACTIVITY_TYPE, DESCRIPTION) VALUES ($userIdInt, $type, $desc)");
            } catch (\Throwable $e) {
                error_log("logPlayerActivity basic error: " . $e->getMessage());
            }
            return;
        }

        $resolvedHostId = auditResolveHostId($conn, $userIdInt, $game_id, $host_id);
        $hid = $resolvedHostId > 0 ? $resolvedHostId : 'NULL';
        $gid = is_numeric($game_id) && (int)$game_id > 0 ? (int)$game_id : 'NULL';

        if ($meta === null || $meta === '') {
            $metaSql = 'NULL';
        } else {
            $metaStr = is_string($meta) ? $meta : (json_encode($meta) ?: '');
            $metaSql = "'" . mysqli_real_escape_string($conn, $metaStr) . "'";
        }

        // Client IP extraction supporting proxies (X-Forwarded-For)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipParts[0]);
        }
        $ipSql = $ip === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, substr($ip, 0, 45)) . "'";

        try {
            mysqli_query(
                $conn,
                "INSERT INTO ca_player_logs (USER_ID, HOST_ID, GAME_ID, ACTIVITY_TYPE, DESCRIPTION, META, IP_ADDRESS)
                 VALUES ($userIdInt, $hid, $gid, $type, $desc, $metaSql, $ipSql)"
            );
        } catch (\Throwable $e) {
            error_log("logPlayerActivity extended error: " . $e->getMessage());
        }
    }
}
