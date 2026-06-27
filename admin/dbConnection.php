<?php
date_default_timezone_set('America/Toronto');
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

?>