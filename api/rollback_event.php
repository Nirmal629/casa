<?php
session_start();
include('dbConnection.php');
$event_id = $_POST['event_id'];
$host_id = $_POST['host_id'];

$sql = "UPDATE ca_events 
        SET STATUS = 'Active'
        WHERE ID = '$event_id'";

if ($conn->query($sql) === TRUE) {
    $response['success'] = true;
} else {
    $response['success'] = false;
    $response['error'] = 'Database error: ' . $conn->error;
}

echo json_encode($response);

?>