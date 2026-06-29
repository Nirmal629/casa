<?php
session_start();
include('dbConnection.php');

// Input sanitization
$filterYear = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
$filterMonth = isset($_POST['month']) ? (int)$_POST['month'] : date('n');
$filterPlayerId = isset($_POST['player']) && $_POST['player'] !== '' ? (int)$_POST['player'] : null;

// Prepare query
$sql = "SELECT * FROM ca_users WHERE USERTYPE='Player'";
if ($filterPlayerId) {
    $sql .= " AND ID = {$filterPlayerId}";
}
$sql .= " ORDER BY ID ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo '<div class="table-responsive host_payment">
            <table class="table table-success table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Sl. No.</th>
                        <th>Profile</th>
                        <th>Player Name</th>
                        <th>Email/Phone</th>
                            <th>IS PREMIUM</th>
                        <th>Total Game</th>
                        <th>Total Amount</th>
                        <th>Total Payment</th>
                        <th>Total Due</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
    $sl = 1;
    while ($data = $result->fetch_assoc()) {
        $user_id = $data['ID'];
        $profile_pic = !empty($data['PROFILE_IMAGE']) ? 'profile_img/' . $data['PROFILE_IMAGE'] : "../assets/images/profile.jpg";

        // Get games for this user in selected month/year
        $gamesQuery = "
            SELECT cg.GAME_ID, cg.PRICE, cg.CURRENCY
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = '{$user_id}'
              AND cg.HOST_ID = '{$_SESSION['user_id']}'
              AND cg.STATUS = 'Y'
              AND ce.STATUS = 'Completed'
              AND MONTH(ce.EVENT_DATE) = '{$filterMonth}'
              AND YEAR(ce.EVENT_DATE) = '{$filterYear}'
        ";
        $gamesResult = $conn->query($gamesQuery);
        $gameCount = $gamesResult->num_rows;

        if ($gameCount > 0) {
            $totalAmount = 0;
            $totalPaid = 0;
            $currency = '$'; // fallback

            while ($game = $gamesResult->fetch_assoc()) {
                $game_id = $game['GAME_ID'];
                $price = $game['PRICE'];
                $currency = $game['CURRENCY'];
                $totalAmount += $price;

                // Get payments by EVENT_DATE logic
                $paymentQuery = "
                    SELECT SUM(p.AMOUNT) AS PAYED_TOTAL
                    FROM ca_payment AS p
                    INNER JOIN ca_events AS e ON p.GAME_ID = e.ID
                    WHERE p.USER_ID = '{$user_id}'
                      AND p.GAME_ID = '{$game_id}'
                      AND p.STATUS != 'R'
                      AND MONTH(e.EVENT_DATE) = '{$filterMonth}'
                      AND YEAR(e.EVENT_DATE) = '{$filterYear}'
                ";
                $payResult = $conn->query($paymentQuery);
                $payRow = $payResult->fetch_assoc();
                $paid = $payRow['PAYED_TOTAL'] ?? 0;
                $totalPaid += $paid;
            }

            $totalDue = $totalAmount - $totalPaid;
            
            $isPremium = (isset($data['PREMIUM']) && $data['PREMIUM'] === 'Y');
                    $premiumChecked = $isPremium ? 'checked' : '';
                    $premiumLabel = $isPremium ? 'Premium' : 'Non Premium';
                    $switchId = 'premium-switch-' . $user_id;

            echo "<tr>
                    <th scope='row'>{$sl}</th>
                    <td><div class='profile_pic'><img src='{$profile_pic}' class='img-fluid' alt='profile pic' /></div></td>
                    <td>{$data['NAME']}</td>
                    <td>
    <span style='color:#1a73e8;'>{$data['EMAIL']}</span><br>
    <span style='color:#34a853;'>{$data['WHATSAPP_NUMBER']}</span>
</td>
                            <td>
                                <div class='form-check form-switch mb-0'>
                                    <input 
                                        class='form-check-input premium-switch' 
                                        type='checkbox' 
                                        id='{$switchId}'
                                        data-user-id='{$user_id}'
                                        {$premiumChecked}
                                        style='cursor:pointer'

                                    >
                                </div>
                            </td>
                    <td>{$gameCount}</td>
                    <td>{$currency} {$totalAmount}</td>
                    <td>{$currency} {$totalPaid}</td>
                    <td>{$currency} {$totalDue}</td>
                    <td><button type='button' class='playPaymentModal_open btn btn-primary btn-sm' data-id='{$user_id}'>View More</button></td>
                  </tr>";
            $sl++;
        }
    }

    echo '</tbody></table></div>';
} else {
    echo "<p>No players found.</p>";
}

$conn->close();
