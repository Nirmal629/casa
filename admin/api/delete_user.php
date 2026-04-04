<?php
include '../dbConnection.php'; // Adjust with your DB connection script

if (isset($_POST['id'])) {
    // Retrieve and sanitize the ID
    $userId = intval($_POST['id']); 

    // Normal MySQLi query to update the DEL_STATUS
    $sql = "UPDATE ca_users SET DEL_STATUS = 'Y' WHERE ID = $userId";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "No ID provided."]);
}

?>
