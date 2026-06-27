<?php
include 'dbConnection.php';

// print_r($_POST);exit;

function validate_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$response = array();

// Validate and sanitize inputs
$id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : null;
$host_name = isset($_POST['host_name']) ? validate_input($_POST['host_name']) : null;
$event_country = isset($_POST['event_country']) ? validate_input($_POST['event_country']) : null;
$event_province = isset($_POST['event_province']) ? validate_input($_POST['event_province']) : null;
$event_city = isset($_POST['event_city']) ? validate_input($_POST['event_city']) : null;
$event_currency = isset($_POST['event_currency']) ? validate_input($_POST['event_currency']) : null;
$event_venue = isset($_POST['event_venue']) ? validate_input($_POST['event_venue']) : null;
$event_category = isset($_POST['event_category']) ? validate_input($_POST['event_category']) : null;
$gender_category = isset($_POST['gender_category']) ? validate_input($_POST['gender_category']) : null;
$gender_skill_level = isset($_POST['gender_skill_level']) ? validate_input($_POST['gender_skill_level']) : null;
$event_type = isset($_POST['event_type']) ? validate_input($_POST['event_type']) : null;
$event_date = isset($_POST['event_date']) ? validate_input($_POST['event_date']) : null;
$event_time = isset($_POST['event_time']) ? validate_input($_POST['event_time']) : null;
$to_time = isset($_POST['to_time']) ? validate_input($_POST['to_time']) : null;
$freeze_date = isset($_POST['freeze_date']) ? validate_input($_POST['freeze_date']) : null;
$freeze_time = isset($_POST['freeze_time']) ? validate_input($_POST['freeze_time']) : null;
$event_cost = isset($_POST['event_cost']) ? validate_input($_POST['event_cost']) : null;
$event_discount = isset($_POST['event_discount']) ? validate_input($_POST['event_discount']) : null;
$event_description = isset($_POST['event_description']) ? validate_input($_POST['event_description']) : null;
$event_message = isset($_POST['event_message']) ? validate_input($_POST['event_message']) : null;
$status = isset($_POST['status']) ? validate_input($_POST['status']) : null;
$facilitycost = isset($_POST['facilitycost']) ? validate_input($_POST['facilitycost']) : null;
$accessoriesCost = isset($_POST['accessoriesCost']) ? validate_input($_POST['accessoriesCost']) : null;
$snackscost = isset($_POST['snackscost']) ? validate_input($_POST['snackscost']) : null;
$eventtotalCostt = isset($_POST['eventtotalCostt']) ? validate_input($_POST['eventtotalCostt']) : null;
$eventtotalplayerCostt = isset($_POST['eventtotalplayerCostt']) ? validate_input($_POST['eventtotalplayerCostt']) : null;
$profitloss = isset($_POST['profitloss']) ? validate_input($_POST['profitloss']) : null;

// Check required fields
if (!$id || !$host_name || !$event_country || !$event_date || !$event_time || !$event_cost) {
    $response['success'] = false;
    $response['error'] = 'Missing required fields.';
    echo json_encode($response);
    exit;
}

// Ensure numeric fields are valid
if (!is_numeric($event_cost)) {
    $response['success'] = false;
    $response['error'] = 'Event cost and discount must be numeric.';
    echo json_encode($response);
    exit;
}

// Update query
$sql = "UPDATE ca_events 
        SET HOST_NAME = '$host_name',
            EVENT_COUNTRY = '$event_country',
            EVENT_PROVINCE = '$event_province',
            EVENT_CITY = '$event_city',
            EVENT_CURRENCY = '$event_currency',
            EVENT_VENUE = '$event_venue',
            EVENT_CATEGORY = '$event_category',
            GENDER_CATEGORY = '$gender_category',
            GENDER_SKILL_LEVEL = '$gender_skill_level',
            EVENT_TYPE = '$event_type',
            EVENT_DATE = '$event_date',
            EVENT_TIME = '$event_time',
            TO_TIME = '$to_time',
            CANCEL_DATE = '$freeze_date',
            CANCEL_TIME = '$freeze_time',
            EVENT_COST = '$event_cost',
            EVENT_DISCOUNT = '$event_discount',
            EVENT_DESCRIPTION = '$event_description',
            EVENT_MESSAGE = '$event_message',
            STATUS = '$status',
            FACILITY_COST='$facilitycost',
            ACCESSORIES_COST='$accessoriesCost',
            SNACKS_COST='$snackscost',
            TOTAL_EVENT_COST='$eventtotalCostt',
            TOTAL_PLAYER_COST='$eventtotalplayerCostt',
            PROFIT_LOSS='$profitloss',
            BIRDIE_USED = '".$_POST['birdieUsed']."',
            BIRDIE_COST = '$accessoriesCost',
            CLUB_COST = '".$_POST['clubClost']."'
        WHERE ID = '$id'";

if ($conn->query($sql) === TRUE) {
    $response['success'] = true;
    if(isset($_POST['updatePlayerPrice']) && $_POST['updatePlayerPrice'] == 'Y')
    {
        $updateJoin = "UPDATE ca_gamejoin 
                      SET PRICE = '$event_cost', CURRENCY = '$event_currency' 
                      WHERE GAME_ID = '$id'";
                           if ($conn->query($updateJoin) === TRUE) {
                    $response['success'] = true;
                    } else {
                        $response['success'] = false;
                        $response['error'] = 'Event updated but failed to update ca_gamejoin: ' . $conn->error;
                    }
    }

} else {
    $response['success'] = false;
    $response['error'] = 'Database error: ' . $conn->error;
}

echo json_encode($response);
?>
