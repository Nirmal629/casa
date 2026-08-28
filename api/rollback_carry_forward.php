<?php
session_start();
include('dbConnection.php');

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

    // Check if the current month is locked (carried forward to next month)
    $nextMonth = ($month % 12) + 1;
    $nextYear = ($month == 12) ? ($year + 1) : $year;
    
    $isOverrideActive = isset($_SESSION['ledger_override'][$user_id][$year][$month]) && $_SESSION['ledger_override'][$user_id][$year][$month] === true;
    
    $isLocked = false;
    if (!$isOverrideActive) {
        try {
            $lockRes = mysqli_query($conn, "SELECT 1 FROM host_player_carry_forward
                    WHERE host_id = $host_id AND player_id = $user_id
                      AND carry_month = $nextMonth AND carry_year = $nextYear
                    LIMIT 1");
            $isLocked = ($lockRes && mysqli_num_rows($lockRes) > 0);
        } catch (mysqli_sql_exception $e) {
            $isLocked = false;
        }
        if ($isLocked) {
            $response['message'] = 'This month is locked and cannot be rolled back.';
            echo json_encode($response);
            exit;
        }
    }

    // Fetch the carry record for this month
    $carryQuery = null;
    try {
        $carryQuery = mysqli_query($conn, "SELECT id, opening_balance, balance_type, source_month, source_year 
                FROM host_player_carry_forward 
                WHERE host_id = $host_id AND player_id = $user_id 
                  AND carry_month = $month AND carry_year = $year 
                LIMIT 1");
    } catch (mysqli_sql_exception $e) {
        $carryQuery = null;
    }
            
    if ($carryQuery && mysqli_num_rows($carryQuery) > 0) {
        $carryRow = mysqli_fetch_assoc($carryQuery);
        $carryForwardId = (int) $carryRow['id'];
        $openingBalance = (float) $carryRow['opening_balance'];
        $balanceType = $carryRow['balance_type'];
        $sourceMonth = (int) $carryRow['source_month'];
        $sourceYear = (int) $carryRow['source_year'];

        // Delete from host_player_carry_forward
        $deleteOk = mysqli_query($conn, "DELETE FROM host_player_carry_forward WHERE id = $carryForwardId");
        
        if ($deleteOk) {
            // Append ROLLBACK action to history
            $monthName = date("F", mktime(0, 0, 0, $sourceMonth, 10));
            $remarks = "Rolled back carry forward from " . $monthName . " " . $sourceYear . " to " . $month . "/" . $year;
            $remarksEsc = mysqli_real_escape_string($conn, $remarks);

            mysqli_query($conn, "INSERT INTO ca_host_player_carry_forward_history
                    (carry_forward_id, host_id, player_id, source_month, source_year,
                     destination_month, destination_year, opening_balance, balance_type,
                     action, remarks, created_by, created_at)
                VALUES
                    ($carryForwardId, $host_id, $user_id, $sourceMonth, $sourceYear,
                     $month, $year, $openingBalance, '$balanceType',
                     'ROLLBACK', '$remarksEsc', $host_id, NOW())");

            $response['success'] = true;
            $response['message'] = 'Carry forward rolled back successfully.';
        } else {
            $response['message'] = 'Failed to delete carry forward record.';
        }
    } else {
        $response['message'] = 'Carry forward record not found.';
    }

    // Re-render and return updated HTML
    require_once __DIR__ . '/render_player_pay_html.php';
    $response['html'] = renderPlayerPayHtml($conn, $user_id, $year, $month);
}

echo json_encode($response);
exit;
