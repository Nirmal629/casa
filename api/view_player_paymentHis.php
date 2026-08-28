<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];

    // Fetch all payment attempts for this game/player with reviewer info
    $query = "SELECT p.*, u.NAME AS REVIEWED_BY_NAME
              FROM `ca_payment` p
              LEFT JOIN ca_users u ON p.REVIEWED_BY = u.ID
              WHERE p.USER_ID = '$user_id' AND p.GAME_ID = '$game_id'
              ORDER BY p.ID ASC";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        echo '<table class="table table-success table-striped table-bordered datatable paymentTab">
                <thead>
                    <tr class="table-info">
                        <th scope="col">Attempt</th>
                        <th scope="col">Date &amp; Time</th>
                        <th scope="col">Requested By</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Status</th>
                        <th scope="col">Reviewed By</th>
                        <th scope="col">Reviewed Date &amp; Time</th>
                        <th scope="col">Remarks</th>
                    </tr>
                </thead>
                <tbody>';

        $sl = 1; // Attempt counter
        while ($data = mysqli_fetch_assoc($result)) {
            $statusStr = $data['STATUS'] == 'Y' ? "Approved / Paid" : ($data['STATUS'] == 'N' ? "Pending Approval" : ($data['STATUS'] == 'R' ? "Rejected" : "Unknown"));
            $statusClass = $data['STATUS'] == 'Y' ? "green" : ($data['STATUS'] == 'N' ? "blue" : ($data['STATUS'] == 'R' ? "red" : "Unknown"));
            $reviewedBy = $data['REVIEWED_BY_NAME'] ? htmlspecialchars($data['REVIEWED_BY_NAME']) : '—';
            $reviewedAt = $data['REVIEWED_AT'] && $data['REVIEWED_AT'] !== '0000-00-00 00:00:00' ? $data['REVIEWED_AT'] : '—';
            $remarks = $data['REJECTION_REASON'] ? htmlspecialchars($data['REJECTION_REASON']) : '—';

            echo "<tr>
                    <th scope='row'>Attempt #{$sl}</th>
                    <td>{$data['PAYMENT_DATE']} {$data['PAYMENT_TIME']}</td>
                    <td>Player</td>
                    <td>{$data['AMOUNT']}</td>
                    <td><span class='{$statusClass}'>{$statusStr}</span></td>
                    <td>{$reviewedBy}</td>
                    <td>{$reviewedAt}</td>
                    <td>{$remarks}</td>
                  </tr>";
            $sl++; // Increment serial number
        }

        echo '</tbody></table>';
    } else {
        echo '<p class="text-danger">No payment records found.</p>';
    }
    
}
?>
