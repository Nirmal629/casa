<?php
session_start();
include('dbConnection.php');
header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratedPlayerId = $_POST['rated_player_id'] ?? 0;
    $skillLevel = mysqli_real_escape_string($conn, $_POST['skill_level'] ?? '');
    $newRank = (int) ($_POST['ranking'] ?? 0);

    if (!$ratedPlayerId || !$skillLevel || !$newRank) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Get previous rank of rated player
        $prevRankRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT CURRENT_RANKING FROM ca_users WHERE ID = $ratedPlayerId"));
        $oldRank = (int) ($prevRankRow['CURRENT_RANKING'] ?? 0);

        // Determine if ranking needs adjustment
        if ($oldRank > 0 && $newRank != $oldRank) {
            if ($newRank < $oldRank) {
                // Promote: shift everyone between newRank and oldRank - 1 down by 1
                $shiftQuery = "
                    UPDATE ca_users 
                    SET CURRENT_RANKING = CURRENT_RANKING + 1 
                    WHERE VERIFIED_LEVEL = '$skillLevel' 
                      AND CURRENT_RANKING >= $newRank 
                      AND CURRENT_RANKING < $oldRank 
                      AND ID != $ratedPlayerId
                ";
            } else {
                // Demote: shift everyone between oldRank + 1 and newRank up by 1
                $shiftQuery = "
                    UPDATE ca_users 
                    SET CURRENT_RANKING = CURRENT_RANKING - 1 
                    WHERE VERIFIED_LEVEL = '$skillLevel' 
                      AND CURRENT_RANKING <= $newRank 
                      AND CURRENT_RANKING > $oldRank 
                      AND ID != $ratedPlayerId
                ";
            }

            if (!mysqli_query($conn, $shiftQuery)) {
                throw new Exception("Failed to shift rankings.");
            }
        } else {
            // If user had no previous rank (new entry), shift all from newRank downward
            $shiftQuery = "
                UPDATE ca_users 
                SET CURRENT_RANKING = CURRENT_RANKING + 1 
                WHERE VERIFIED_LEVEL = '$skillLevel' 
                  AND CURRENT_RANKING >= $newRank 
                  AND ID != $ratedPlayerId
            ";
            if (!mysqli_query($conn, $shiftQuery)) {
                throw new Exception("Failed to shift rankings.");
            }
        }

        // Update the rated user's VERIFIED_LEVEL and CURRENT_RANKING
        $updateRated = "
            UPDATE ca_users 
            SET VERIFIED_LEVEL = '$skillLevel', CURRENT_RANKING = $newRank 
            WHERE ID = $ratedPlayerId
        ";
        if (!mysqli_query($conn, $updateRated)) {
            throw new Exception("Failed to update rated user.");
        }

        // Save to ca_player_ratings (REPLACE or INSERT/UPDATE as needed)
        $ratingQuery = "
            REPLACE INTO ca_player_ratings 
            (RATER_ID, RATED_PLAYER_ID, SKILL_LEVEL, RANKING) 
            VALUES 
            ($currentUserId, $ratedPlayerId, '$skillLevel', $newRank)
        ";
        if (!mysqli_query($conn, $ratingQuery)) {
            throw new Exception("Failed to save rating.");
        }

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Rating updated successfully and rankings adjusted.']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}


// <?php
// session_start();
// include('dbConnection.php');

// header('Content-Type: application/json');
// $currentUserId = $_SESSION['user_id'] ?? 0;

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $ratedPlayerId = $_POST['rated_player_id'] ?? 0;
//     $skillLevel = mysqli_real_escape_string($conn, $_POST['skill_level'] ?? '');
//     $ranking = (int) ($_POST['ranking'] ?? 0);

//     if (!$ratedPlayerId || !$skillLevel || !$ranking) {
//         echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
//         exit;
//     }

//     // Start transaction
//     mysqli_begin_transaction($conn);

//     try {
//         // 1. Save or update player rating
//         $query = "REPLACE INTO ca_player_ratings (RATER_ID, RATED_PLAYER_ID, SKILL_LEVEL, RANKING) 
//                   VALUES ($currentUserId, $ratedPlayerId, '$skillLevel', $ranking)";
//         if (!mysqli_query($conn, $query)) {
//             throw new Exception('Failed to save rating.');
//         }

//         // 2. Check if someone else already has this ranking in that level
//         $conflictResult = mysqli_query($conn, "
//             SELECT ID, CURRENT_RANKING 
//             FROM ca_users 
//             WHERE VERIFIED_LEVEL = '$skillLevel' 
//             AND CURRENT_RANKING = $ranking 
//             AND ID != $ratedPlayerId 
//             LIMIT 1
//         ");
//         $conflictUser = mysqli_fetch_assoc($conflictResult);

//         // 3. Get previous rank of rated player
//         $prevRankResult = mysqli_query($conn, "SELECT CURRENT_RANKING FROM ca_users WHERE ID = $ratedPlayerId");
//         $prevRankRow = mysqli_fetch_assoc($prevRankResult);
//         $previousRank = (int) ($prevRankRow['CURRENT_RANKING'] ?? 0);

//         // 4. Update rated player's VERIFIED_LEVEL and CURRENT_RANKING
//         $updateRated = "UPDATE ca_users SET VERIFIED_LEVEL='$skillLevel', CURRENT_RANKING=$ranking WHERE ID=$ratedPlayerId";
//         if (!mysqli_query($conn, $updateRated)) {
//             throw new Exception('Failed to update rated user.');
//         }

//         // 5. If someone else had this ranking, swap their rank
//         if ($conflictUser) {
//             $conflictUserId = $conflictUser['ID'];

//             // Update conflicting user's rank to rated player's previous rank
//             $newRankForConflict = ($previousRank > 0) ? $previousRank : 'NULL';
//             $updateConflict = "UPDATE ca_users SET CURRENT_RANKING=$newRankForConflict WHERE ID=$conflictUserId";

//             if (!mysqli_query($conn, $updateConflict)) {
//                 throw new Exception('Failed to update conflicting user.');
//             }
//         }

//         mysqli_commit($conn);
//         echo json_encode(['success' => true, 'message' => 'Rating updated successfully and rankings adjusted.']);
//     } catch (Exception $e) {
//         mysqli_rollback($conn);
//         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
//     }
// } else {
//     echo json_encode(['success' => false, 'message' => 'Invalid request.']);
// }
