<?php
require_once __DIR__ . '/_security.php';
requireApiUser(['Host','Trainer']);

include('dbConnection.php');
requireApiPost();
requireOwnedEvent($conn, $_POST['game_id'] ?? null);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = filter_var($_POST['payid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $game_id = (int) $_POST['game_id'];
    $status = $_POST['status'] ?? '';
    if (!$id || !is_string($status) || !in_array($status, ['Y', 'N', 'R'], true)) apiSecurityDeny(400, 'Invalid payment.');
    $stmt = $conn->prepare('UPDATE ca_payment SET STATUS = ? WHERE ID = ? AND GAME_ID = ?');
    $stmt->bind_param('sii', $status, $id, $game_id);
    $update_pay = $stmt->execute();
    if($update_pay)
    {
    // Fetch total amount paid by the user for this game
    $query = "SELECT * FROM `ca_payment` WHERE GAME_ID = '$game_id'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        echo '<table class="table table-success table-striped table-bordered datatable paymentTab">
                <thead>
                    <tr class="table-info">
                        <th scope="col">Sl.</th>
                        <th scope="col">Player</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Date</th>
                        <th scope="col">Time</th>
                        <th scope="col">Type</th>
                        <th scope="col">Evidence</th>
                        <th scope="col">Message</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>';

        $sl = 1; // Serial number counter
        while ($data = mysqli_fetch_assoc($result)) {
            $select_user = mysqli_query($conn,"select * from ca_users where ID='".$data['USER_ID']."'");
            $fetch_user = mysqli_fetch_assoc($select_user);
            echo "<tr>
                    <th scope='row'>{$sl}</th>
                    <th scope='row'>{$fetch_user['NAME']} <strong>({$fetch_user['WHATSAPP_NUMBER']})</strong></th>
                    <td>{$data['AMOUNT']}</td>
                    <td>{$data['PAYMENT_DATE']}</td>
                    <td>{$data['PAYMENT_TIME']}</td>
                    <td>{$data['PAYMENT_TYPE']}</td>
                    <td>{$data['DETAILS']}</td>
                    <td>{$data['MESSAGE']}</td>
                    <td>
                        <span class='status ";
                        
            if ($data['STATUS'] == 'Y') {
                echo "green'><i class='fa-solid fa-check-circle'></i> Approved";
            } elseif ($data['STATUS'] == 'N') {
                echo "yellow'><i class='fa-solid fa-hourglass-half'></i> Pending";
            } elseif ($data['STATUS'] == 'R') {
                echo "red'><i class='fa-solid fa-times-circle'></i> Rejected";
            } else {
                echo "gray'>Unknown";
            }
            
            echo "</span>
                    </td>
                    <td>";
            
            // Show Approve/Reject buttons if status is "Pending"
            if ($data['STATUS'] == 'N') {
                echo "
                    <button class='btn btn-success action-btn' data-id='{$data['ID']}' data-status='Y' data-game-id='{$game_id}'>
                        <i class='fa-solid fa-check'></i> Approve
                    </button>
                    <button class='btn btn-danger action-btn' data-id='{$data['ID']}' data-status='R' data-game-id='{$game_id}'>
                        <i class='fa-solid fa-times'></i> Reject
                    </button>";
            }
            
            echo "</td></tr>";
            $sl++; // Increment serial number
        }

        echo '</tbody></table>';
    } else {
        echo '<p class="text-danger">No payment records found.</p>';
    }
    }
    else
    {
     echo "Sorry! Server Is Busy";   
    }
    
}
?>
