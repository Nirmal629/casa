<?php
session_start();
include('../dbConnection.php');

$level = $_POST['level'] ?? '';
$gender = $_POST['gender'] ?? '';

$q = "SELECT ID, NAME, VERIFIED_LEVEL FROM ca_users 
      WHERE USERTYPE='Player' AND DEL_STATUS='N' AND LOG_STATUS='Y'";

if ($level !== '') $q .= " AND VERIFIED_LEVEL='$level'";
if ($gender !== '') $q .= " AND GENDER='$gender'";

$q .= " ORDER BY NAME ASC";

$res = mysqli_query($conn, $q);

$players = [];
while ($r = mysqli_fetch_assoc($res)) $players[] = $r;

echo json_encode(['players' => $players]);
