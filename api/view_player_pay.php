<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $year = $_POST['year'];
    $month = $_POST['month'];

    $is_player_side = isset($_POST['is_player_side']) && $_POST['is_player_side'] == 1;
    $host_id = isset($_POST['host_id']) ? (int)$_POST['host_id'] : 0;

    // Shared renderer — grouped Event Cost / Payments / Balance layout
    require_once __DIR__ . '/render_player_pay_html.php';
    echo renderPlayerPayHtml($conn, $user_id, $year, $month, $is_player_side, $host_id);

    mysqli_close($conn);
}
?>
