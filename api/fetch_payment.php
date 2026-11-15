<?php
require_once __DIR__ . '/_security.php';
requireApiUser([]);

include('dbConnection.php');
$requestedUser = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$requestedGame = filter_var($_POST['game_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$requestedUser || !$requestedGame) apiSecurityDeny(400, 'Invalid request.');
if ((int) $requestedUser !== (int) $_SESSION['user_id']) requireOwnedEvent($conn, $requestedGame);
$_POST['user_id'] = (int) $requestedUser;
$_POST['game_id'] = (int) $requestedGame;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];

    // Fetch total amount paid by the user for this game
    $query = "SELECT SUM(AMOUNT) AS total_amount FROM `ca_payment` WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id' AND STATUS !='R'";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    $total_amount = $data['total_amount'] ?? 0; // Default to 0 if no payments found

    // Fetch game fee from `ca_gamejoin` table
    $query2 = "SELECT PRICE, CURRENCY FROM `ca_gamejoin` WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id' AND STATUS='Y'";
    $result2 = mysqli_query($conn, $query2);
    $data2 = mysqli_fetch_assoc($result2);
    $game_fee = $data2['PRICE'] ?? 0;

    // Calculate remaining due amount
    $due_amount = max($game_fee - $total_amount, 0);

    $rawCurr = trim((string)($data2['CURRENCY'] ?? ''));
    $currencySymbol = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;

    echo json_encode([
        "success" => true,
        "total_amount" => number_format((float)$game_fee, 2),
        "due" => number_format((float)$due_amount, 2),
        "currency" => $currencySymbol,
    ]);

    mysqli_close($conn);
}
?>
