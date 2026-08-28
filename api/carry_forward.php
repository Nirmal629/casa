<?php
session_start();
include('dbConnection.php');
require_once __DIR__ . '/helpers/ledger_helper.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'html' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $host_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $year    = (int) ($_POST['year'] ?? date('Y'));
    $month   = (int) ($_POST['month'] ?? date('n'));

    if ($user_id <= 0) {
        $response['message'] = 'Invalid user ID';
        echo json_encode($response);
        exit;
    }

    // Carry Forward is only allowed for a FINISHED (past) month.
    // The current month and any future month are blocked.
    $nowYear  = (int) date('Y');
    $nowMonth = (int) date('n');
    if ($year > $nowYear || ($year == $nowYear && $month >= $nowMonth)) {
        $response['message'] = 'The current month cannot be carried forward until it ends.';
        echo json_encode($response);
        exit;
    }

    // 1. Calculate the balance using the helper
    $summary = calculateLedgerSummary($conn, $user_id, $host_id, $year, $month);
    $carryAmount = $summary['balance'];

    // Get month name of the selected month
    $monthName = date("F", mktime(0, 0, 0, $month, 10));
    $carryLabel = "Carry Forward from " . $monthName . " " . $year;

    // 2. Calculate the next month and year
    $nextMonth = ($month % 12) + 1;
    $nextYear  = ($month == 12) ? ($year + 1) : $year;
    $nextDate  = sprintf('%04d-%02d-01', $nextYear, $nextMonth);

    // Search for existing carry record for the next month
    $existingCarryQuery = "
        SELECT id, opening_balance, balance_type FROM host_player_carry_forward
        WHERE host_id = $host_id
          AND player_id = $user_id
          AND carry_month = $nextMonth
          AND carry_year = $nextYear
        LIMIT 1
    ";
    $existingCarryRes = mysqli_query($conn, $existingCarryQuery);
    $existingCarry = mysqli_fetch_assoc($existingCarryRes);

    $isDuplicate = false;
    $targetAmount = abs($carryAmount);
    $targetType = ($carryAmount > 0) ? 'DUE' : (($carryAmount < 0) ? 'ADVANCE' : 'DUE');

    if ($existingCarry) {
        if (abs((float)$existingCarry['opening_balance'] - $targetAmount) < 0.001 && $existingCarry['balance_type'] === $targetType) {
            $isDuplicate = true;
        } else {
            if ($carryAmount == 0) {
                $response['message'] = 'Carry balance reset to $0.00';
            }
        }
    } else {
        if ($carryAmount == 0) {
            $isDuplicate = true;
        }
    }

    if ($isDuplicate) {
        $response['success'] = false;
        $response['message'] = 'Carry already applied for this month.';
    } else {
        if ($response['message'] === '') {
            $response['success'] = true;
            $response['message'] = 'Balance carried forward successfully';
        }

        // ------------------------------------------------------------------
        // NEW FEATURE: Carry Forward Audit Trail
        // Independent, write-only. Does NOT change any existing logic —
        // it only records the operation into the two new audit tables.
        // (host_player_carry_forward → current state upsert,
        //  ca_host_player_carry_forward_history → append-only history)
        // ------------------------------------------------------------------
        require_once __DIR__ . '/helpers/carry_forward_audit.php';
        recordCarryForwardAudit(
            $conn,
            $host_id,       // created_by (host performing the carry)
            $user_id,       // player being carried
            $month, $year,  // source (month being carried FROM)
            $nextMonth, $nextYear, // destination (carry_month)
            $carryAmount,   // signed balance: + Due / - Advance / 0 reset
            $monthName      // for human-readable remarks
        );

        // Re-render and return the updated html for the selected month to refresh UI
        require_once __DIR__ . '/render_player_pay_html.php';
        $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month);
    }
}

echo json_encode($response);
exit;
