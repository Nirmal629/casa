<?php
date_default_timezone_set('America/Toronto');
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

// echo "Connected successfully";

?>
$conn->set_charset($dbCharset);
