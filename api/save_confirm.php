<?php
session_start();
include('dbConnection.php');
include('helpers/gameAutoConfirm.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($_POST['type'] == 'checked')
    {
        
            $userId = $_POST['user_id'];
            $gameId = $_POST['game_id'];
            $hostId = $_POST['host_id'];
            $currency = $_POST['currency'];
            $price = $_POST['price'];
            $createdAt = date('Y-m-d H:i:s'); // Get the current timestamp
        
            $check_game_exits = mysqli_query($conn,"select * from ca_gamejoin where USER_ID='".$_POST['user_id']."' AND GAME_ID='".$_POST['game_id']."'");
            $count_rows = mysqli_num_rows($check_game_exits);
            // Insert the invitation data into the `ca_gamejoin` table
            if($count_rows > 0)
            {
                $query = "UPDATE ca_gamejoin SET CONFIRMED = 'Y' WHERE USER_ID='".$_POST['user_id']."' AND GAME_ID='".$_POST['game_id']."'";
    
                // $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$price','$currency','Invite', '$createdAt')";
                $result = mysqli_query($conn, $query);
            
                if ($result) {
                    // $joinMessage = applyAutoConfirmAndMessage($conn, $gameId,false);

                    echo json_encode(['status' => 'success', 'message' => 'Status Updated successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update invitation.']);
                }
            }
            else
            {
                echo json_encode(['status' => 'error', 'message' => 'Player Not yet added']);
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
        
            $check_game_exits = mysqli_query($conn,"select * from ca_gamejoin where USER_ID='".$_POST['user_id']."' AND GAME_ID='".$_POST['game_id']."'");
            $count_rows = mysqli_num_rows($check_game_exits);
            // Insert the invitation data into the `ca_gamejoin` table
            if($count_rows > 0)
            {
                echo $query = "UPDATE ca_gamejoin SET CONFIRMED = 'N' WHERE USER_ID='".$_POST['user_id']."' AND GAME_ID='".$_POST['game_id']."'";
    
                // $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$price','$currency','Invite', '$createdAt')";
                $result = mysqli_query($conn, $query);
            
                if ($result) {
                    // $joinMessage = applyAutoConfirmAndMessage($conn, $gameId,false);

                    echo json_encode(['status' => 'success', 'message' => 'Status Updated successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update invitation.']);
                }
            }
            else
            {
                echo json_encode(['status' => 'error', 'message' => 'Player Not yet added']);
            }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}


?>