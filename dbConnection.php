<?php
date_default_timezone_set('America/Toronto');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
if (!defined('DATABASE_NAME')) define('DATABASE_NAME', 'casa_test');
if (!defined('USERNAME'))      define('USERNAME', 'casa_test');
if (!defined('PASSWORD'))      define('PASSWORD', 'casa_test123#');
// Database configuration
$host = "127.0.0.1"; // Database host (e.g., localhost)

// Create connection
$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// echo "Connected successfully";

// Set charset (important for text issues)
$conn->set_charset("utf8mb4");

// Run temporary database migration
include_once(__DIR__ . '/db_migration.php');
?>