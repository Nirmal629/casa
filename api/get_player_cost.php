<?php 
include('dbConnection.php');

$eventId = $_POST['event_id'];

/* -----------------------------------
   1️⃣ Fetch event details
----------------------------------- */
$eventRes = mysqli_query($conn, "
    SELECT HOST_ID, EVENT_VENUE
    FROM ca_events
    WHERE ID = '$eventId'
    LIMIT 1
");

$event = mysqli_fetch_assoc($eventRes);

$hostId     = $event['HOST_ID'];
$eventVenue = trim($event['EVENT_VENUE']);

/* -----------------------------------
   2️⃣ Confirmed players + total cost
----------------------------------- */
$playerRes = mysqli_query($conn, "
    SELECT 
        COUNT(*) AS confirmed_players,
        COALESCE(SUM(PRICE),0) AS total_player_cost
    FROM ca_gamejoin
    WHERE GAME_ID = '$eventId'
      AND CONFIRMED = 'Y'
");

$playerData = mysqli_fetch_assoc($playerRes);

$confirmedPlayers = (int)$playerData['confirmed_players'];
$totalPlayerCost = (float)$playerData['total_player_cost'];

/* -----------------------------------
   3️⃣ Birdies used logic
----------------------------------- */
if ($eventVenue === 'Epic Badminton') {
    $birdiesUsed = $confirmedPlayers <=4 ? 4 :$confirmedPlayers  * 2;
} else {
    $birdiesUsed = $confirmedPlayers<=4?4:$confirmedPlayers;
}

/* -----------------------------------
   4️⃣ Court cost from venue
----------------------------------- */
$venueRes = mysqli_query($conn, "
    SELECT COST
    FROM ca_venue
    WHERE NAME = '".mysqli_real_escape_string($conn, $eventVenue)."'
      AND HOST_ID = '$hostId'
    LIMIT 1
");

$venueRow  = mysqli_fetch_assoc($venueRes);
$courtCost = isset($venueRow['COST']) ? (float)$venueRow['COST'] : 0;

/* -----------------------------------
   5️⃣ Response
----------------------------------- */

$birdieSql = "SELECT BRIDIE FROM ca_bride LIMIT 1";
$birdieRes = mysqli_query($conn, $birdieSql);
$birdieRow = mysqli_fetch_assoc($birdieRes);

$confRes = mysqli_query($conn, "
                            SELECT COUNT(*) AS total
                            FROM ca_gamejoin
                            WHERE GAME_ID = '$eventId'
                              AND CONFIRMED = 'Y'
                        ");
                        $confirmed_players = (int)mysqli_fetch_assoc($confRes)['total'];
                        
                        $confResj = mysqli_query($conn, "
                            SELECT COUNT(*) AS total
                            FROM ca_gamejoin
                            WHERE GAME_ID = '$eventId'
            
                        ");
                        $joined_players = (int)mysqli_fetch_assoc($confResj)['total'];
        
echo json_encode([
    'success'              => true,
    'confirmed_players'    => $confirmedPlayers,
    'birdies_used'         => (float)$birdieRow['BRIDIE'],
    'court_cost'           => $courtCost,
    'total_player_cost'    => $totalPlayerCost,
    'confirmed_players'    => $confirmed_players,
    'joined_players'       => $joined_players
]);

?>