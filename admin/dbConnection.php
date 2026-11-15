<?php
require_once __DIR__ . '/_session.php';
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__DIR__ . '/index.php')) requireAdminSession();
if (file_exists(__DIR__ . '/../app/Config/env.php')) {
    require_once __DIR__ . '/../app/Config/env.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Config/Timezone.php';
$appTimezone = \App\Config\Timezone::name();
date_default_timezone_set($appTimezone);
} elseif (file_exists(__DIR__ . '/../app/config/env.php')) {
    require_once __DIR__ . '/../app/config/env.php';
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

$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset($dbCharset);
