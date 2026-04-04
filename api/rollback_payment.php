<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $game_id = mysqli_real_escape_string($conn, $_POST['game_id']);
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $year = $_POST['year'] ?? date('Y');
    $month = $_POST['month'] ?? date('n');

    // Delete the payment entry
    $query = "DELETE FROM ca_payment WHERE GAME_ID = '$game_id' AND USER_ID = '$user_id'";

    if (mysqli_query($conn, $query)) {
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
    } else {
        echo "Failed to delete payment: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
