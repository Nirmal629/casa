<?php
session_start();
include('dbConnection.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'html' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $game_id = isset($_POST['game_id']) ? (int) $_POST['game_id'] : 0;
    $year = $_POST['year'] ?? date('Y');
    $month = $_POST['month'] ?? date('n');

    // ✅ Step 1: Perform the action (approve/reject/pay)
    if ($action === 'approve') {
        $sql = "UPDATE ca_payment SET STATUS = 'Y' 
                WHERE USER_ID = $user_id AND GAME_ID = $game_id AND STATUS = 'N'";
        $conn->query($sql);
        $response['success'] = true;
        $response['message'] = 'Approved successfully';
    } elseif ($action === 'reject') {
        $sql = "UPDATE ca_payment SET STATUS = 'R' 
                WHERE USER_ID = $user_id AND GAME_ID = $game_id AND STATUS = 'N'";
        $conn->query($sql);
        $response['success'] = true;
        $response['message'] = 'Rejected successfully';
    } elseif ($action === 'pay') {
        $amount = (float) $_POST['amount'];
        $type = mysqli_real_escape_string($conn, $_POST['payment_type']);
        $date = date('Y-m-d');
        $time = date('H:i:s');

        $sql = "INSERT INTO ca_payment 
                (USER_ID, GAME_ID, AMOUNT, PAYMENT_DATE, PAYMENT_TIME, PAYMENT_TYPE, STATUS, CREATED_AT)
                VALUES 
                ($user_id, $game_id, $amount, '$date', '$time', '$type', 'Y', NOW())";
        $conn->query($sql);
        $response['success'] = true;
        $response['message'] = 'Payment added';
    }

    // ✅ Step 2: Fetch the updated HTML table
    ob_start();

    $query = "
        SELECT 
            cg.ID AS GAME_JOIN_ID,
            cg.GAME_ID,
            cg.PRICE,
            cg.CURRENCY,
            ce.EVENT_DATE,
            ce.EVENT_TIME,
            ce.EVENT_VENUE
        FROM ca_gamejoin cg
        INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
        WHERE cg.USER_ID = '$user_id'
            AND cg.HOST_ID = '{$_SESSION['user_id']}'
            AND cg.STATUS = 'Y'
            AND ce.STATUS = 'Completed'
            AND MONTH(ce.EVENT_DATE) = '$month'
            AND YEAR(ce.EVENT_DATE) = '$year'
        ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
    ";

    $result = mysqli_query($conn, $query);
    $count = mysqli_num_rows($result);

    if ($count > 0) {
        $i = 1;
        $totalAmount = 0;
        $totalPaid = 0;
        $totalDue = 0;

        echo '<table class="table table-striped table-bordered">
                <thead>
                    <tr class="table-info">
                        <th>Sl.</th>
                        <th>Date & Time</th>
                        <th>Venue</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Due</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';

        while ($row = mysqli_fetch_assoc($result)) {
            $game_id = $row['GAME_ID'];
            $price = $row['PRICE'];
            $currency = $row['CURRENCY'];

            $payResult = mysqli_query($conn, "
                SELECT SUM(p.AMOUNT) AS Total, MAX(p.STATUS) AS STATUS
                FROM ca_payment p
                INNER JOIN ca_events e ON p.GAME_ID = e.ID
                WHERE p.GAME_ID = '$game_id'
                  AND p.USER_ID = '$user_id'
                  AND p.STATUS != 'R'
                  AND MONTH(e.EVENT_DATE) = '$month'
                  AND YEAR(e.EVENT_DATE) = '$year'
            ");
            $payData = mysqli_fetch_assoc($payResult);
            $paid = $payData['Total'] ?? 0;
            $due = $price - $paid;

            $rowClass = '';
            if ($paid == 0) $rowClass = 'table-danger';
            elseif ($due == 0) $rowClass = 'table-success';

            $actionHtml = '';
            if ($due == 0 && $payData['STATUS'] === 'N') {
                $actionHtml = "
                    <button class='btn btn-success btn-sm approveBtnnn' data-id='{$game_id}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}'>Approve</button>
                    <button class='btn btn-danger btn-sm rejectBtnnn' data-id='{$game_id}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}'>Reject</button>
                ";
            } elseif ($paid == 0) {
                $actionHtml = "<button class='btn btn-warning btn-sm payBtnnn' data-id='{$game_id}' data-user='{$user_id}' data-due='{$due}' data-year='{$year}' data-month='{$month}'>Pay</button>";
            } else {
                $actionHtml = "
        <span class='badge bg-secondary'>Paid</span><br/>
        <button class='btn btn-danger btn-sm rollbackBtnnn mt-1' data-id='{$game_id}' data-user='{$user_id}' data-amount='{$paid}'>Rollback</button>
    ";
            }

            echo "<tr class='{$rowClass}'>
                    <td>{$i}</td>
                    <td>{$row['EVENT_DATE']} {$row['EVENT_TIME']}</td>
                    <td>{$row['EVENT_VENUE']}</td>
                    <td>{$currency} {$price}</td>
                    <td>{$currency} {$paid}</td>
                    <td>{$currency} {$due}</td>
                    <td>{$actionHtml}</td>
                </tr>";

            $totalAmount += $price;
            $totalPaid += $paid;
            $totalDue += $due;
            $i++;
        }

        echo "<tr class='table-dark'>
                <th colspan='3'>Total</th>
                <td>{$currency} {$totalAmount}</td>
                <td>{$currency} {$totalPaid}</td>
                <td>{$currency} {$totalDue}</td>
                <td></td>
              </tr>
            </tbody>
        </table>";
    } else {
        echo "<p>No Record(s)</p>";
    }

    $response['html'] = ob_get_clean();
}

echo json_encode($response);
exit;
