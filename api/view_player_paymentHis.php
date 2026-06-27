<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $game_id = $_POST['game_id'];

    // Fetch total amount paid by the user for this game
    $query = "SELECT * FROM `ca_payment` WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        echo '<table class="table table-success table-striped table-bordered datatable paymentTab">
                <thead>
                    <tr class="table-info">
                        <th scope="col">Sl.</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Date</th>
                        <th scope="col">Time</th>
                        <th scope="col">Type</th>
                        <th scope="col">Evidence</th>
                        <th scope="col">Message</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>';

        $sl = 1; // Serial number counter
        while ($data = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <th scope='row'>{$sl}</th>
                    <td>{$data['AMOUNT']}</td>
                    <td>{$data['PAYMENT_DATE']}</td>
                    <td>{$data['PAYMENT_TIME']}</td>
                    <td>{$data['PAYMENT_TYPE']}</td>
                    <td>{$data['DETAILS']}</td>
                    <td>{$data['MESSAGE']}</td>
<td><span class='"
                . ($data['STATUS'] == 'Y' ? "green" : ($data['STATUS'] == 'N' ? "blue" : ($data['STATUS'] == 'R' ? "red" : "Unknown"))) .
                "'>"
                . ($data['STATUS'] == 'Y' ? "Approved" : ($data['STATUS'] == 'N' ? "Pending" : ($data['STATUS'] == 'R' ? "Rejected" : "Unknown"))) .
                "</span></td>                </tr>";
            $sl++; // Increment serial number
        }

        echo '</tbody></table>';
    } else {
        echo '<p class="text-danger">No payment records found.</p>';
    }
    
}
?>
