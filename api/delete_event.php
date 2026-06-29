<?php
include 'dbConnection.php';

$response = array();

// Validate and sanitize input
if (isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // Delete query
    $sql = "DELETE FROM ca_events WHERE ID = '$id'";

    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
    } else {
        $response['success'] = false;
        $response['error'] = 'Database error: ' . $conn->error;
    }
} else {
    $response['success'] = false;
    $response['error'] = 'Invalid event ID.';
}

echo json_encode($response);
?>
