<?php
date_default_timezone_set('America/Toronto'); // Eastern Time (ET)
require_once __DIR__ . '/../config/env.php';

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

$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset($dbCharset);

if (!function_exists('logPlayerActivity')) {
    function logPlayerActivity($conn, $user_id, $activity_type, $description = null) {
        if (!$conn || !is_numeric($user_id)) return;
        $user_id = (int)$user_id;
        $activity_type = mysqli_real_escape_string($conn, $activity_type);
        $description = $description ? "'" . mysqli_real_escape_string($conn, $description) . "'" : "NULL";
        $sql = "INSERT INTO ca_player_logs (USER_ID, ACTIVITY_TYPE, DESCRIPTION) VALUES ($user_id, '$activity_type', $description)";
        mysqli_query($conn, $sql);
    }
}
