<?php
// print_r($_POST);exit;
include('../dbConnection.php');

if (isset($_POST['user_id']) && isset($_POST['new_status'])) {
    $userId = $_POST['user_id'];
    $newStatus = $_POST['new_status'];

    // Ensure that the new status is either 'Y' or 'N'
    if ($newStatus != 'Y' && $newStatus != 'N') {
        echo "Invalid status value";
        exit;
    }

    // Update the user's status in the database
    $sql = "UPDATE ca_users SET LOG_STATUS = ? WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newStatus, $userId);  // 'si' means string and integer

    if ($stmt->execute()) {
        echo "Status updated successfully";
    } else {
        echo "Error updating status: " . $stmt->error;  // Display error message for debugging
    }

    $stmt->close();
}
?>
