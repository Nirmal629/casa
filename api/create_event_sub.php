<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
session_start();
include 'dbConnection.php';
include 'helpers/gameAutoConfirm.php';
function validate_input($data) {
    return htmlspecialchars(trim($data));
}

// Collect POST data (no validation, only basic sanitization)
$host_name = validate_input($_POST['host_name'] ?? '');
$event_country = validate_input($_POST['event_country'] ?? '');
$event_province = validate_input($_POST['event_province'] ?? '');
$event_city = validate_input($_POST['event_city'] ?? '');
$event_currency = validate_input($_POST['event_currency'] ?? '');
$event_venue = validate_input($_POST['event_venue'] ?? '');
$event_category = validate_input($_POST['event_category'] ?? '');
$gender_category = validate_input($_POST['gender_category'] ?? '');
$gender_skill_level = validate_input($_POST['gender_skill_level'] ?? '');
$event_type = validate_input($_POST['event_type'] ?? '');
$event_date = validate_input($_POST['event_date'] ?? '');
$event_time = validate_input($_POST['event_time'] ?? '');
$to_time = validate_input($_POST['to_time'] ?? '');
$freeze_date = validate_input($_POST['freeze_date'] ?? '');
$freeze_time = validate_input($_POST['freeze_time'] ?? '');
$event_cost = validate_input($_POST['event_cost'] ?? '');
$event_discount = validate_input($_POST['event_discount'] ?? '');
$event_description = validate_input($_POST['event_description'] ?? '');
$event_message = validate_input($_POST['event_message'] ?? '');
$status = validate_input($_POST['status'] ?? '');
$facilitycost = validate_input($_POST['facilitycost'] ?? '');
$accessoriesCost = validate_input($_POST['accessoriesCost'] ?? '');
$snackscost = validate_input($_POST['snackscost'] ?? '');
$eventtotalCostt = validate_input($_POST['eventtotalCostt'] ?? '');
$eventtotalplayerCostt = validate_input($_POST['eventtotalplayerCostt'] ?? '');
$profitloss = validate_input($_POST['profitloss'] ?? '');

// Insert query
$sql = "INSERT INTO ca_events (HOST_ID,HOST_NAME, EVENT_COUNTRY, EVENT_PROVINCE, EVENT_CITY, EVENT_CURRENCY,
    EVENT_VENUE, EVENT_CATEGORY, GENDER_CATEGORY, GENDER_SKILL_LEVEL,
    EVENT_TYPE, EVENT_DATE, EVENT_TIME, TO_TIME, CANCEL_DATE, CANCEL_TIME,
    EVENT_COST, EVENT_DISCOUNT, EVENT_DESCRIPTION, EVENT_MESSAGE, STATUS,
    FACILITY_COST, ACCESSORIES_COST, SNACKS_COST, TOTAL_EVENT_COST,
    TOTAL_PLAYER_COST, PROFIT_LOSS
) VALUES (
    '".$_SESSION['user_id']."','$host_name', '$event_country', '$event_province', '$event_city', '$event_currency',
    '$event_venue', '$event_category', '$gender_category', '$gender_skill_level',
    '$event_type', '$event_date', '$event_time', '$to_time', '$freeze_date', '$freeze_time',
    '$event_cost', '$event_discount', '$event_description', '$event_message', '$status',
    '$facilitycost', '$accessoriesCost', '$snackscost', '$eventtotalCostt',
    '$eventtotalplayerCostt', '$profitloss'
)";

$response = [];

if ($conn->query($sql) === TRUE) {
    $new_game_id = $conn->insert_id;
    $select_joineddata = mysqli_query($conn,"select * from ca_gamejoin_default where GAME_ID='".$_POST['id']."'");
    while ($fetch_joinedData = mysqli_fetch_assoc($select_joineddata)) {
    $user_id     = $fetch_joinedData['USER_ID'];
    $game_id     = $fetch_joinedData['GAME_ID'];
    $host_id     = $fetch_joinedData['HOST_ID'];
    $price       = $fetch_joinedData['PRICE'];
    $currency    = $fetch_joinedData['CURRENCY'];
    $type        = $fetch_joinedData['TYPE'];
    $join_status = $fetch_joinedData['STATUS'];
    // $confirmed   = $fetch_joinedData['CONFIRMED'];
    $confirmed   = 'N';
    $created_at  = $fetch_joinedData['CREATED_AT'];

    // Insert into ca_gamejoin
    mysqli_query($conn, "INSERT INTO ca_gamejoin (
        USER_ID, GAME_ID, HOST_ID, PRICE, CURRENCY, TYPE, STATUS, CONFIRMED, CREATED_AT
    ) VALUES (
        '$user_id', '".$new_game_id."', '$host_id', '$price', '$currency', '$type', '$join_status', '$confirmed', '$created_at'
    )");
    applyAutoConfirmAndMessage($conn, $new_game_id);
}

   if($event_category == 'Badminton Game')
        {
        
        $event_id = mysqli_insert_id($conn);
        $venueSql = "
            SELECT COST 
            FROM ca_venue 
            WHERE NAME = '$event_venue' AND HOST_ID='".$_SESSION['user_id']."'
            LIMIT 1
        ";
        $venueRes = mysqli_query($conn, $venueSql);
        $venueRow = mysqli_fetch_assoc($venueRes);
        
        $court_rent_per_hour = (float)$venueRow['COST'];
        
        $birdieSql = "SELECT BRIDIE FROM ca_bride LIMIT 1";
        $birdieRes = mysqli_query($conn, $birdieSql);
        $birdieRow = mysqli_fetch_assoc($birdieRes);
        
        $birdie_price = (float)$birdieRow['BRIDIE'];
        
        $start = new DateTime($event_time);
        $end   = new DateTime($to_time);
        
        $interval = $start->diff($end);
        $hours = $interval->h + ($interval->i / 60);
        
        // safety
        if ($hours <= 0) {
            $hours = 1;
        }
        
        $players = 4;
        if($event_venue == 'Epic Badminton')
        {
        $birdies_used = $players * 2;
        }
        else
        {
            $birdies_used = $players;
        }
        
        // Court cost
        $facility_cost = ceil($court_rent_per_hour * $hours);
        
        // Birdie cost
        $accessories_cost = ceil($birdie_price * $birdies_used);
        
        //club cost
        $club_cost = 1 * 4;
        
        
        // Total event cost
        $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
        
        // Per player cost
        $per_player_cost = ceil($total_event_cost / $players);
        
        $updateSql = "
        UPDATE ca_events SET
            EVENT_COST = '$per_player_cost',
            FACILITY_COST = '$facility_cost',
            ACCESSORIES_COST = '$accessories_cost',
            TOTAL_EVENT_COST = '$total_event_cost',
            CLUB_COST='$club_cost',
            BIRDIE_USED='$birdies_used',
            BIRDIE_PRICE='$birdie_price',
            BIRDIE_COST='$accessories_cost',
            JOIN_MESSAGE='0 court confirmed 6 spots available for first court'
        WHERE ID = '$event_id'
    ";
    
    mysqli_query($conn, $updateSql);
}


    
    $response['success'] = true;
    $response['insert_id'] = $conn->insert_id;
} else {
    $response['success'] = false;
    $response['error'] = 'Database error: ' . $conn->error;
}

echo json_encode($response);
?>
