<?php
// DB connection ($conn) is already provided by inner-header.php

$currentYear = date('Y');
$currentMonth = date('n');
?>

<div class="playarPayment_game">
    <div class="custom_card">
        <!--<h6 class="card_heading">The Payment List</h6>-->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-primary">The Payment List</h6>
            <button id="refreshBtn" class="btn btn-sm btn-outline-secondary py-0" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>  

        <div class="mb-3">
            <form>
                <div class="row g-1 align-items-center">
                    <div class="col-auto">
                        <select class="form-select py-0 px-2" id="hhost" style="width: auto; max-width: 120px; height: 31px; font-size: 0.95rem;">
                        <option value="">Player</option>
                        <?php
                        $query = "SELECT ID, NAME FROM ca_users WHERE DEL_STATUS = 'N' AND LOG_STATUS='Y' ORDER BY NAME";
                        $result = mysqli_query($conn, $query);
                    
                        while ($row = mysqli_fetch_assoc($result)) {
                            $nameParts = explode(' ', trim($row['NAME']));
                            $shortName = $nameParts[0];
                            if (count($nameParts) > 1) {
                                $shortName .= ' ' . strtoupper(substr(end($nameParts), 0, 1)) . '.';
                            }
                            echo "<option value=\"{$row['ID']}\">" . htmlspecialchars($shortName) . "</option>";
                        }
                        ?>
                    </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select py-0 px-2" id="hpyear" style="width: 75px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                            <option value="">Year</option>
                            <?php
                            for ($year = 2025; $year <= $currentYear; $year++) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select py-0 px-2" id="hpmonth" style="width: 70px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                            <option value="">Month</option>
                            <?php
                            $months = [
                                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                            ];
                            foreach ($months as $num => $name) {
                                $selected = ($num == $currentMonth) ? 'selected' : '';
                                echo "<option value=\"$num\" $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <!--<button type="button" class="btn btn-primary" id="hpfilter">Submit</button>-->
                        <!--<button type="button" class="btn btn-danger" id="reset">Reset</button>-->
                        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center" id="hpfilter" style="height: 31px; width: 40px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php
        $players = $conn->query("SELECT * FROM ca_users WHERE USERTYPE = 'Player' ORDER BY ID ASC");
        if ($players->num_rows > 0) {
            echo '<div class="table-responsive host_payment" style="font-size: 0.75rem;">
                <table class="table table-success table-striped table-bordered table-sm text-nowrap align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>SN</th>
                            <th>Profile</th>
                            <th>Player</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Premium</th>
                            <th>Games</th>
                            <th>Amount$</th>
                            <th>Payment$</th>
                            <th>Due$</th>
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
                            <td><span style='color:#1a73e8;'>{$player['EMAIL']}</span><br></td>
                            <td><span style='color:#34a853;'>{$player['WHATSAPP_NUMBER']}</span></td>
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

        // $conn->close(); // Do NOT close — other tabs still need $conn
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
                                    <th scope="col">SN</th>
                                    <th scope="col">Date</th>
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

