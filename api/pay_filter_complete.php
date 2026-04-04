<?php
session_start();
include('dbConnection.php');
if($_POST['type']=='filter')
{
    echo '<table class="table table-success table-striped table-bordered datatable paymentTab">
        <thead>
            <tr class="table-info">
                <th scope="col">Sl.</th>
                                        <th scope="col">Host</th>

                <th scope="col">Date & Time</th>
                <th scope="col">Venue</th>
                <th scope="col">Amount</th>
                <th scope="col">Payment</th>
                <th scope="col">Due</th>
                <th scope="col">View History</th>
                <th scope="col">Add Payment</th>

            </tr>
        </thead>
        <tbody>';

$year = $_POST['year'];
$month = $_POST['month'];
$host = $_POST['host'];

$conditions = [];

// Dynamic filters
if (!empty($host)) {
    $conditions[] = "ce.HOST_ID = '" . mysqli_real_escape_string($conn, $host) . "'";
}
if (!empty($year)) {
    $conditions[] = "YEAR(ce.EVENT_DATE) = '" . mysqli_real_escape_string($conn, $year) . "'";
}
if (!empty($month)) {
    $conditions[] = "MONTH(ce.EVENT_DATE) = '" . mysqli_real_escape_string($conn, $month) . "'";
}

// Base query
$sql = "
    SELECT 
        cg.ID AS GAME_JOIN_ID, cg.USER_ID, cg.GAME_ID, cg.PRICE, cg.CURRENCY, cg.STATUS AS GAME_JOIN_STATUS, cg.CREATED_AT AS GAME_JOIN_CREATED_AT, 
        ce.ID AS EVENT_ID, ce.HOST_NAME, ce.EVENT_DATE, ce.EVENT_TIME, ce.EVENT_VENUE, ce.EVENT_COST AS EVENT_PRICE, ce.EVENT_CURRENCY, 
        ce.STATUS AS EVENT_STATUS, ce.CREATED_AT AS EVENT_CREATED_AT 
    FROM ca_gamejoin AS cg 
    INNER JOIN ca_events AS ce ON cg.GAME_ID = ce.ID 
    WHERE cg.USER_ID = '" . mysqli_real_escape_string($conn, $_SESSION['user_id']) . "' 
    AND cg.STATUS = 'Y' 
    AND ce.STATUS = 'Completed'
    ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
";

// Append dynamic conditions
if (!empty($conditions)) {
    $sql .= ' AND ' . implode(' AND ', $conditions);
}

// Execute query
$select_game = mysqli_query($conn, $sql);

$count_game = mysqli_num_rows($select_game);

if ($count_game > 0) {
    $totalAmount = 0;
    $totalPayment = 0;
    $totalDue = 0;
    $i = 1;

    while ($fetch_games = mysqli_fetch_assoc($select_game)) {
        $selectPayment = mysqli_query($conn, "SELECT SUM(AMOUNT) AS Total FROM ca_payment WHERE USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$fetch_games['GAME_ID']."' AND STATUS!='R'");
        $fetchPayment = mysqli_fetch_assoc($selectPayment);
        
        $dueAmount = $fetch_games['PRICE'] - $fetchPayment['Total'];
        $totalAmount += $fetch_games['PRICE'];
        $totalPayment += $fetchPayment['Total'];
        $totalDue += $dueAmount;
        
        $selectPaymentStatus = mysqli_query($conn,"select * from ca_payment where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$fetch_games['GAME_ID']."'");
                        $countPaymentStatus = mysqli_num_rows($selectPaymentStatus);
                        $fetchPaymentStatus = mysqli_fetch_assoc($selectPaymentStatus);
                        
                        // Default button values
                        $payBtnText = "Pay";
                        $payBtnClass = "PayAmountModal_open btn-primary";
                        $payBtnDisabled = "";
                        
                        // If payment record exists
                        if ($countPaymentStatus > 0) {
                            if ($fetchPaymentStatus['STATUS'] === 'N') {
                                // Paid but not approved
                                $payBtnText = "Paid";
                                $payBtnClass = "btn-primary"; // no modal open class
                            } elseif ($fetchPaymentStatus['STATUS'] === 'Y') {
                                // Approved
                                $payBtnText = "Approved";
                                $payBtnClass = "btn-success"; // different color
                                $payBtnDisabled = "disabled"; // disable completely
                            }
                            elseif ($fetchPaymentStatus['STATUS'] === 'R') {
                                // Approved
                                $payBtnText = "Rejected! Pay Again";
                                $payBtnClass = "btn-danger"; // different color
                            }
                        }

        echo '<tr>
                <th scope="row">'.$i.'</th>
                <td>'.$fetch_games['HOST_NAME'].'</td>
                <td>'.$fetch_games['EVENT_DATE'].' '.$fetch_games['EVENT_TIME'].'</td>
                <td>'.$fetch_games['EVENT_VENUE'].'</td>
                <td>'.$fetch_games['CURRENCY'].' '.$fetch_games['PRICE'].'</td>
                <td>'.$fetch_games['CURRENCY'].' '.$fetchPayment['Total'].'</td>
                <td>'.$fetch_games['CURRENCY'].' '.$dueAmount.'</td>
                <td><button class="btn view_btn" data-id="'.$fetch_games['GAME_ID'].'" data-user-id="'.$_SESSION['user_id'].'"><i class="fa-regular fa-eye"></i></button></td>
                <td><button class="btn btn-primary '.$payBtnClass.' mb-2" data-id="'.$fetch_games['GAME_ID'].'" data-user-id="'.$_SESSION['user_id'].'" '.$payBtnDisabled.'>'.$payBtnText.'</button></td>

            </tr>';
        $i++;
    }
} else {
    echo '<tr><td colspan="8" class="text-center">No Record(s)</td></tr>';
}

echo '<tr class="table-dark">
        <th class="text-start" colspan="4">Total:</th>
        <td>'.$totalAmount.'</td>
        <td>'.$totalPayment.'</td>
        <td>'.$totalDue.'</td>
        <td></td>
    </tr>';

echo '</tbody></table>';

}
else
{
    $currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
    echo '<table class="table table-success table-striped table-bordered datatable paymentTab">
        <thead>
            <tr class="table-info">
                <th scope="col">Sl.</th>
                <th scope="col">Host</th>
                <th scope="col">Date & Time</th>
                <th scope="col">Venue</th>
                <th scope="col">Amount</th>
                <th scope="col">Payment</th>
                <th scope="col">Due</th>
                <th scope="col">View History</th>
                <th scope="col">Add Payment</th>

            </tr>
        </thead>
        <tbody>';


$select_game = mysqli_query($conn, "SELECT 
        cg.ID AS GAME_JOIN_ID, cg.USER_ID, cg.GAME_ID, cg.PRICE, cg.CURRENCY, cg.STATUS AS GAME_JOIN_STATUS, cg.CREATED_AT AS GAME_JOIN_CREATED_AT, 
        ce.ID AS EVENT_ID,ce.HOST_NAME AS HOST_NAME, ce.EVENT_DATE, ce.EVENT_TIME, ce.EVENT_VENUE, ce.EVENT_COST AS EVENT_PRICE, ce.EVENT_CURRENCY AS EVENT_CURRENCY, 
        ce.STATUS AS EVENT_STATUS, ce.CREATED_AT AS EVENT_CREATED_AT 
    FROM ca_gamejoin AS cg 
    INNER JOIN ca_events AS ce ON cg.GAME_ID = ce.ID 
    WHERE cg.USER_ID = '".$_SESSION['user_id']."' 
    AND cg.STATUS = 'Y' 
    AND ce.STATUS = 'Completed' AND YEAR(ce.EVENT_DATE) = '$currentYear' AND MONTH(ce.EVENT_DATE) = '$currentMonth' ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC");
    

$count_game = mysqli_num_rows($select_game);

if ($count_game > 0) {
    $totalAmount = 0;
    $totalPayment = 0;
    $totalDue = 0;
    $i = 1;

    while ($fetch_games = mysqli_fetch_assoc($select_game)) {
        $selectPayment = mysqli_query($conn, "SELECT SUM(AMOUNT) AS Total FROM ca_payment WHERE USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$fetch_games['GAME_ID']."' AND STATUS!='R'");
        $fetchPayment = mysqli_fetch_assoc($selectPayment);
        
        $dueAmount = $fetch_games['PRICE'] - $fetchPayment['Total'];
        $totalAmount += $fetch_games['PRICE'];
        $totalPayment += $fetchPayment['Total'];
        $totalDue += $dueAmount;
        
        $selectPaymentStatus = mysqli_query($conn,"select * from ca_payment where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$fetch_games['GAME_ID']."'");
                        $countPaymentStatus = mysqli_num_rows($selectPaymentStatus);
                        $fetchPaymentStatus = mysqli_fetch_assoc($selectPaymentStatus);
                        
                        // Default button values
                        $payBtnText = "Pay";
                        $payBtnClass = "PayAmountModal_open btn-primary";
                        $payBtnDisabled = "";
                        
                        // If payment record exists
                        if ($countPaymentStatus > 0) {
                            if ($fetchPaymentStatus['STATUS'] === 'N') {
                                // Paid but not approved
                                $payBtnText = "Paid";
                                $payBtnClass = "btn-primary"; // no modal open class
                            } elseif ($fetchPaymentStatus['STATUS'] === 'Y') {
                                // Approved
                                $payBtnText = "Approved";
                                $payBtnClass = "btn-success"; // different color
                                $payBtnDisabled = "disabled"; // disable completely
                            }
                            elseif ($fetchPaymentStatus['STATUS'] === 'R') {
                                // Approved
                                $payBtnText = "Rejected! Pay Again";
                                $payBtnClass = "btn-danger"; // different color
                            }
                        }

        echo '<tr>
                <th scope="row">'.$i.'</th>
                <td>'.$fetch_games['HOST_NAME'].'</td>
                <td>'.$fetch_games['EVENT_DATE'].' '.$fetch_games['EVENT_TIME'].'</td>
                <td>'.$fetch_games['EVENT_VENUE'].'</td>
                <td>'.$fetch_games['CURRENCY'].' '.$fetch_games['PRICE'].'</td>
                <td>'.$fetch_games['CURRENCY'].' '.$fetchPayment['Total'].'</td>
                <td>'.$fetch_games['CURRENCY'].' '.$dueAmount.'</td>
                <td><button class="btn view_btn" data-id="'.$fetch_games['GAME_ID'].'" data-user-id="'.$_SESSION['user_id'].'"><i class="fa-regular fa-eye"></i></button></td>
                <td><button class="btn btn-primary '.$payBtnClass.' mb-2" data-id="'.$fetch_games['GAME_ID'].'" data-user-id="'.$_SESSION['user_id'].'" '.$payBtnDisabled.'>'.$payBtnText.'</button></td>

            </tr>';
        $i++;
    }
} else {
    echo '<tr><td colspan="8" class="text-center">No Record(s)</td></tr>';
}

echo '<tr class="table-dark">
        <th class="text-start" colspan="4">Total:</th>
        <td>'.$totalAmount.'</td>
        <td>'.$totalPayment.'</td>
        <td>'.$totalDue.'</td>
        <td></td>
    </tr>';

echo '</tbody></table>';

}

?>