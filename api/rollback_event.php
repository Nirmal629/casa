<?php
require_once __DIR__ . '/_security.php';
requireApiUser(['Host','Trainer']);

include('dbConnection.php');
requireApiPost();
requireOwnedEvent($conn, $_POST['event_id'] ?? null);
$event_id = (int) $_POST['event_id'];
$host_id = (int) $_SESSION['user_id'];
$sql = "UPDATE ca_events SET STATUS = 'Active' WHERE ID = $event_id AND HOST_ID = $host_id";

if ($conn->query($sql) === TRUE) {
    $response['success'] = true;
} else {
    $response['success'] = false;
    $response['error'] = 'Database error: ' . $conn->error;
}

echo json_encode($response);

?>