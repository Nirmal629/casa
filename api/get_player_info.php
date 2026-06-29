<?php
include('dbConnection.php');
session_start();
header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? 0;
$ratedPlayerId = $_POST['rated_player_id'] ?? 0;

$response = ['user' => null, 'rating' => null];

// From ca_users
$userQuery = mysqli_query($conn, "SELECT VERIFIED_LEVEL, CURRENT_RANKING FROM ca_users WHERE ID = $ratedPlayerId");
if ($user = mysqli_fetch_assoc($userQuery)) {
    $response['user'] = $user;
}

// From ca_player_ratings (if already rated)
$ratingQuery = mysqli_query($conn, "SELECT SKILL_LEVEL, RANKING FROM ca_player_ratings WHERE RATER_ID = $currentUserId AND RATED_PLAYER_ID = $ratedPlayerId");
if ($rating = mysqli_fetch_assoc($ratingQuery)) {
    $response['rating'] = $rating;
}

echo json_encode($response);
exit;
