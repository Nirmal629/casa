<?php

 //Centralized Database Connection
 
require_once __DIR__ . '/env.php';

// Expose constants
if (!defined('DATABASE_NAME')) define('DATABASE_NAME', env('DB_NAME', 'casa_test'));
if (!defined('USERNAME'))      define('USERNAME', env('DB_USER', 'root'));
if (!defined('PASSWORD'))      define('PASSWORD', env('DB_PASS', ''));

// 1. MySQLi Connection (Initialized immediately as it is used globally)
global $conn;
if (!isset($conn)) {
    $conn = new mysqli(env('DB_HOST', '127.0.0.1'), USERNAME, PASSWORD, DATABASE_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset(env('DB_CHARSET', 'utf8mb4'));
}

function getDbConnection(): mysqli {
    global $conn;
    return $conn;
}

// 2. PDO Connection (Lazy Loaded - only initialized if called)
global $pdo;
function getPdoConnection(): PDO {
    global $pdo;
    if (!isset($pdo)) {
        $dsn = "mysql:host=" . env('DB_HOST', '127.0.0.1') . ";dbname=" . DATABASE_NAME . ";charset=" . env('DB_CHARSET', 'utf8mb4');
        $pdo = new PDO($dsn, USERNAME, PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// 3. Log Activity Helper
if (!function_exists('logPlayerActivity')) {
    function logPlayerActivity($conn, $user_id, $activity_type, $description = null) {
        if (!$conn || !is_numeric($user_id)) return;
        $user_id = (int)$user_id;
        $type = mysqli_real_escape_string($conn, $activity_type);
        $desc = $description ? "'" . mysqli_real_escape_string($conn, $description) . "'" : "NULL";
        mysqli_query($conn, "INSERT INTO ca_player_logs (USER_ID, ACTIVITY_TYPE, DESCRIPTION) VALUES ($user_id, '$type', $desc)");
    }
}
