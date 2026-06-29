<?php
session_start(); // Ensure session is started
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('dbConnection.php');

    header('Content-Type: application/json'); // Return JSON response

    // // Database connection
    // $conn = new mysqli("localhost", "casa_db", "casa_sports", "C@sa_sports24#");

    // if ($conn->connect_error) {
    //     echo json_encode(["success" => false, "message" => "Database connection failed."]);
    //     exit();
    // }

    // Sanitize inputs
    $host_name = $conn->real_escape_string($_POST['host_name']);
    $event_country = $conn->real_escape_string($_POST['event_country']);
    $event_province = $conn->real_escape_string($_POST['event_province']);
    $event_city = $conn->real_escape_string($_POST['event_city']);
    $event_currency = $conn->real_escape_string($_POST['event_currency']);
    $event_venue = $conn->real_escape_string($_POST['event_venue']);
    $event_category = $conn->real_escape_string($_POST['event_category']);
    $gender_category = $conn->real_escape_string($_POST['gender_category']);
    $gender_skill_level = $conn->real_escape_string($_POST['gender_skill_level']);
    $event_type = $conn->real_escape_string($_POST['event_type']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_time = $conn->real_escape_string($_POST['event_time']);
    $to_time = $conn->real_escape_string($_POST['to_Time']);
    $freeze_date = $conn->real_escape_string($_POST['freeze_date']);
    $freeze_time = $conn->real_escape_string($_POST['freeze_time']);
    $event_cost = $conn->real_escape_string($_POST['event_cost']);
    $event_discount = $conn->real_escape_string($_POST['event_discount']);
    $event_description = $conn->real_escape_string($_POST['event_description']);
    $event_message = $conn->real_escape_string($_POST['event_message']);

    // SQL Query
    $sql = "INSERT INTO ca_events (HOST_ID, HOST_NAME, EVENT_COUNTRY, EVENT_PROVINCE, EVENT_CITY, EVENT_CURRENCY, EVENT_VENUE, EVENT_CATEGORY, GENDER_CATEGORY, GENDER_SKILL_LEVEL, EVENT_TYPE, EVENT_DATE, EVENT_TIME, TO_TIME,CANCEL_DATE, CANCEL_TIME, EVENT_COST, EVENT_DISCOUNT, EVENT_DESCRIPTION, EVENT_MESSAGE)
            VALUES ('".$_SESSION['user_id']."', '$host_name', '$event_country', '$event_province', '$event_city', '$event_currency', '$event_venue', '$event_category', '$gender_category', '$gender_skill_level', '$event_type', '$event_date', '$event_time', '$to_time','$freeze_date', '$freeze_time', '$event_cost', '$event_discount', '$event_description', '$event_message')";

    if (mysqli_query($conn,$sql)) {
        
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
            COURTS_CONFIRMED=0,
            JOIN_MESSAGE='0 court confirmed 6 spots available for first court'
        WHERE ID = '$event_id'
    ";
    
    mysqli_query($conn, $updateSql);
}




        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $conn->error]);
    }

    $conn->close();

?>
