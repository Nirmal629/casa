<?php
include('dbConnection.php');
header('Content-Type: application/json');

$level = $_GET['level'] ?? '';
if (!$level) {
    echo json_encode(['total' => 0]);
    exit;
}

$res = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM ca_users 
    WHERE VERIFIED_LEVEL = '$level'
");

$row = mysqli_fetch_assoc($res);
$total = $row['total'] ?? 0;

echo json_encode(['total' => $total]);
