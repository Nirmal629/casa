<?php
session_start();
include('dbConnection.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'html' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $host_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $venue = mysqli_real_escape_string($conn, trim($_POST['venue'] ?? ''));
    $type = mysqli_real_escape_string($conn, trim($_POST['type'] ?? ''));
    $amount = (float) ($_POST['amount'] ?? 0);
    $datetime = trim($_POST['expense_datetime'] ?? '');
    $year = (int) ($_POST['year'] ?? date('Y'));
    $month = (int) ($_POST['month'] ?? date('n'));

    if ($user_id <= 0 || $amount <= 0 || $datetime === '') {
        $response['message'] = 'Please fill all required fields';
        echo json_encode($response);
        exit;
    }

    // datetime-local format: YYYY-MM-DDTHH:MM  → split into date + time
    $expenseDate = date('Y-m-d', strtotime($datetime));
    $expenseTime = date('H:i:s', strtotime($datetime));

    $expMonth = (int) date('n', strtotime($expenseDate));
    $expYear = (int) date('Y', strtotime($expenseDate));

    if ($expYear != $year || $expMonth != $month) {
        $response['message'] = 'The expense date must belong to the selected ledger month.';
        echo json_encode($response);
        exit;
    }

    $isOverrideActive = (isset($_SESSION['ledger_override'][$user_id][$year][$month]) && $_SESSION['ledger_override'][$user_id][$year][$month] === true) || (isset($_POST['override']) && (int)$_POST['override'] === 1);

    if (!$isOverrideActive) {
        $nMonth = ($expMonth % 12) + 1;
        $nYear = ($expMonth == 12) ? ($expYear + 1) : $expYear;
        $isLocked = false;
        try {
            $lockRes = mysqli_query($conn, "SELECT 1 FROM host_player_carry_forward
                    WHERE host_id = $host_id AND player_id = $user_id
                      AND carry_month = $nMonth AND carry_year = $nYear
                    LIMIT 1");
            $isLocked = ($lockRes && mysqli_num_rows($lockRes) > 0);
        } catch (mysqli_sql_exception $e) {
            $isLocked = false;
        }
        if ($isLocked) {
            $response['message'] = 'This month has already been carried forward and is locked.';
            echo json_encode($response);
            exit;
        }
    }

    // Only the CURRENT month accepts new expenses. Previous months are
    // read-only (carry forward only) — enforce server-side as well.
    $nowYear  = (int) date('Y');
    $nowMonth = (int) date('n');
    if (!$isOverrideActive) {
        if ($expYear != $nowYear || $expMonth != $nowMonth) {
            $response['message'] = 'Expenses can only be added for the current month. Previous months are read-only (carry forward only).';
            echo json_encode($response);
            exit;
        }
    }

    if ($type === '') {
        $type = 'Expense';
    }

    $sql = "INSERT INTO ca_expense
            (USER_ID, HOST_ID, VENUE, TYPE, AMOUNT, CURRENCY, EXPENSE_DATE, EXPENSE_TIME, STATUS, CREATED_AT)
            VALUES
            ($user_id, $host_id, '$venue', '$type', $amount, 'CAD', '$expenseDate', '$expenseTime', 'Y', NOW())";

    if (mysqli_query($conn, $sql)) {
        $response['success'] = true;
        $response['message'] = 'Expense added successfully';

        // Re-render with the shared renderer so the new record appears immediately
        require_once __DIR__ . '/render_player_pay_html.php';
        $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month);
    } else {
        $response['message'] = 'Failed to add expense: ' . mysqli_error($conn);
    }
}

echo json_encode($response);
