<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('dbConnection.php');
include 'helpers/gameAutoConfirm.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($_POST['type'] == 'checked')
    {
        if($_POST['dtype'] == 'invite')
        {
        // print_r($_POST);exit;
            $userId = $_POST['user_id'];
            $gameId = $_POST['game_id'];
            $hostId = $_POST['host_id'];
            $currency = $_POST['currency'];
            $price = $_POST['price'];
            $createdAt = date('Y-m-d H:i:s'); // Get the current timestamp
        
            // Insert the invitation data into the `ca_gamejoin` table
            $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$price','$currency','Invite', '$createdAt')";
            $result = mysqli_query($conn, $query);
        
            if ($result) {
    applyAutoConfirmAndMessage($conn, $gameId);

                echo json_encode(['status' => 'success', 'message' => 'Invitation sent successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send invitation.']);
            }
        }
        else
        {
            $userId = $_POST['user_id'];
            $gameId = $_POST['game_id'];
            $hostId = $_POST['host_id'];
            $currency = $_POST['currency'];
            $price = $_POST['price'];
            $createdAt = date('Y-m-d H:i:s'); // Get the current timestamp
        
            // Insert the invitation data into the `ca_gamejoin` table
            $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE,STATUS, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$price', '$currency','Public','Y','$createdAt')";

            // $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$price','$currency','Invite', '$createdAt')";
            $result = mysqli_query($conn, $query);
        
            if ($result) {
                
    applyAutoConfirmAndMessage($conn, $gameId);

                echo json_encode(['status' => 'success', 'message' => 'Invitation sent successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send invitation.']);
            }
        }
    }
    else
    {
        $query = "DELETE FROM ca_gamejoin WHERE USER_ID='".$_POST['user_id']."' AND GAME_ID='".$_POST['game_id']."'";
        $result = mysqli_query($conn, $query);
    applyAutoConfirmAndMessage($conn, $_POST['game_id']);

    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>