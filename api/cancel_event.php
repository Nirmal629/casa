<?php
session_start();
    include('dbConnection.php'); // Ensure DB connection is included
    include 'helpers/gameAutoConfirm.php';


    $userId = $_POST['USER_ID'];
    $gameId = $_POST['ID'];
    $hostId = $_POST['HOST_ID'];
    $cost = $_POST['EVENT_COST'];
    $currency = $_POST['EVENT_CURRENCY'];
    $createdAt = date('Y-m-d H:i:s'); // Current timestamp

    // Fetch event details to check CANCEL_DATE and CANCEL_TIME
    $query_event = "SELECT CANCEL_DATE, CANCEL_TIME FROM ca_events WHERE ID = '$gameId'";
    $result_event = mysqli_query($conn, $query_event);
    $eventData = mysqli_fetch_assoc($result_event);

    if (!$eventData) {
        echo json_encode(['status' => 'error', 'message' => 'Event not found.']);
        exit;
    }

    // Check if CANCEL_DATE or CANCEL_TIME is missing
    if (empty($eventData['CANCEL_DATE']) || empty($eventData['CANCEL_TIME'])) {
        echo json_encode(['status' => 'error', 'message' => 'Event cancellation date or time is missing.']);
        exit;
    }

    // Get current date & time
    $currentDateTime = date('Y-m-d H:i:s');
    $cancelDateTime = $eventData['CANCEL_DATE'] . ' ' . $eventData['CANCEL_TIME'];
    
    // Check if any payment exists in `ca_payment` table for this user & game
    $query_payment = "SELECT COUNT(*) AS payment_count FROM ca_payment WHERE USER_ID = '$userId' AND GAME_ID = '$gameId' AND STATUS != 'R'";
    $result_payment = mysqli_query($conn, $query_payment);
    $paymentData = mysqli_fetch_assoc($result_payment);

    if ($paymentData['payment_count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Event cannot be canceled as payment has been made.']);
        exit;
    }

    // If current date-time is within CANCEL_DATE & CANCEL_TIME, delete from ca_gamejoin
    if ($currentDateTime <= $cancelDateTime) {
        $deleteQuery = "DELETE FROM ca_gamejoin WHERE USER_ID = '$userId' AND GAME_ID = '$gameId'";
        $deleteResult = mysqli_query($conn, $deleteQuery);

        if ($deleteResult) {
                applyAutoConfirmAndMessage($conn, $gameId);

            echo json_encode(['status' => 'error', 'message' => 'Event canceled within allowed time, removed from game join list.']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to cancel event .']);
            exit;
        }
    }
    else
    {
        echo json_encode(['status' => 'error', 'message' => 'Time exceeds. Failed to cancel event.']);
            exit;
    }
?>
