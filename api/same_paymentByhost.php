<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];
    $amount = $_POST['amount'];
    $payment_date = date("Y-m-d"); // Current date (YYYY-MM-DD)
    $payment_time = date("H:i:s"); // Current time (HH:MM:SS)
    $payment_type = 'Cash';

    // Escape strings to prevent SQL injection
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $game_id = mysqli_real_escape_string($conn, $game_id);
    $amount = mysqli_real_escape_string($conn, $amount);
    $payment_date = mysqli_real_escape_string($conn, $payment_date);
    $payment_time = mysqli_real_escape_string($conn, $payment_time);
    $payment_type = mysqli_real_escape_string($conn, $payment_type);

    // SQL query to insert data
    $query = "INSERT INTO `ca_payment` (`USER_ID`, `GAME_ID`, `AMOUNT`, `PAYMENT_DATE`, `PAYMENT_TIME`, `PAYMENT_TYPE`, `STATUS`) 
              VALUES ('$user_id', '$game_id', '$amount', '$payment_date', '$payment_time', '$payment_type', 'Y')";

    if (mysqli_query($conn, $query)) {
            $query = "SELECT * FROM `ca_gamejoin` WHERE GAME_ID = '$game_id'";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) > 0) {
                echo '<table class="table table-success table-striped table-bordered datatable paymentTab">
                        <thead>
                            <tr class="table-info">
                                <th scope="col">Sl.</th>
                                <th scope="col">Player</th>
                                <th scope="col">Date Joined</th>
                                <th scope="col">Type</th>
                                <th scope="col">Total Amount</th>
                                <th scope="col">Due Amount</th>
                                <th scope="col">Pay</th>
                            </tr>
                        </thead>
                        <tbody>';
        
                $sl = 1; // Serial number counter
                while ($data = mysqli_fetch_assoc($result)) {
                    $select_user = mysqli_query($conn,"select * from ca_users where ID='".$data['USER_ID']."'");
                    $fetch_user = mysqli_fetch_assoc($select_user);
                    
                    $query_paid = "SELECT SUM(AMOUNT) AS total_amount FROM `ca_payment` WHERE USER_ID = '".$data['USER_ID']."' AND GAME_ID = '".$data['GAME_ID']."' AND STATUS !='R'";
                    $result_paid = mysqli_query($conn, $query_paid);
                    $data_paid = mysqli_fetch_assoc($result_paid);
                    $total_amount_paid = $data_paid['total_amount'] ?? 0; // Default to 0 if no payments found
                    
                    $due_amount = max($data['PRICE'] - $total_amount_paid, 0);
        
                    echo "<tr>
                            <th scope='row'>{$sl}</th>
                            <th scope='row'>{$fetch_user['NAME']} <strong>({$fetch_user['WHATSAPP_NUMBER']})</strong></th>
                            <td>{$data['CREATED_AT']}</td>
                            <td>{$data['TYPE']}</td>
                            <td>{$data['PRICE']}</td>
                            <td>{$due_amount}</td>
                            <td>";

                            // Show "Pay" button if due amount is greater than 0, else show "Paid"
                            echo ($due_amount != 0) ? 
                                "<button class='btn btn-success action-btn-pay' data-id='{$data['USER_ID']}' data-status='Y' data-game-id='{$game_id}' data-pay-amount='{$due_amount}'>
                                    <i class='fa-solid fa-check'></i> Pay
                                </button>" 
                                : "<span class='text-success'><i class='fa-solid fa-check-circle'></i> Paid</span>";
                        
                            echo "</td></tr>";
                    $sl++; // Increment serial number
                    }
                    
                    echo '</tbody></table>';
                
        
            } else {
                echo '<p class="text-danger">No payment records found.</p>';
            }
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>

