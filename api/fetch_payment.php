<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];

    // Fetch total amount paid by the user for this game
    $query = "SELECT SUM(AMOUNT) AS total_amount FROM `ca_payment` WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id' AND STATUS !='R'";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    $total_amount = $data['total_amount'] ?? 0; // Default to 0 if no payments found

    // echo "SELECT PRICE, CURRENCY FROM `ca_gamejoin` WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id' AND STATUS='Y'";
    // Fetch game fee from `ca_gamejoin` table
    $query2 = "SELECT PRICE, CURRENCY FROM `ca_gamejoin` WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id' AND STATUS='Y'";
    $result2 = mysqli_query($conn, $query2);
    $data2 = mysqli_fetch_assoc($result2);
    $game_fee = $data2['PRICE'] ?? 0;

    // Calculate remaining due amount
    $due_amount = max($game_fee - $total_amount, 0);

    echo json_encode([
        "success" => true,
        "total_amount" => $game_fee,
        "due" => number_format($due_amount, 2),
        "currency" => $data2['CURRENCY'] ?? '',
    ]);

    mysqli_close($conn);
}
?>
