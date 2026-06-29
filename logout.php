<?php
session_start(); // Start the session if not already started
include('api/dbConnection.php');

if (isset($_SESSION['user_id'])) {
    logPlayerActivity($conn, $_SESSION['user_id'], 'LOGOUT', 'Successful logout');
}

session_destroy(); // Destroy the session
// Redirect to the desired location
header('Location: index.php');
exit; // Ensure no further code executes
?>
