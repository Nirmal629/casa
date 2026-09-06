<?php
date_default_timezone_set('America/Toronto');
require_once __DIR__ . '/config/env.php';

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

if (!function_exists('logPlayerActivity')) {
    /**
     * Record a business activity in ca_player_logs (the audit-log source of truth).
     *
     * Backwards compatible: the extra arguments are optional and the function
     * still works before the audit-log migration adds HOST_ID/GAME_ID/META/IP_ADDRESS.
     *
     * @param int|string      $host_id  owning host for game activity (NULL for global events like login)
     * @param int|string      $game_id  related game id, if any
     * @param array|string    $meta     extra context, stored as JSON text
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

        $hid = is_numeric($host_id) ? (int) $host_id : 'NULL';
        $gid = is_numeric($game_id) ? (int) $game_id : 'NULL';
        if ($meta === null || $meta === '') {
            $metaSql = 'NULL';
        } else {
            $metaStr = is_string($meta) ? $meta : json_encode($meta);
            $metaSql = "'" . mysqli_real_escape_string($conn, $metaStr) . "'";
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipSql = $ip === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, substr($ip, 0, 45)) . "'";

        @mysqli_query($conn,
            "INSERT INTO ca_player_logs (USER_ID, HOST_ID, GAME_ID, ACTIVITY_TYPE, DESCRIPTION, META, IP_ADDRESS)
             VALUES ($user_id, $hid, $gid, $type, $desc, $metaSql, $ipSql)");
    }
}

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
