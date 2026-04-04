<?php
session_start();
include('dbConnection.php');

    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];

    // Fetch total amount paid by the user for this game
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
    

?>
