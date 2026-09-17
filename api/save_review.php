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

$reviewId = !empty($_POST['review_id']) ? intval($_POST['review_id']) : null;

try {
    if ($reviewId) {
        // Verify that review belongs to current user
        $checkStmt = $pdo->prepare("SELECT ID FROM ca_reviews WHERE ID = ? AND USER_ID = ?");
        $checkStmt->execute([$reviewId, $userId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo json_encode(['status' => 'error', 'message' => 'Review not found or unauthorized.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE ca_reviews SET RATING = ?, MESSAGE = ? WHERE ID = ? AND USER_ID = ?");
        $stmt->execute([$rating, $clean_message, $reviewId, $userId]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Your review has been updated successfully.',
            'action' => 'update',
            'review_id' => $reviewId
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ca_reviews (USER_ID, PLAYER_ROLE, RATING, MESSAGE, STATUS, DATE_CREATED) VALUES (?, ?, ?, ?, 'Active', NOW())");
        $stmt->execute([$userId, $userType, $rating, $clean_message]);
        $newId = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => 'Thank you! Your review has been submitted successfully.',
            'action' => 'insert',
            'review_id' => $newId
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
