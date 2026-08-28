<?php
session_start();
header('Content-Type: application/json');

$user_id = (int)($_POST['user_id'] ?? 0);
$year = (int)($_POST['year'] ?? 0);
$month = (int)($_POST['month'] ?? 0);
$state = ($_POST['state'] ?? 'off') === 'on';

if ($user_id > 0 && $year > 0 && $month > 0) {
    if ($state) {
        $_SESSION['ledger_override'][$user_id][$year][$month] = true;
    } else {
        unset($_SESSION['ledger_override'][$user_id][$year][$month]);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
