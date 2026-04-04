<?php
session_start();
date_default_timezone_set('America/Toronto');

const DATABASE_NAME = 'casa_db';
const USERNAME = 'casa_sports';
const PASSWORD = 'C@sa_sports24#';
$host = 'localhost';
$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentYear = date('Y');
$currentMonth = date('n');
?>

<div class="playarPayment_game">
    <div class="custom_card">
        <h6 class="card_heading">Player Payment List</h6>

        <div class="mb-4">
            <form>
                <div class="row">
                    <div class="col-auto">
                        <select class="form-select" id="hhost">
                        <option value="">Select Player</option>
                        <?php
                        $query = "SELECT ID, NAME FROM ca_users WHERE DEL_STATUS = 'N' AND LOG_STATUS='Y' ORDER BY NAME";
                        $result = mysqli_query($conn, $query);
                    
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<option value=\"{$row['ID']}\">{$row['NAME']}</option>";
                        }
                        ?>
                    </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" id="hpyear">
                            <option value="">Select the Year</option>
                            <?php
                            for ($year = 2024; $year <= 2030; $year++) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" id="hpmonth">
                            <option value="">Select the Month</option>
                            <?php
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                            ];
                            foreach ($months as $num => $name) {
                                $selected = ($num == $currentMonth) ? 'selected' : '';
                                echo "<option value=\"$num\" $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary" id="hpfilter">Submit</button>
                        <!--<button type="button" class="btn btn-danger" id="reset">Reset</button>-->
                    </div>
                </div>
            </form>
        </div>

        <?php
        $players = $conn->query("SELECT * FROM ca_users WHERE USERTYPE = 'Player' ORDER BY ID ASC");
        if ($players->num_rows > 0) {
            echo '<div class="table-responsive host_payment">
                <table class="table table-success table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Profile</th>
                            <th>Player Name</th>
                            <th>Email/Phone</th>
                            <th>IS PREMIUM</th>
                            <th>Total Games</th>
                            <th>Total Amount</th>
                            <th>Total Payment</th>
                            <th>Total Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>';

            $sl = 1;
            while ($player = $players->fetch_assoc()) {
                $user_id = $player['ID'];
                $profile_pic = !empty($player['PROFILE_IMAGE']) ? 'profile_img/' . $player['PROFILE_IMAGE'] : 'assets/images/profile.jpg';

                // Get all joined games for this player for selected month/year
                $gamesQuery = "
                    SELECT cg.ID, cg.GAME_ID, cg.PRICE, cg.CURRENCY
                    FROM ca_gamejoin cg
                    INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
                    WHERE cg.USER_ID = '$user_id'
                        AND cg.HOST_ID = '{$_SESSION['user_id']}'
                        AND cg.STATUS = 'Y'
                        AND ce.STATUS = 'Completed'
                        AND MONTH(ce.EVENT_DATE) = '$currentMonth'
                        AND YEAR(ce.EVENT_DATE) = '$currentYear'
                ";

                $gamesResult = $conn->query($gamesQuery);
                $gameCount = $gamesResult->num_rows;

                if ($gameCount > 0) {
                    $totalAmount = 0;
                    $totalPaid = 0;

                    while ($game = $gamesResult->fetch_assoc()) {
                        $game_id = $game['GAME_ID'];
                        $price = $game['PRICE'];
                        $currency = $game['CURRENCY'];
                        $totalAmount += $price;

                        // Get payments for this game
                        $paymentQuery = "
                            SELECT SUM(p.AMOUNT) AS PAYED
                            FROM ca_payment p
                            INNER JOIN ca_events e ON p.GAME_ID = e.ID
                            WHERE p.USER_ID = '$user_id'
                                AND p.GAME_ID = '$game_id'
                                AND p.STATUS != 'R'
                                AND MONTH(e.EVENT_DATE) = '$currentMonth'
                                AND YEAR(e.EVENT_DATE) = '$currentYear'
                        ";
                        $paymentResult = $conn->query($paymentQuery);
                        $paymentData = $paymentResult->fetch_assoc();
                        $totalPaid += $paymentData['PAYED'] ?? 0;
                    }

                    $totalDue = $totalAmount - $totalPaid;
                    
                    $isPremium = (isset($player['PREMIUM']) && $player['PREMIUM'] === 'Y');
                    $premiumChecked = $isPremium ? 'checked' : '';
                    $premiumLabel = $isPremium ? 'Premium' : 'Non Premium';
                    $switchId = 'premium-switch-' . $user_id;

                    echo "<tr>
                            <th scope='row'>{$sl}</th>
                            <td><div class='profile_pic'><img src='{$profile_pic}' class='img-fluid' alt='profile pic' /></div></td>
                            <td>{$player['NAME']}</td>
                            <td>
    <span style='color:#1a73e8;'>{$player['EMAIL']}</span><br>
    <span style='color:#34a853;'>{$player['WHATSAPP_NUMBER']}</span>
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
        ?>
        
        <!-- Player Modal -->
        <section class="customModal_wrap playPaymentModal">
            <div class="customModal_body">
                <h6 class="customModal_head">View History</h6>
                <button class="customModal_close btn playPaymentModal_close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="customModal_content">
                    <div class="table-responsive patmentTb">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr class="table-info">
                                    <th scope="col">Sl.</th>
                                    <th scope="col">Date & Time</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Payment</th>
                                    <th scope="col">Due</th>
                                    <th scope="col">Verify</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic content will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>
<script>
(function () {
    // This function will handle the toggle logic
    function handlePremiumChange(checkbox) {
        const userId = checkbox.getAttribute('data-user-id');
        const isPremium = checkbox.checked ? 'Y' : 'N';

        const formCheck = checkbox.closest('.form-check');
        const label = formCheck ? formCheck.querySelector('.form-check-label') : null;

        const originalChecked = !checkbox.checked; // for revert if error
        const originalText = label ? label.textContent : '';

        // Optimistic UI update
        if (label) {
            label.textContent = checkbox.checked ? 'Premium' : 'Non Premium';
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../api/update_player_premium.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status !== 200 || xhr.responseText.trim() !== 'OK') {
                    alert('Failed to update premium status. Please try again.');

                    // Revert checkbox & label
                    checkbox.checked = originalChecked;
                    if (label) {
                        label.textContent = originalText || (checkbox.checked ? 'Premium' : 'Non Premium');
                    }
                }
            }
        };

        const params =
            'user_id=' + encodeURIComponent(userId) +
            '&is_premium=' + encodeURIComponent(isPremium);

        xhr.send(params);
    }

    // EVENT DELEGATION: works for current and future .premium-switch elements
    document.addEventListener('change', function (e) {
        const target = e.target;
        if (target && target.classList && target.classList.contains('premium-switch')) {
            handlePremiumChange(target);
        }
    });
})();
</script>

