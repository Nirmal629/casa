<?php
date_default_timezone_set('America/Toronto'); // Eastern Time (ET)
const DATABASE_NAME='casa_test';
const USERNAME="casa_test";
const PASSWORD="casa_test123#";

// Database configuration
$host = "localhost"; // Database host (e.g., localhost)

// Create connection
$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// echo "Connected successfully";

// Global Activity Logger
function logPlayerActivity($conn, $user_id, $activity_type, $description = null) {
    if (!$conn || empty($user_id) || empty($activity_type)) return false;
    $type = mysqli_real_escape_string($conn, $activity_type);
    $desc = $description ? "'" . mysqli_real_escape_string($conn, $description) . "'" : "NULL";
    $sql = "INSERT INTO `ca_player_logs` (`USER_ID`, `ACTIVITY_TYPE`, `DESCRIPTION`) VALUES ('$user_id', '$type', $desc)";
    return mysqli_query($conn, $sql);
}
?>