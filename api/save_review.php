<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../dbConnection_PDO.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to submit a review.']);
    exit;
}

$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
if ($rating < 1 || $rating > 5) {
    $rating = 5;
}

$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Please write a review message.']);
    exit;
}

// Strip malicious scripts but keep safe formatting tags
$allowed_tags = '<b><strong><i><em><u><s><strike><p><h1><h2><h3><ul><ol><li><a><hr><br>';
$clean_message = strip_tags($message, $allowed_tags);

$userType = $_SESSION['usertype'] ?? 'Player';

try {
    $stmt = $pdo->prepare("INSERT INTO ca_reviews (USER_ID, PLAYER_ROLE, RATING, MESSAGE, STATUS, DATE_CREATED) VALUES (?, ?, ?, ?, 'Active', NOW())");
    $stmt->execute([$userId, $userType, $rating, $clean_message]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you! Your review has been submitted successfully.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
