<?php
date_default_timezone_set('America/Toronto');
const DATABASE_NAME='casa_db';
const USERNAME="casa_sports";
const PASSWORD="C@sa_sports24#";

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