<?php
require 'dbConnection.php';
$res = $conn->query('SELECT * FROM ca_player_club_status');
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>
