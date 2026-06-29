<?php
include('../dbConnection.php');

$data = json_decode(file_get_contents("php://input"), true);

$eventId   = (int)$data['event_id'];
$eventTime = $data['event_time'];
$toTime    = $data['to_time'];

/* -----------------------------------
   1️⃣ Fetch event details
----------------------------------- */
$eventRes = mysqli_query($conn, "
    SELECT EVENT_CATEGORY, EVENT_VENUE, HOST_ID
    FROM ca_events
    WHERE ID = '$eventId'
    LIMIT 1
");

if (!$eventRes || mysqli_num_rows($eventRes) === 0) {
    echo json_encode(['success' => false]);
    exit;
}

$event = mysqli_fetch_assoc($eventRes);

if (trim($event['EVENT_CATEGORY']) !== 'Badminton Game') {
    echo json_encode(['success' => false]);
    exit;
}

$eventVenue = $event['EVENT_VENUE'];
$hostId     = $event['HOST_ID'];

/* -----------------------------------
   2️⃣ Calculate hours
----------------------------------- */
$start = new DateTime($eventTime);
$end   = new DateTime($toTime);
$diff  = $start->diff($end);

$hours = $diff->h + ($diff->i / 60);
if ($hours <= 0) $hours = 1;

/* -----------------------------------
   3️⃣ Court cost
----------------------------------- */
$venueRes = mysqli_query($conn, "
    SELECT COST
    FROM ca_venue
    WHERE NAME = '".mysqli_real_escape_string($conn, $eventVenue)."'
      AND HOST_ID = '$hostId'
    LIMIT 1
");

$venueRow = mysqli_fetch_assoc($venueRes);
$courtCostPerHour = (float)$venueRow['COST'];
$facilityCost = $courtCostPerHour * $hours;

/* -----------------------------------
   4️⃣ Confirmed players
----------------------------------- */
$playerRes = mysqli_query($conn, "
    SELECT COUNT(*) AS confirmed
    FROM ca_gamejoin
    WHERE GAME_ID = '$eventId'
      AND CONFIRMED = 'Y'
");

$confirmedPlayers = (int)mysqli_fetch_assoc($playerRes)['confirmed'];

/* -----------------------------------
   5️⃣ Birdie price
----------------------------------- */
$birdieRes = mysqli_query($conn, "SELECT BRIDIE FROM ca_bride LIMIT 1");
$birdiePrice = (float)mysqli_fetch_assoc($birdieRes)['BRIDIE'];

/* -----------------------------------
   6️⃣ COST CALCULATION
----------------------------------- */
if ($confirmedPlayers <= 4) {

    // BASE 4 PLAYER LOGIC (DO NOT CHANGE)
    $players = 4;

    $birdiesUsed = ($eventVenue === 'Epic Badminton')
        ? $players * 2
        : $players;

    $accessoriesCost = $birdiePrice * $birdiesUsed;
    $clubCost = 4;

    $totalEventCost = $facilityCost + $accessoriesCost + $clubCost;
    $eventCost = ceil($totalEventCost / 4);
    
    mysqli_query($conn, "
        UPDATE ca_gamejoin
        SET PRICE = '$eventCost'
        WHERE GAME_ID = '$eventId'
          AND CONFIRMED = 'Y'
    ");

    // Keep player prices unchanged here

} else {

    // DYNAMIC LOGIC
    $birdiesUsed = ($eventVenue === 'Epic Badminton')
        ? $confirmedPlayers * 2
        : $confirmedPlayers;

    $accessoriesCost = $birdiePrice * $birdiesUsed;
    $clubCost = $confirmedPlayers;

    $totalEventCost = $facilityCost + $accessoriesCost + $clubCost;
    $eventCost = ceil($totalEventCost / $confirmedPlayers);

    mysqli_query($conn, "
        UPDATE ca_gamejoin
        SET PRICE = '$eventCost'
        WHERE GAME_ID = '$eventId'
          AND CONFIRMED = 'Y'
    ");
}

/* -----------------------------------
   7️⃣ Update event
----------------------------------- */
mysqli_query($conn, "
    UPDATE ca_events SET
        EVENT_TIME = '$eventTime',
        TO_TIME = '$toTime',
        FACILITY_COST = '$facilityCost',
        TOTAL_EVENT_COST = '$totalEventCost',
        EVENT_COST = '$eventCost'
    WHERE ID = '$eventId'
");

/* -----------------------------------
   8️⃣ Total player cost + P/L
----------------------------------- */
$sumRes = mysqli_query($conn, "
    SELECT SUM(PRICE) AS total
    FROM ca_gamejoin
    WHERE GAME_ID = '$eventId'
      AND CONFIRMED = 'Y'
");

$totalPlayerCost = (float)mysqli_fetch_assoc($sumRes)['total'];
$profitLoss = ceil($totalPlayerCost - $totalEventCost);

mysqli_query($conn, "
    UPDATE ca_events SET
        TOTAL_PLAYER_COST = '$totalPlayerCost',
        PROFIT_LOSS = '$profitLoss'
    WHERE ID = '$eventId'
");

/* -----------------------------------
   9️⃣ Response
----------------------------------- */
echo json_encode([
    'success' => true,
    'hours' => $hours,
    'total_event_cost' => ceil($totalEventCost),
    'event_cost' => $eventCost,
    'total_player_cost' => ceil($totalPlayerCost),
    'profit_loss' => ceil($profitLoss)
]);
exit;
