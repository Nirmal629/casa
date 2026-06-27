<?php
session_start();
include 'dbConnection.php';
include 'helpers/gameAutoConfirm.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['USER_ID'];
    $gameId = $_POST['ID'];
    $hostId = $_POST['HOST_ID'];
    $cost = $_POST['EVENT_COST'];
    $currency = $_POST['EVENT_CURRENCY'];
    $createdAt = date('Y-m-d H:i:s'); // Get the current timestamp

    // Insert the invitation data into the `ca_gamejoin` table
    $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE,STATUS, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$cost', '$currency','Public','Y','$createdAt')";
    $result = mysqli_query($conn, $query);

    if ($result) {
             applyAutoConfirmAndMessage($conn, $gameId);



        echo json_encode(['status' => 'success', 'message' => 'Event Joined successfully.','outputHTML'=>""]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to Join.','outputHTML'=>null]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
