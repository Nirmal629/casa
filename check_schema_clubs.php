<?php
require 'dbConnection.php';
$res = $conn->query('SHOW COLUMNS FROM ca_clubs');
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>
