<?php
session_start(); // Ensure session is started
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('dbConnection.php');

$data = json_decode(file_get_contents("php://input"), true);
$event_id = (int)$data['event_id'];

$players = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM ca_gamejoin
     WHERE GAME_ID='$event_id' AND STATUS='Y' AND CONFIRMED='Y'"
))['total'];

$divisor = $players > 4 ? $players : 4;

echo json_encode([
    "success" => true,
    "no_player_joined" => $divisor
]);

?>