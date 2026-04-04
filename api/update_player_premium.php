<?php
session_start();
date_default_timezone_set('America/Toronto');
include('dbConnection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $isPremium = (isset($_POST['is_premium']) && $_POST['is_premium'] === 'Y') ? 'Y' : 'N';

    if ($userId > 0) {
        // IMPORTANT: ensure you have column IS_PREMIUM in ca_users (CHAR(1) or VARCHAR(1))
        $stmt = $conn->prepare("UPDATE ca_users SET PREMIUM = ? WHERE ID = ?");
        if (!$stmt) {
            http_response_code(500);
            echo 'Prepare failed';
            exit;
        }

        $stmt->bind_param("si", $isPremium, $userId);

        if ($stmt->execute()) {
            echo 'OK';
        } else {
            http_response_code(500);
            echo 'Update failed';
        }

        $stmt->close();
    } else {
        http_response_code(400);
        echo 'Invalid user';
    }
} else {
    http_response_code(405);
    echo 'Method not allowed';
}

$conn->close();
