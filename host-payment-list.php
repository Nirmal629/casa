<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Toronto');
include_once __DIR__ . '/dbConnection.php';

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
                <div class="row g-1 align-items-center flex-wrap">
                    <!-- Player filter -->
                    <div class="col-auto">
                        <select class="form-select form-select-sm py-0" id="hhost" style="width: auto; min-width: 120px; height: 31px;">
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
                    <!-- Year filter -->
                    <div class="col-auto">
                        <select class="form-select form-select-sm py-0" id="hpyear" style="width: auto; height: 31px;">
                            <option value="" selected>Year</option>
                            <?php
                            for ($year = 2025; $year <= $currentYear; $year++) {
                                echo "<option value=\"$year\">$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Month filter -->
                    <div class="col-auto">
                        <select class="form-select form-select-sm py-0" id="hpmonth" style="width: auto; height: 31px;">
                            <option value="" selected>Month</option>
                            <?php
                            $months = [
                                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                            ];
                            foreach ($months as $num => $name) {
                                echo "<option value=\"$num\">$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Search box -->
                    <div class="col-auto">
                        <input
                            type="text"
                            class="form-control form-control-sm py-0"
                            id="hpsearch"
                            placeholder="Search name / email / phone…"
                            style="height: 31px; min-width: 200px;"
                            autocomplete="off"
                        >
                    </div>
                    <!-- Sort by -->
                    <div class="col-auto d-none">
                        <select class="form-select form-select-sm py-0" id="hpsortby" style="width: auto; min-width: 120px; height: 31px;" title="Sort by">
                            <option value="name">Name</option>
                            <option value="games">Games</option>
                            <option value="amount">Amount</option>
                            <option value="paid">Payment</option>
                            <option value="due">Due</option>
                        </select>
                    </div>
                    <!-- Sort direction toggle -->
                    <div class="col-auto d-none">
                        <button type="button" id="hpsortdir" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" style="height: 31px;" data-dir="asc" title="Toggle sort direction">
                            <span id="hpsortdir-icon">▲</span>
                            <span id="hpsortdir-label" style="font-size:0.7rem;">ASC</span>
                        </button>
                    </div>
                    <!-- Search / Filter button -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center" id="hpfilter" style="height: 31px; width: 40px;" title="Search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Player list table — populated via AJAX on page load -->
        <div id="hostPaymentTableContainer" class="host_payment">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">Loading players...</p>
            </div>
        </div>
        
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

