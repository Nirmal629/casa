<?php
session_start(); // Ensure session is started
include('dbConnection.php');
$response = array();
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
    $to_time = $conn->real_escape_string($_POST['to_time']);
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
        $response['success'] = true;
    } else {
        $response['success'] = false;
    $response['error'] = 'Database error: ' . $conn->error;
    }

echo json_encode($response);

?>
