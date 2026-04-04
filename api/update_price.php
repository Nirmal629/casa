<?php
session_start();
include('dbConnection.php');

    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $game_id = mysqli_real_escape_string($conn, $_POST['game_id']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    
    // Check if the record exists
    $checkQuery = "SELECT COUNT(*) AS count FROM ca_gamejoin WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id'";
    $result = mysqli_query($conn, $checkQuery);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        // Update existing record
        $query = "UPDATE ca_gamejoin SET PRICE = '$price' WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id'";
        
        if (mysqli_query($conn, $query)) {
            echo "success";
        } else {
            echo "error updating record";
        }
    } else {
        // Return an error if the record does not exist
        echo "error: record not found";
    }

?>