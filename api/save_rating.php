<?php
// File: save_rating.php

session_start();
include('dbConnection.php');
header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratedPlayerId = $_POST['rated_player_id'] ?? 0;
    $skillLevel = mysqli_real_escape_string($conn, $_POST['skill_level'] ?? '');
    $ranking = (int) ($_POST['ranking'] ?? 0);

    if (!$ratedPlayerId || !$skillLevel || !$ranking) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Check if already rated
    $check = mysqli_query($conn, "SELECT ID FROM ca_player_ratings WHERE RATER_ID=$currentUserId AND RATED_PLAYER_ID=$ratedPlayerId");

    if (mysqli_num_rows($check)) {
        // Update
        $query = "UPDATE ca_player_ratings SET SKILL_LEVEL='$skillLevel', RANKING=$ranking, RATING_DATE=NOW() WHERE RATER_ID=$currentUserId AND RATED_PLAYER_ID=$ratedPlayerId";
    } else {
        // Insert
        $query = "INSERT INTO ca_player_ratings (RATER_ID, RATED_PLAYER_ID, SKILL_LEVEL, RANKING) VALUES ($currentUserId, $ratedPlayerId, '$skillLevel', $ranking)";
    }

    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Rating saved.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}