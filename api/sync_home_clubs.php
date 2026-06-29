<?php
// api/sync_home_clubs.php
// Helper functions for automatically syncing players with Home Clubs (join_type = 'H') based on location and game type.

/**
 * Syncs a single player to all matching Home Clubs.
 * Used when a player registers or logs in.
 */
function syncPlayerToHomeClubs($conn, $player_id, $country, $province, $city, $games) {
    if (empty($country) || empty($city)) return;

    // Convert games string (e.g. "Badminton, Tennis") to an array for matching
    $player_games = array_map('trim', explode(',', strtolower($games)));

    // Find all active Home Clubs matching the location
    $stmt = $conn->prepare("SELECT id, host_id, game_type FROM ca_clubs WHERE join_type = 'H' AND status = 'Active' AND LOWER(TRIM(country)) = LOWER(TRIM(?)) AND LOWER(TRIM(province)) = LOWER(TRIM(?)) AND LOWER(TRIM(city)) = LOWER(TRIM(?))");
    $stmt->bind_param("sss", $country, $province, $city);
    $stmt->execute();
    $result = $stmt->get_result();

    $insert_stmt = $conn->prepare("INSERT IGNORE INTO ca_player_club_status (club_id, host_id, player_id, status) VALUES (?, ?, ?, 'accepted')");

    while ($club = $result->fetch_assoc()) {
        // Check game type match using a more forgiving substring search
        $club_game = trim($club['game_type']);
        if (!empty($club_game) && stripos($games, $club_game) !== false) {
            // Match found, auto-join player to this club purely based on location
            $insert_stmt->bind_param("iii", $club['id'], $club['host_id'], $player_id);
            $insert_stmt->execute();
        }
    }
}

/**
 * Syncs a single Home Club to all matching existing players.
 * Used when a host creates or updates a club to be a Home Club.
 */
function syncHomeClubToPlayers($conn, $club_id, $host_id, $country, $province, $city, $game_type) {
    if (empty($country) || empty($city)) return;

    $club_game = strtolower(trim($game_type));

    // Find all players matching the location
    $stmt = $conn->prepare("SELECT ID, GAMES FROM ca_users WHERE USERTYPE = 'Player' AND LOWER(TRIM(COUNTRY)) = LOWER(TRIM(?)) AND LOWER(TRIM(PROVINCE)) = LOWER(TRIM(?)) AND LOWER(TRIM(CITY)) = LOWER(TRIM(?))");
    $stmt->bind_param("sss", $country, $province, $city);
    $stmt->execute();
    $result = $stmt->get_result();

    $insert_stmt = $conn->prepare("INSERT IGNORE INTO ca_player_club_status (club_id, host_id, player_id, status) VALUES (?, ?, ?, 'accepted')");

    while ($player = $result->fetch_assoc()) {
        $player_games_str = $player['GAMES'] ?? '';
        if (!empty($club_game) && stripos($player_games_str, $club_game) !== false) {
            // Match found, auto-join player to this club purely based on location
            $insert_stmt->bind_param("iii", $club_id, $host_id, $player['ID']);
            $insert_stmt->execute();
        }
    }
}
?>
