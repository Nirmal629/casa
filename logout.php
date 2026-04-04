<?php
session_start(); // Start the session if not already started
session_destroy(); // Destroy the session

// Redirect to the desired location
header('Location: https://casainfotech.com/index.php');
exit; // Ensure no further code executes
?>
