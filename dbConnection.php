<?php
require_once __DIR__ . '/app/Config/env.php';
$appTimezone = env('APP_TIMEZONE', env('TIMEZONE', 'Asia/Kolkata'));
if (!empty($appTimezone)) {
    @date_default_timezone_set((string) $appTimezone);
}

$host = env('DB_HOST', 'localhost');
$dbName = env('DB_NAME', 'casa_test');
$dbUser = env('DB_USER', 'root');
$dbPass = env('DB_PASS', '');
$dbCharset = env('DB_CHARSET', 'utf8mb4');

if (!defined('DATABASE_NAME')) {
    define('DATABASE_NAME', $dbName);
}
if (!defined('USERNAME')) {
    define('USERNAME', $dbUser);
}
if (!defined('PASSWORD')) {
    define('PASSWORD', $dbPass);
}

// Create connection
$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset (important for text issues)
$conn->set_charset($dbCharset);

if (!function_exists('auditResolveGameHost')) {
    /** Resolve the owning HOST_ID for a game id from ca_events (0 if unknown). */
    function auditResolveGameHost($conn, $game_id) {
        if (!$conn || !is_numeric($game_id)) return 0;
        $game_id = (int) $game_id;
        $r = @mysqli_query($conn, "SELECT HOST_ID FROM ca_events WHERE ID = $game_id LIMIT 1");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        return $row ? (int) $row['HOST_ID'] : 0;
    }
}

if (!function_exists('auditResolveHostId')) {
    /**
     * Resolve the HOST_ID for a given user, game, or current request/session context.
     */
    function auditResolveHostId($conn, $user_id = null, $game_id = null, $provided_host_id = null) {
        if ($provided_host_id !== null && is_numeric($provided_host_id) && (int)$provided_host_id > 0) {
            return (int)$provided_host_id;
        }

        if ($game_id !== null && is_numeric($game_id) && (int)$game_id > 0 && function_exists('auditResolveGameHost')) {
            $gameHost = auditResolveGameHost($conn, $game_id);
            if ($gameHost > 0) {
                return $gameHost;
            }
        }

        if (isset($_SESSION['usertype']) && (strcasecmp($_SESSION['usertype'], 'Host') === 0 || strcasecmp($_SESSION['usertype'], 'Trainer') === 0)) {
            $hostUser = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : ($user_id ? (int)$user_id : 0);
            if ($hostUser > 0) {
                return $hostUser;
            }
        }

        $sessionRequestKeys = [
            'host_id', 'HOST_ID', 'mapped_host_id', 'user_host_id', 'current_host_id', 'selected_host_id'
        ];
        foreach ($sessionRequestKeys as $k) {
            if (isset($_GET[$k]) && is_numeric($_GET[$k]) && (int)$_GET[$k] > 0) {
                return (int)$_GET[$k];
            }
            if (isset($_POST[$k]) && is_numeric($_POST[$k]) && (int)$_POST[$k] > 0) {
                return (int)$_POST[$k];
            }
            if (isset($_REQUEST[$k]) && is_numeric($_REQUEST[$k]) && (int)$_REQUEST[$k] > 0) {
                return (int)$_REQUEST[$k];
            }
            if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k]) && (int)$_SESSION[$k] > 0) {
                return (int)$_SESSION[$k];
            }
        }

        if ($conn && $user_id !== null && is_numeric($user_id) && (int)$user_id > 0) {
            $uid = (int)$user_id;
            
            $r1 = @mysqli_query($conn, "SELECT host_id FROM ca_player_club_status WHERE player_id = $uid AND (status = 'accepted' OR status = 'Y') ORDER BY id DESC LIMIT 1");
            if ($r1 && ($row1 = mysqli_fetch_assoc($r1))) {
                if (!empty($row1['host_id']) && (int)$row1['host_id'] > 0) {
                    return (int)$row1['host_id'];
                }
            }

            $r2 = @mysqli_query($conn, "SELECT ce.HOST_ID FROM ca_gamejoin cg JOIN ca_events ce ON cg.GAME_ID = ce.ID WHERE cg.USER_ID = $uid AND ce.HOST_ID IS NOT NULL AND ce.HOST_ID > 0 ORDER BY cg.ID DESC LIMIT 1");
            if ($r2 && ($row2 = mysqli_fetch_assoc($r2))) {
                if (!empty($row2['HOST_ID']) && (int)$row2['HOST_ID'] > 0) {
                    return (int)$row2['HOST_ID'];
                }
            }
        }

        return 0;
    }
}

if (!function_exists('logPlayerActivity')) {
    /**
     * Record a business activity in ca_player_logs (the audit-log source of truth).
     */
    function logPlayerActivity($conn, $user_id, $activity_type, $description = null, $host_id = null, $game_id = null, $meta = null) {
        if (!$conn || !is_numeric($user_id)) return;

        static $hasExtended = null;
        if ($hasExtended === null) {
            $hasExtended = false;
            $probe = @mysqli_query($conn, "SHOW COLUMNS FROM ca_player_logs LIKE 'HOST_ID'");
            if ($probe && mysqli_num_rows($probe) > 0) $hasExtended = true;
        }

        $user_id = (int) $user_id;
        $type = "'" . mysqli_real_escape_string($conn, (string) $activity_type) . "'";
        $desc = ($description === null || $description === '')
            ? "NULL" : "'" . mysqli_real_escape_string($conn, (string) $description) . "'";

        if (!$hasExtended) {
            @mysqli_query($conn, "INSERT INTO ca_player_logs (USER_ID, ACTIVITY_TYPE, DESCRIPTION) VALUES ($user_id, $type, $desc)");
            return;
        }

        $resolvedHostId = auditResolveHostId($conn, $user_id, $game_id, $host_id);
        $hid = $resolvedHostId > 0 ? $resolvedHostId : 'NULL';
        $gid = is_numeric($game_id) && (int)$game_id > 0 ? (int)$game_id : 'NULL';
        if ($meta === null || $meta === '') {
            $metaSql = 'NULL';
        } else {
            $metaStr = is_string($meta) ? $meta : json_encode($meta);
            $metaSql = "'" . mysqli_real_escape_string($conn, $metaStr) . "'";
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipParts[0]);
        }
        $ipSql = $ip === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, substr($ip, 0, 45)) . "'";

        @mysqli_query($conn,
            "INSERT INTO ca_player_logs (USER_ID, HOST_ID, GAME_ID, ACTIVITY_TYPE, DESCRIPTION, META, IP_ADDRESS)
             VALUES ($user_id, $hid, $gid, $type, $desc, $metaSql, $ipSql)");
    }
}
