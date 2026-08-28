<?php
session_start();
include('dbConnection.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'html' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $game_id = isset($_POST['game_id']) ? (int) $_POST['game_id'] : 0;
    $payment_id = isset($_POST['payment_id']) ? (int) $_POST['payment_id'] : 0;
    $year = (int) ($_POST['year'] ?? 0);
    $month = (int) ($_POST['month'] ?? 0);
    if ($year <= 0) {
        $year = (int) date('Y');
    }
    if ($month <= 0) {
        $month = (int) date('n');
    }

    // Determine host_id
    $host_id = 0;
    if (isset($_POST['host_id']) && (int)$_POST['host_id'] > 0) {
        $host_id = (int)$_POST['host_id'];
    } elseif ($game_id > 0) {
        $hostRes = $conn->query("SELECT HOST_ID FROM ca_events WHERE ID = $game_id LIMIT 1");
        if ($hostRes && $hostRow = $hostRes->fetch_assoc()) {
            $host_id = (int)$hostRow['HOST_ID'];
        }
    } elseif ($game_id < 0) {
        $hostRes = $conn->query("SELECT HOST_ID FROM ca_expense WHERE ID = " . abs($game_id) . " LIMIT 1");
        if ($hostRes && $hostRow = $hostRes->fetch_assoc()) {
            $host_id = (int)$hostRow['HOST_ID'];
        }
    }
    if ($host_id === 0 && isset($_SESSION['usertype']) && ($_SESSION['usertype'] === 'Host' || $_SESSION['usertype'] === 'Trainer')) {
        $host_id = (int)$_SESSION['user_id'];
    }

    $isPlayerSide = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Player');

    // Month locking: approve/reject/pay are blocked for a month that has
    // already been carried forward (a Carry Forward record exists for the next month).
    $uid = (int) $user_id;
    $nMonth = ($month % 12) + 1;
    $nYear = ($month == 12) ? ($year + 1) : $year;
    $lockRes = $conn->query("SELECT 1 FROM host_player_carry_forward
            WHERE host_id = $host_id AND player_id = $uid
              AND carry_month = $nMonth AND carry_year = $nYear
            LIMIT 1");
    if ($lockRes && $lockRes->num_rows > 0) {
        $response['success'] = false;
        $response['message'] = 'This month has already been carried forward and is locked.';
        require_once __DIR__ . '/render_player_pay_html.php';
        $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month, $isPlayerSide, $host_id);
        echo json_encode($response);
        exit;
    }

    // Only the CURRENT month accepts payment actions. Previous months are
    // read-only (carry forward only) — enforce server-side as well.
    $nowYear  = (int) date('Y');
    $nowMonth = (int) date('n');
    $isOverrideActive = (isset($_SESSION['ledger_override'][$uid][$year][$month]) && $_SESSION['ledger_override'][$uid][$year][$month] === true) || (isset($_POST['override']) && (int)$_POST['override'] === 1);
    if (!$isOverrideActive) {
        if ($year != $nowYear || $month != $nowMonth) {
            $response['success'] = false;
            $response['message'] = 'Payments can only be added for the current month. Previous months are read-only (carry forward only).';
            require_once __DIR__ . '/render_player_pay_html.php';
            $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month, $isPlayerSide, $host_id);
            echo json_encode($response);
            exit;
        }
    }

    // ✅ Step 1: Perform the action (approve/reject/pay)
    if ($action === 'approve' || $action === 'reject') {
        // Backend security check
        if ($isPlayerSide) {
            $response['success'] = false;
            $response['message'] = 'Unauthorized action.';
            echo json_encode($response);
            exit;
        }
    }

    if ($action === 'approve') {
        $reviewer = (int) $_SESSION['user_id'];
        if ($payment_id > 0) {
            $sql = "UPDATE ca_payment SET STATUS = 'Y', REVIEWED_BY = $reviewer, REVIEWED_AT = NOW()
                    WHERE ID = $payment_id AND STATUS = 'N'";
        } else {
            $sql = "UPDATE ca_payment SET STATUS = 'Y', REVIEWED_BY = $reviewer, REVIEWED_AT = NOW()
                    WHERE USER_ID = $user_id AND GAME_ID = $game_id AND STATUS = 'N'";
        }
        $conn->query($sql);
        $response['success'] = true;
        $response['message'] = 'Approved successfully';
    } elseif ($action === 'reject') {
        $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, trim($_POST['reason'])) : '';
        $reviewer = (int) $_SESSION['user_id'];
        if ($payment_id > 0) {
            $sql = "UPDATE ca_payment SET STATUS = 'R', REJECTION_REASON = '$reason', REVIEWED_BY = $reviewer, REVIEWED_AT = NOW()
                    WHERE ID = $payment_id AND STATUS = 'N'";
        } else {
            $sql = "UPDATE ca_payment SET STATUS = 'R', REJECTION_REASON = '$reason', REVIEWED_BY = $reviewer, REVIEWED_AT = NOW()
                    WHERE USER_ID = $user_id AND GAME_ID = $game_id AND STATUS = 'N'";
        }
        $conn->query($sql);
        $response['success'] = true;
        $response['message'] = 'Rejected successfully';
    } elseif ($action === 'reinitiate') {
        if ($payment_id > 0) {
            $sql = "UPDATE ca_payment SET STATUS = 'N', REJECTION_REASON = NULL, REVIEWED_BY = NULL, REVIEWED_AT = NULL
                    WHERE ID = $payment_id AND STATUS = 'R'";
            $conn->query($sql);
            $response['success'] = true;
            $response['message'] = 'Payment re-initiated successfully';
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid payment ID';
        }
    } elseif ($action === 'pay') {
        // Prevent duplicate pending payment requests for players
        if ($isPlayerSide) {
            $checkPending = mysqli_query($conn, "SELECT 1 FROM ca_payment WHERE USER_ID = $user_id AND GAME_ID = $game_id AND STATUS = 'N' LIMIT 1");
            if ($checkPending && mysqli_num_rows($checkPending) > 0) {
                $response['success'] = false;
                $response['message'] = 'A payment request is already pending approval.';
                require_once __DIR__ . '/render_player_pay_html.php';
                $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month, $isPlayerSide, $host_id);
                echo json_encode($response);
                exit;
            }
        }

        $amount = (float) $_POST['amount'];
        $type = mysqli_real_escape_string($conn, $_POST['payment_type'] ?? 'Interac');
        $details = isset($_POST['details']) ? mysqli_real_escape_string($conn, trim($_POST['details'])) : '';
        $datetime = isset($_POST['payment_datetime']) ? trim($_POST['payment_datetime']) : '';

        if ($datetime !== '') {
            $date = date('Y-m-d', strtotime($datetime));
            $time = date('H:i:s', strtotime($datetime));

            $payMonth = (int) date('n', strtotime($date));
            $payYear = (int) date('Y', strtotime($date));
            if ($payYear != $year || $payMonth != $month) {
                $response['success'] = false;
                $response['message'] = 'The payment date must belong to the selected ledger month.';
                require_once __DIR__ . '/render_player_pay_html.php';
                $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month, $isPlayerSide, $host_id);
                echo json_encode($response);
                exit;
            }
        } else {
            $date = date('Y-m-d');
            $time = date('H:i:s');
        }

        $status = $isPlayerSide ? 'N' : 'Y';
        $reviewed_by = $isPlayerSide ? 'NULL' : (int)$host_id;
        $reviewed_at = $isPlayerSide ? 'NULL' : 'NOW()';

        $sql = "INSERT INTO ca_payment 
                (USER_ID, GAME_ID, AMOUNT, PAYMENT_DATE, PAYMENT_TIME, PAYMENT_TYPE, DETAILS, STATUS, REVIEWED_BY, REVIEWED_AT, CREATED_AT)
                VALUES 
                ($user_id, $game_id, $amount, '$date', '$time', '$type', '$details', '$status', $reviewed_by, $reviewed_at, NOW())";
        $conn->query($sql);
        $response['success'] = true;
        $response['message'] = $isPlayerSide ? 'Payment request submitted for approval.' : 'Payment added';
    }

    // ✅ Step 2: Fetch the updated HTML (shared renderer — grouped layout)
    require_once __DIR__ . '/render_player_pay_html.php';
    $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month, $isPlayerSide, $host_id);
}

echo json_encode($response);
exit;
