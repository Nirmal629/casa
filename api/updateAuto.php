<?php
include 'dbConnection.php';
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
print_r($data);
echo $gameId = $data['eventID'];
exit;
?>