<?php
include('dbConnection.php');
header('Content-Type: application/json');

$playerId = $_POST['rated_player_id'] ?? 0;

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT VERIFIED_LEVEL, CURRENT_RANKING FROM ca_users WHERE ID=$playerId"));
$rating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SKILL_LEVEL, RANKING FROM ca_player_ratings WHERE RATED_PLAYER_ID=$playerId"));

$ratingsQuery = mysqli_query($conn, "
    SELECT u.NAME as rater, r.SKILL_LEVEL as level, r.RANKING as ranking 
    FROM ca_player_ratings r 
    JOIN ca_users u ON u.ID = r.RATER_ID 
    WHERE r.RATED_PLAYER_ID = $playerId
");

$ratings = [];
while ($row = mysqli_fetch_assoc($ratingsQuery)) {
    $ratings[] = $row;
}

echo json_encode([
    'user' => $user,
    'rating' => $rating,
    'ratings' => $ratings
]);
