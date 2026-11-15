<?php
declare(strict_types=1);

require_once __DIR__ . '/_security.php';
$userId = requireApiUser(['Player', 'Host', 'Trainer']);
requireApiPost();

$rating   = isset($_POST['rating']) ? (int) $_POST['rating'] : 5;
$message  = trim((string) ($_POST['message'] ?? ''));
$reviewId = !empty($_POST['review_id']) ? (int) $_POST['review_id'] : null;
$userType = (string) ($_SESSION['usertype'] ?? 'Player');

$pdo = \App\Config\Database::getPdo();
$reviewModel = new \App\Models\ReviewModel($pdo);
$result = $reviewModel->saveReview($userId, $userType, $rating, $message, $reviewId);

$status = ($result['status'] === 'success') ? 200 : 400;
http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
exit;
