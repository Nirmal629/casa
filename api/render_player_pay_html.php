<?php
/**
 * Shared HTML renderer for the player payment view (host-side).
 *
 * Used by: api/view_player_pay.php, api/payment_action.php, api/rollback_payment.php, api/add_expense.php
 *
 * Produces the grouped layout:
 *   - Quick Actions bar (Carry / Expense / Pay)
 *   - Expense form (hidden by default, toggled by the Expense quick action)
 *   - Event Cost section  (games + expenses)  →  Total Event Cost
 *   - Payments section    (existing payment rows, Rollback kept)  →  Total Payments
 *   - Balance section     (Total Due -$X red / Total Credit $X green / $0.00)
 *
 * CRITICAL: must NOT change any existing DB logic, queries, button classes
 * (approveBtnnn/rejectBtnnn/payBtnnn/rollbackBtnnn), or the $(".patmentTb").html() reload contract.
 */

require_once __DIR__ . '/helpers/ledger_helper.php';

if (!function_exists('renderPlayerPayHtml')) {

    function renderPlayerPayHtml($conn, $user_id, $year, $month, $is_player_side = false, $override_host_id = 0)
    {
        $host_id = ($override_host_id > 0) ? (int) $override_host_id : (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0);
        $user_id = (int) $user_id;
        $year = (int) $year;
        $month = (int) $month;

        // ------------------------------------------------------------------
        // Month state:
        //   - The current month cannot be carried forward (month not finished).
        //   - A month is LOCKED as soon as its closing balance has been
        //     carried forward → a Carry Forward record exists in the NEXT month.
        // ------------------------------------------------------------------
        $isCurrentMonth = ($year === (int) date('Y') && $month === (int) date('n'));
        $isPastMonth = ($year < (int) date('Y') || ($year === (int) date('Y') && $month < (int) date('n')));

        $nextMonth = ($month % 12) + 1;
        $nextYear = ($month == 12) ? ($year + 1) : $year;

        // Month M is locked ⟺ a Carry Forward record created FROM M exists in M+1
        $lockRes = mysqli_query($conn, "SELECT 1 FROM host_player_carry_forward
                WHERE host_id = $host_id AND player_id = $user_id
                  AND carry_month = $nextMonth AND carry_year = $nextYear
                LIMIT 1");
        $isLocked = ($lockRes && mysqli_num_rows($lockRes) > 0);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isOverrideActive = isset($_SESSION['ledger_override'][$user_id][$year][$month]) && $_SESSION['ledger_override'][$user_id][$year][$month] === true;
        if ($isPastMonth && $isLocked && $isOverrideActive) {
            $isOverrideActive = false;
            unset($_SESSION['ledger_override'][$user_id][$year][$month]);
        }
        $isEffectivelyLocked = $isLocked && !$isOverrideActive;

        $html = '';

        // Wrapper — used by JS to detect the month state (active / pending / locked)
        $monthState = $isEffectivelyLocked ? 'locked' : ($isCurrentMonth ? 'active' : 'pending');
        $html .= '<div class="pay-history-wrapper" data-locked="' . ($isEffectivelyLocked ? '1' : '0') . '" data-state="' . $monthState . '">';

        // Lock banner
        if ($isEffectivelyLocked) {
            $html .= '<div class="pay-lock-banner">
                <i class="fa-solid fa-lock"></i> This month has already been carried forward and is locked. Payments and expenses cannot be changed.
            </div>';
        } elseif (!$isCurrentMonth && !$isOverrideActive) {
            // Pending-carry month: read-only, only Carry Forward is allowed
            $html .= '<div class="pay-pending-banner">
                <i class="fa-solid fa-hourglass-half"></i> This month is pending carry forward. Only the Carry Forward action is allowed.
            </div>';
        }

        // ------------------------------------------------------------------
        // Quick Actions bar
        //   Active   (current month)     → Carry ✗  | Expense ✓ | Pay ✓
        //   Pending  (past, no carry)    → Carry ✓  | Expense ✗ | Pay ✗
        //   Locked   (carried forward)   → Carry ✗  | Expense ✗ | Pay ✗
        // ------------------------------------------------------------------
        $carryDisabled = ($isEffectivelyLocked || !$isPastMonth) ? 'disabled' : '';
        $carryTitle = $isCurrentMonth
            ? 'The current month cannot be carried forward until it ends.'
            : ($isEffectivelyLocked ? 'This month is locked (already carried forward).' : 'Carry the closing balance to the next month.');
        $lockDisabled = ($isEffectivelyLocked || (!$isCurrentMonth && !$isOverrideActive)) ? 'disabled' : '';
        $lockTitle = $isEffectivelyLocked
            ? 'This month is locked (already carried forward).'
            : 'Only the current month accepts new expenses and payments.';

        // Status chip for the month (Active / Pending Carry / Locked)
        if ($isLocked) {
            if ($isOverrideActive) {
                $statusChip = '<span class="pay-status-chip pay-status-active">🔓 UNLOCKED</span>';
            } else {
                $statusChip = '<span class="pay-status-chip pay-status-locked">🔒 LOCKED</span>';
            }
        } elseif ($isCurrentMonth) {
            $statusChip = '<span class="pay-status-chip pay-status-active"><i class="fa-solid fa-circle-check"></i> Active</span>';
        } else {
            if ($isOverrideActive) {
                $statusChip = '<span class="pay-status-chip pay-status-active">🔓 UNLOCKED</span>';
            } else {
                $statusChip = '<span class="pay-status-chip pay-status-pending"><i class="fa-solid fa-hourglass-half"></i> Pending Carry</span>';
            }
        }

        $overrideDisabled = '';
        if ($isPastMonth) {
            if ($isLocked) {
                $overrideDisabled = 'disabled';
            }
        } else {
            $overrideDisabled = 'disabled';
        }

        $origCarryDisabled = ($isLocked || !$isPastMonth) ? 'disabled' : '';
        $origLockDisabled = ($isLocked || !$isCurrentMonth) ? 'disabled' : '';
        $origOverrideDisabled = (!$isPastMonth || $isLocked) ? 'disabled' : '';

        $overrideState = $isOverrideActive ? 'on' : 'off';
        $overrideClass = $isOverrideActive ? 'pay-override-on' : '';
        $overrideIcon = $isOverrideActive ? 'fa-lock-open' : 'fa-lock';
        $overrideTitle = $isOverrideActive
            ? 'Override is Unlocked — click to restore the original state'
            : 'Override is Locked — click to unlock all action buttons (testing only)';

        $html .= '<div class="pay-history-actionbar">
                <span class="pay-history-actionbar-title"><i class="fa-solid fa-bolt"></i> Quick Actions ' . ($is_player_side ? '' : $statusChip) . '</span>
                <div class="pay-history-actionbar-btns">';
        
        if (!$is_player_side) {
            $html .= '
                    <button type="button" class="pay-action-btn pay-action-carry" data-action="carry" data-user="' . $user_id . '" data-year="' . $year . '" data-month="' . $month . '" ' . $carryDisabled . ' data-orig-disabled="' . $origCarryDisabled . '" title="' . $carryTitle . '">
                        <span class="pay-action-ic"><i class="fa-solid fa-arrows-rotate"></i></span> Carry
                    </button>
                    <button type="button" class="pay-action-btn pay-action-expense" data-action="expense" ' . $lockDisabled . ' data-orig-disabled="' . $origLockDisabled . '" title="' . $lockTitle . '">
                        <span class="pay-action-ic"><i class="fa-solid fa-receipt"></i></span> Expense
                    </button>';
        }
        
        $html .= '
                    <button type="button" class="pay-action-btn pay-action-pay" data-action="pay" ' . $lockDisabled . ' data-orig-disabled="' . $origLockDisabled . '" title="' . $lockTitle . '">
                        <span class="pay-action-ic"><i class="fa-solid fa-hand-holding-dollar"></i></span> Pay
                    </button>';
                    
        if (!$is_player_side) {
            $html .= '
                    <button type="button" class="pay-action-btn pay-action-override ' . $overrideClass . '" data-override="' . $overrideState . '" data-user="' . $user_id . '" data-year="' . $year . '" data-month="' . $month . '" ' . $overrideDisabled . ' data-orig-disabled="' . $origOverrideDisabled . '" title="' . $overrideTitle . '">
                        <span class="pay-action-ic"><i class="fa-solid ' . $overrideIcon . '"></i></span> Override
                    </button>';
        }

        $html .= '
                </div>
            </div>';

        // ------------------------------------------------------------------
        // Expense form (hidden by default — toggled by the Expense quick action)
        // NOTE: rendered only for the current month (and never when locked).
        // Previous months are read-only — expenses can only be added now.
        // ------------------------------------------------------------------
        if (!$isEffectivelyLocked) {
            $html .= '<div class="pay-expense-form-wrap" id="payExpenseFormWrap" style="display:none;">';
            $html .= '  <div class="pay-expense-form-title"><i class="fa-solid fa-receipt"></i> Add Expense</div>';
            $html .= '  <form id="payExpenseForm" class="pay-expense-form">';
            $html .= '      <input type="hidden" name="user_id" value="' . $user_id . '">';
            $html .= '      <input type="hidden" name="year" value="' . $year . '">';
            $html .= '      <input type="hidden" name="month" value="' . $month . '">';
            $html .= '      <div class="row g-2 align-items-end">';

            // Venue dropdown (existing venue list)
            $html .= '          <div class="col-md-3 col-6">
                        <label class="pay-form-label">Venue</label>
                        <select name="venue" class="form-select form-select-sm">';
            $venueResult = mysqli_query($conn, "SELECT DISTINCT EVENT_VENUE FROM ca_events WHERE EVENT_VENUE IS NOT NULL AND EVENT_VENUE != '' ORDER BY EVENT_VENUE");
            if ($venueResult && mysqli_num_rows($venueResult) > 0) {
                while ($vRow = mysqli_fetch_assoc($venueResult)) {
                    $html .= '<option value="' . htmlspecialchars($vRow['EVENT_VENUE'], ENT_QUOTES) . '">' . htmlspecialchars($vRow['EVENT_VENUE']) . '</option>';
                }
            } else {
                $html .= '<option value="Casa">Casa</option>';
            }
            $html .= '          </select>
                    </div>';

            // Type (manual text input)
            $html .= '          <div class="col-md-3 col-6">
                        <label class="pay-form-label">Type</label>
                        <input type="text" name="type" class="form-control form-control-sm" placeholder="e.g. Game, Stringing, Food, Parking, Hotel, Expense, Miscellaneous">
                    </div>';

            // Amount
            $html .= '          <div class="col-md-2 col-6">
                        <label class="pay-form-label">Amount</label>
                        <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm" placeholder="0.00">
                    </div>';

            // Date & Time (datetime-local) - Default to the selected year & month
            $defaultDay = ($month == (int) date('n') && $year == (int) date('Y')) ? date('d') : '01';
            $defaultDt = sprintf('%04d-%02d-%02dT%02d:%02d', $year, $month, (int) $defaultDay, date('H'), date('i'));
            $html .= '          <div class="col-md-2 col-6">
                        <label class="pay-form-label">Date &amp; Time</label>
                        <input type="datetime-local" name="expense_datetime" class="form-control form-control-sm" value="' . $defaultDt . '">
                    </div>';

            // Save / Cancel
            $html .= '          <div class="col-md-2 col-12">
                        <div class="d-flex gap-1 pb-1">
                            <button type="submit" class="btn btn-success btn-sm pay-expense-save">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm pay-expense-cancel">Cancel</button>
                        </div>
                    </div>';

            $html .= '      </div>';
            $html .= '  </form>';
            $html .= '</div>';

            // Pay form (hidden by default)
            $html .= '<div class="pay-expense-form-wrap" id="payPaymentFormWrap" style="display:none; margin-top: 10px;">';
            $html .= '  <div class="pay-expense-form-title" style="color: #059669;"><i class="fa-solid fa-hand-holding-dollar"></i> Add Payment</div>';
            $html .= '  <form id="payPaymentForm" class="pay-expense-form">';
            $html .= '      <input type="hidden" name="user_id" value="' . $user_id . '">';
            $html .= '      <input type="hidden" name="year" value="' . $year . '">';
            $html .= '      <input type="hidden" name="month" value="' . $month . '">';
            $html .= '      <div class="row g-2 align-items-end">';

            // Venue dropdown (existing venue list)
            $html .= '          <div class="col-md-3 col-6">
                        <label class="pay-form-label">Venue</label>
                        <select name="venue" class="form-select form-select-sm">';
            $venueResult = mysqli_query($conn, "SELECT DISTINCT EVENT_VENUE FROM ca_events WHERE EVENT_VENUE IS NOT NULL AND EVENT_VENUE != '' ORDER BY EVENT_VENUE");
            if ($venueResult && mysqli_num_rows($venueResult) > 0) {
                while ($vRow = mysqli_fetch_assoc($venueResult)) {
                    $html .= '<option value="' . htmlspecialchars($vRow['EVENT_VENUE'], ENT_QUOTES) . '">' . htmlspecialchars($vRow['EVENT_VENUE']) . '</option>';
                }
            } else {
                $html .= '<option value="Casa">Casa</option>';
            }
            $html .= '          </select>
                    </div>';

            // Type (manual text input)
            $html .= '          <div class="col-md-3 col-6">
                        <label class="pay-form-label">Type</label>
                        <input type="text" name="type" class="form-control form-control-sm" placeholder="e.g. Interac, Cash, Credit Card" value="Interac">
                    </div>';

            // Amount
            $html .= '          <div class="col-md-2 col-6">
                        <label class="pay-form-label">Amount</label>
                        <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm" placeholder="0.00" required>
                    </div>';

            // Date & Time (datetime-local)
            $defaultDay = ($month == (int) date('n') && $year == (int) date('Y')) ? date('d') : '01';
            $defaultDt = sprintf('%04d-%02d-%02dT%02d:%02d', $year, $month, (int) $defaultDay, date('H'), date('i'));
            $html .= '          <div class="col-md-2 col-6">
                        <label class="pay-form-label">Date &amp; Time</label>
                        <input type="datetime-local" name="payment_datetime" class="form-control form-control-sm" value="' . $defaultDt . '">';
            $html .= '      </div>';

            // Save / Cancel
            $html .= '          <div class="col-md-2 col-12">
                        <div class="d-flex gap-1 pb-1">
                            <button type="submit" class="btn btn-success btn-sm pay-payment-save">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm pay-payment-cancel">Cancel</button>
                        </div>
                    </div>';

            $html .= '      </div>';
            $html .= '  </form>';
            $html .= '</div>';
        } // end if (! $isLocked && $isCurrentMonth) — expense form

        // ------------------------------------------------------------------
        // Event Cost rows: games (existing query + TYPE from EVENT_CATEGORY) + expenses
        // Carry Forward records are EXCLUDED from the real costs (they are only an
        // opening balance — they must never change the totals).
        // ------------------------------------------------------------------
        $gamesQuery = "
            SELECT
                cg.ID AS GAME_JOIN_ID,
                cg.GAME_ID,
                cg.PRICE,
                cg.CURRENCY,
                ce.EVENT_DATE,
                ce.EVENT_TIME,
                ce.EVENT_VENUE,
                ce.EVENT_CATEGORY AS COST_TYPE
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = '$user_id'
                AND cg.HOST_ID = '$host_id'
                AND cg.STATUS = 'Y'
                AND ce.STATUS = 'Completed'
                AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
                AND MONTH(ce.EVENT_DATE) = '$month'
                AND YEAR(ce.EVENT_DATE) = '$year'
            ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
        ";
        $gamesResult = mysqli_query($conn, $gamesQuery);

        // Expenses (new ca_expense records) — carry records excluded from real costs
        $expenseQuery = "
            SELECT
                ID,
                VENUE,
                TYPE AS COST_TYPE,
                AMOUNT,
                CURRENCY,
                EXPENSE_DATE,
                EXPENSE_TIME
            FROM ca_expense
            WHERE USER_ID = '$user_id'
                AND HOST_ID = '$host_id'
                AND STATUS = 'Y'
                AND NOT (TYPE = 'PreviousDue' OR TYPE LIKE 'Carry Forward from %')
                AND MONTH(EXPENSE_DATE) = '$month'
                AND YEAR(EXPENSE_DATE) = '$year'
            ORDER BY EXPENSE_DATE DESC, EXPENSE_TIME DESC
        ";
        $expenseResult = mysqli_query($conn, $expenseQuery);

        // Carry Forward opening-balance records for the SELECTED month
        // (old-style PreviousDue events + ca_expense carry records) — displayed
        // separately, never included in totals.
        // Carry Forward opening-balance record for the SELECTED month from host_player_carry_forward
        $carryRecordQuery = mysqli_query($conn, "SELECT opening_balance, balance_type, source_month, source_year FROM host_player_carry_forward WHERE host_id = $host_id AND player_id = $user_id AND carry_month = $month AND carry_year = $year LIMIT 1");
        $carryRecord = ($carryRecordQuery) ? mysqli_fetch_assoc($carryRecordQuery) : null;

        $carryCostRows = [];
        $carryPayRows = [];

        if ($carryRecord && (float) $carryRecord['opening_balance'] > 0) {
            $cAmount = (float) $carryRecord['opening_balance'];
            $cMonthName = date("F", mktime(0, 0, 0, $carryRecord['source_month'], 10));
            $cLabel = "Carry Forward from " . $cMonthName . " " . $carryRecord['source_year'];
            $cDateTime = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, 1, 0, 0, 0);

            if ($carryRecord['balance_type'] === 'DUE') {
                $carryCostRows[] = [
                    'kind' => 'expense',
                    'id' => 0,
                    'price' => $cAmount,
                    'currency' => 'CAD',
                    'cost_date' => sprintf('%04d-%02d-01', $year, $month),
                    'cost_time' => '00:00:00',
                    'venue' => 'Casa',
                    'type' => $cLabel,
                ];
            } else { // ADVANCE
                $carryPayRows[] = [
                    'ID' => 0,
                    'GAME_ID' => 0,
                    'AMOUNT' => $cAmount,
                    'PAYMENT_DATE' => sprintf('%04d-%02d-01', $year, $month),
                    'PAYMENT_TIME' => '00:00:00',
                    'PAYMENT_TYPE' => $cLabel,
                    'STATUS' => 'Y'
                ];
            }
        }

        // Merge both into one sorted list (by date desc, time desc)
        $costRows = [];
        if ($gamesResult && mysqli_num_rows($gamesResult) > 0) {
            while ($gRow = mysqli_fetch_assoc($gamesResult)) {
                $costRows[] = [
                    'kind' => 'game',
                    'id' => $gRow['GAME_ID'],
                    'price' => $gRow['PRICE'],
                    'currency' => $gRow['CURRENCY'],
                    'cost_date' => $gRow['EVENT_DATE'],
                    'cost_time' => $gRow['EVENT_TIME'],
                    'venue' => $gRow['EVENT_VENUE'],
                    'type' => ($gRow['COST_TYPE'] !== null && $gRow['COST_TYPE'] !== '') ? $gRow['COST_TYPE'] : 'Game',
                ];
            }
        }
        if ($expenseResult && mysqli_num_rows($expenseResult) > 0) {
            while ($eRow = mysqli_fetch_assoc($expenseResult)) {
                $costRows[] = [
                    'kind' => 'expense',
                    'id' => $eRow['ID'],
                    'price' => $eRow['AMOUNT'],
                    'currency' => ($eRow['CURRENCY'] !== null && $eRow['CURRENCY'] !== '') ? $eRow['CURRENCY'] : 'CAD',
                    'cost_date' => $eRow['EXPENSE_DATE'],
                    'cost_time' => $eRow['EXPENSE_TIME'],
                    'venue' => $eRow['VENUE'],
                    'type' => ($eRow['COST_TYPE'] !== null && $eRow['COST_TYPE'] !== '') ? $eRow['COST_TYPE'] : 'Expense',
                ];
            }
        }

        // Sort: date DESC, time DESC (nulls last)
        usort($costRows, function ($a, $b) {
            $cmp = strcmp((string) $b['cost_date'], (string) $a['cost_date']);
            if ($cmp !== 0)
                return $cmp;
            return strcmp((string) $b['cost_time'], (string) $a['cost_time']);
        });

        // ------------------------------------------------------------------
        // Payments (existing payment rows — keep Rollback)
        // Carry Forward payments (GAME_ID = 0) are EXCLUDED from the total —
        // they are opening-balance records, never a new Payment.
        // ------------------------------------------------------------------
        $paymentsQuery = "
            SELECT
                p.ID,
                p.GAME_ID,
                p.AMOUNT,
                p.PAYMENT_DATE,
                p.PAYMENT_TIME,
                p.PAYMENT_TYPE,
                p.STATUS,
                COALESCE(e.EVENT_VENUE, ex.VENUE, p.DETAILS, '') AS VENUE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = '$user_id'
                AND (
                    (p.GAME_ID > 0 AND e.HOST_ID = '$host_id')
                    OR (p.GAME_ID < 0 AND ex.HOST_ID = '$host_id')
                    OR (p.GAME_ID = 0 AND (p.REVIEWED_BY = '$host_id' OR p.STATUS = 'N' OR p.STATUS = 'R'))
                )
                AND NOT (
                    (p.GAME_ID = 0 AND (p.PAYMENT_TYPE = 'Carry' OR p.PAYMENT_TYPE LIKE 'Carry Forward from %'))
                    OR (p.GAME_ID > 0 AND EXISTS (
                            SELECT 1 FROM ca_events e2
                            WHERE e2.ID = p.GAME_ID
                              AND (e2.EVENT_CATEGORY = 'PreviousDue' OR e2.EVENT_CATEGORY LIKE 'Carry Forward from %')
                        ))
                    OR (p.GAME_ID < 0 AND EXISTS (
                            SELECT 1 FROM ca_expense ex2
                            WHERE ex2.ID = -p.GAME_ID
                              AND (ex2.TYPE = 'PreviousDue' OR ex2.TYPE LIKE 'Carry Forward from %')
                        ))
                )
                AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = '$month'
                AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = '$year'
            ORDER BY p.PAYMENT_DATE DESC, p.PAYMENT_TIME DESC
        ";
        $paymentsResult = mysqli_query($conn, $paymentsQuery);

        // Carry Forward payment records (opening balance) — displayed separately
        $carryPayResult = null;

        // Settlements of carried balances (payments made against a Carry Forward
        // cost record) — displayed separately; they ARE actual payment
        // transactions and count in the month's Total Payments.
        $carrySettleQuery = "
            SELECT
                p.ID,
                p.GAME_ID,
                p.AMOUNT,
                p.PAYMENT_DATE,
                p.PAYMENT_TIME,
                p.PAYMENT_TYPE,
                p.STATUS,
                COALESCE(e.EVENT_VENUE, ex.VENUE, p.DETAILS, '') AS VENUE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = '$user_id'
                AND p.STATUS != 'R'
                AND (
                    (p.GAME_ID > 0 AND e.HOST_ID = '$host_id')
                    OR (p.GAME_ID < 0 AND ex.HOST_ID = '$host_id')
                    OR (p.GAME_ID = 0 AND (p.REVIEWED_BY = '$host_id' OR p.STATUS = 'N'))
                )
                AND (
                    (p.GAME_ID > 0 AND (e.EVENT_CATEGORY = 'PreviousDue' OR e.EVENT_CATEGORY LIKE 'Carry Forward from %'))
                    OR (p.GAME_ID < 0 AND (ex.TYPE = 'PreviousDue' OR ex.TYPE LIKE 'Carry Forward from %'))
                )
                AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = '$month'
                AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = '$year'
            ORDER BY p.PAYMENT_DATE DESC, p.PAYMENT_TIME DESC
        ";
        $carrySettleResult = mysqli_query($conn, $carrySettleQuery);

        $hasData = (count($costRows) > 0)
            || (count($carryCostRows) > 0)
            || ($paymentsResult && mysqli_num_rows($paymentsResult) > 0)
            || (count($carryPayRows) > 0)
            || ($carrySettleResult && mysqli_num_rows($carrySettleResult) > 0);

        if ($hasData) {
            // ==============================================================
            // UNIFIED TABLE: Event Cost + Payments
            // ==============================================================
            $html .= '<div class="pay-section pay-section-unified">
                <table class="table table-striped table-bordered pay-table">
                    <thead>
                        <tr class="pay-table-header">
                            <th>Sl.</th>
                            <th>Date &amp; Time</th>
                            <th>Venue</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="pay-table-section-title">
                            <th colspan="6">Event Cost</th>
                        </tr>';

            // ---- Opening Balance (Carry Forward) rows — never in totals ----
            $carrySl = 0;
            if (count($carryCostRows) > 0) {
                $html .= '<tr class="pay-table-section-sub">
                            <th colspan="6">Opening Balance (Carry Forward)</th>
                        </tr>';
                foreach ($carryCostRows as $cRow) {
                    $carrySl++;
                    $cDateTime = $cRow['cost_date'] . ' ' . (strlen((string) $cRow['cost_time']) >= 5 ? substr($cRow['cost_time'], 0, 5) : $cRow['cost_time']);
                    
                    $rollbackBtn = '';
                    if (!$is_player_side) {
                        if (!$isEffectivelyLocked) {
                            $rollbackBtn = " <button type='button' class='btn btn-danger btn-sm pay-action-rollback-carry' data-user='{$user_id}' data-year='{$year}' data-month='{$month}' style='padding: 2px 6px; font-size: 0.68rem; margin-left: 5px; vertical-align: middle;'>Rollback</button>";
                        } else {
                            $rollbackBtn = " <button type='button' class='btn btn-danger btn-sm pay-action-rollback-carry' data-user='{$user_id}' data-year='{$year}' data-month='{$month}' disabled style='padding: 2px 6px; font-size: 0.68rem; margin-left: 5px; vertical-align: middle; opacity: 0.5;'>Rollback</button>";
                        }
                    }

                    $html .= "<tr class='pay-row-carry'>
                            <td>{$carrySl}</td>
                            <td>{$cDateTime}</td>
                            <td>" . htmlspecialchars($cRow['venue']) . "</td>
                            <td>" . htmlspecialchars($cRow['type']) . "</td>
                            <td>" . formatPayAmount((float) $cRow['price'], $cRow['currency']) . "</td>
                            <td>
                                <span class='badge pay-badge-opening'>Opening</span>
                                {$rollbackBtn}
                            </td>
                        </tr>";
                }
            }

            $i = 1;
            $totalEventCost = 0;
            $grandTotalEventCost = 0;
            $renderedCostCount = 0;

            foreach ($costRows as $row) {
                $game_id = ($row['kind'] === 'expense') ? (-1 * (int) $row['id']) : (int) $row['id'];
                $price = (float) $row['price'];
                $currency = $row['currency'];

                // Per-row payment (existing logic: join events for games; direct match for expenses)
                if ($row['kind'] === 'expense') {
                    $payResult = mysqli_query($conn, "
                        SELECT SUM(p.AMOUNT) AS Total, MAX(p.STATUS) AS STATUS, MAX(CASE WHEN p.STATUS = 'N' THEN p.ID ELSE NULL END) AS PENDING_ID
                        FROM ca_payment p
                        WHERE p.GAME_ID = '$game_id'
                          AND p.USER_ID = '$user_id'
                          AND p.STATUS != 'R'
                    ");
                } else {
                    $payResult = mysqli_query($conn, "
                        SELECT SUM(p.AMOUNT) AS Total, MAX(p.STATUS) AS STATUS, MAX(CASE WHEN p.STATUS = 'N' THEN p.ID ELSE NULL END) AS PENDING_ID
                        FROM ca_payment p
                        INNER JOIN ca_events e ON p.GAME_ID = e.ID
                        WHERE p.GAME_ID = '$game_id'
                          AND p.USER_ID = '$user_id'
                          AND p.STATUS != 'R'
                          AND MONTH(e.EVENT_DATE) = '$month'
                          AND YEAR(e.EVENT_DATE) = '$year'
                    ");
                }
                $payData = mysqli_fetch_assoc($payResult);
                $paid = (float) ($payData['Total'] ?? 0);
                $due = $price - $paid;
                $pending_id = $payData['PENDING_ID'] ?? 0;

                $statusQuery = mysqli_query($conn, "SELECT STATUS FROM ca_payment WHERE USER_ID = '$user_id' AND GAME_ID = '$game_id' ORDER BY ID DESC LIMIT 1");
                $statusData = mysqli_fetch_assoc($statusQuery);
                $latestStatus = $statusData ? $statusData['STATUS'] : '';

                $rowClass = '';
                if ($paid == 0)
                    $rowClass = 'pay-row-cost-unpaid';
                elseif ($due == 0)
                    $rowClass = 'pay-row-cost-paid';

                $actionHtml = '';
                if ($isLocked) {
                    // Locked month: no add / edit / delete of payments or expenses
                    $actionHtml = "<span class='badge pay-badge-locked'><i class='fa-solid fa-lock'></i> Locked</span>";
                } elseif (!$isCurrentMonth) {
                    // Pending-carry month: read-only — only Carry Forward is allowed
                    $actionHtml = "<span class='badge pay-badge-pending'>Pending Carry</span>";
                } elseif ($due == 0 && isset($payData['STATUS']) && $payData['STATUS'] === 'N') {
                    if ($is_player_side) {
                        $actionHtml = "<span class='badge pay-badge-pending'>Pending Approval</span>";
                    } else {
                        $actionHtml = "
                            <div class='pay-action-group'>
                                <button class='btn btn-success btn-sm approveBtnnn' data-id='{$game_id}' data-payment-id='{$pending_id}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}'>Approve</button>
                                <button class='btn btn-danger btn-sm rejectBtnnn' data-id='{$game_id}' data-payment-id='{$pending_id}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}'>Reject</button>
                            </div>
                        ";
                    }
                } elseif ($paid == 0) {
                    if ($is_player_side && $latestStatus === 'R') {
                        $actionHtml = "<span class='badge bg-danger text-white me-1' style='font-size:0.75rem;'>Rejected</span><button class='btn btn-danger btn-sm payBtnnn' data-id='{$game_id}' data-user='{$user_id}' data-due='{$due}' data-year='{$year}' data-month='{$month}'>Repay</button>";
                    } elseif ($latestStatus === 'R') {
                        $actionHtml = "<span class='badge bg-danger text-white' style='font-size:0.75rem;'>Rejected</span>";
                    } else {
                        $actionHtml = "<button class='btn btn-warning btn-sm payBtnnn' data-id='{$game_id}' data-user='{$user_id}' data-due='{$due}' data-year='{$year}' data-month='{$month}'>Pay</button>";
                    }
                } else {
                    $actionHtml = "<span class='badge pay-badge-paid'>Paid</span>";
                }

                // Date & Time: Y-m-d H:i (e.g. 2026-08-03 23:00)
                $costDateTime = $row['cost_date'] . ' ' . (strlen((string) $row['cost_time']) >= 5 ? substr($row['cost_time'], 0, 5) : $row['cost_time']);

                // Amount: $35.00 (no "CAD" text)
                $amountHtml = formatPayAmount($price, $currency);

                $html .= "<tr class='{$rowClass}'>
                        <td>{$i}</td>
                        <td>{$costDateTime}</td>
                        <td>" . htmlspecialchars($row['venue']) . "</td>
                        <td>" . htmlspecialchars($row['type']) . "</td>
                        <td>{$amountHtml}</td>
                        <td>{$actionHtml}</td>
                    </tr>";

                $totalEventCost += $price;
                $i++;
                $renderedCostCount++;
            }

            if ($renderedCostCount === 0) {
                $html .= "<tr><td colspan='6' class='text-center text-muted' style='font-size: 0.76rem; padding: 6px;'>No pending event costs.</td></tr>";
            }

            $summary = calculateLedgerSummary($conn, $user_id, $host_id, $year, $month);
            $totalEventCost = $summary['totalExpense'];

            foreach ($carryCostRows as $cRow) {
                // Overridden by summary value but loop kept for structure
            }

            $html .= "      <tr class='pay-table-footer'>
                            <th colspan='4'>Total Event Cost</th>
                            <td>" . formatPayAmount($totalEventCost, 'CAD') . "</td>
                            <td></td>
                        </tr>";

            // Payments sub-section inside the same table
            $html .= '  <tr class="pay-table-section-title">
                            <th colspan="6">Payments</th>
                        </tr>';

            // ---- Carry Forward (Opening Balance) payments — never in totals ----
            $carryPayCount = 0;
            if (count($carryPayRows) > 0) {
                $html .= '<tr class="pay-table-section-sub">
                            <th colspan="6">Opening Balance (Carry Forward)</th>
                        </tr>';
                foreach ($carryPayRows as $cpRow) {
                    $carryPayCount++;
                    $cpAmount = (float) $cpRow['AMOUNT'];
                    $cpDateTime = $cpRow['PAYMENT_DATE'] . ' ' . (strlen((string) $cpRow['PAYMENT_TIME']) >= 5 ? substr($cpRow['PAYMENT_TIME'], 0, 5) : $cpRow['PAYMENT_TIME']);
                    $cpType = ($cpRow['PAYMENT_TYPE'] !== null && $cpRow['PAYMENT_TYPE'] !== '') ? $cpRow['PAYMENT_TYPE'] : 'Carry';
                    
                    $rollbackBtn = '';
                    if (!$is_player_side) {
                        if (!$isEffectivelyLocked) {
                            $rollbackBtn = " <button type='button' class='btn btn-danger btn-sm pay-action-rollback-carry' data-user='{$user_id}' data-year='{$year}' data-month='{$month}' style='padding: 2px 6px; font-size: 0.68rem; margin-left: 5px; vertical-align: middle;'>Rollback</button>";
                        } else {
                            $rollbackBtn = " <button type='button' class='btn btn-danger btn-sm pay-action-rollback-carry' data-user='{$user_id}' data-year='{$year}' data-month='{$month}' disabled style='padding: 2px 6px; font-size: 0.68rem; margin-left: 5px; vertical-align: middle; opacity: 0.5;'>Rollback</button>";
                        }
                    }

                    $html .= "<tr class='pay-row-carry'>
                            <td>{$carryPayCount}</td>
                            <td>{$cpDateTime}</td>
                            <td>—</td>
                            <td>" . htmlspecialchars($cpType) . "</td>
                            <td>" . formatPayAmount($cpAmount, 'CAD') . "</td>
                            <td>
                                <span class='badge pay-badge-opening'>Opening</span>
                                {$rollbackBtn}
                            </td>
                        </tr>";
                }
            }

            // ---- Settlements of carried balances — REAL payments made by the
            // player against a Previous Due amount. They are actual payment
            // transactions, so they ARE included in the Total Payments below. ----
            $carrySettleCount = 0;
            $totalPayments = 0;
            if ($carrySettleResult && mysqli_num_rows($carrySettleResult) > 0) {
                $html .= '<tr class="pay-table-section-sub">
                            <th colspan="6">Carry Settlement</th>
                        </tr>';
                while ($csRow = mysqli_fetch_assoc($carrySettleResult)) {
                    $carrySettleCount++;
                    $csAmount = (float) $csRow['AMOUNT'];
                    $totalPayments += $csAmount;
                    $csDateTime = $csRow['PAYMENT_DATE'] . ' ' . (strlen((string) $csRow['PAYMENT_TIME']) >= 5 ? substr($csRow['PAYMENT_TIME'], 0, 5) : $csRow['PAYMENT_TIME']);
                    $csType = ($csRow['PAYMENT_TYPE'] !== null && $csRow['PAYMENT_TYPE'] !== '') ? $csRow['PAYMENT_TYPE'] : 'Carry Settlement';
                    $html .= "<tr class='pay-row-carry'>
                            <td>{$carrySettleCount}</td>
                            <td>{$csDateTime}</td>
                            <td>" . htmlspecialchars($csRow['VENUE']) . "</td>
                            <td>" . htmlspecialchars($csType) . "</td>
                            <td>" . formatPayAmount($csAmount, 'CAD') . "</td>
                            <td><span class='badge pay-badge-opening'>Opening</span></td>
                        </tr>";
                }
            }

            $j = 1;

            if ($paymentsResult && mysqli_num_rows($paymentsResult) > 0) {
                while ($pRow = mysqli_fetch_assoc($paymentsResult)) {
                    $payGameId = (int) $pRow['GAME_ID'];
                    $payAmount = (float) $pRow['AMOUNT'];
                    $payStatus = $pRow['STATUS'];

                    if ($payStatus === 'Y') {
                        $totalPayments += $payAmount;
                    }

                    $payDateTime = $pRow['PAYMENT_DATE'] . ' ' . (strlen((string) $pRow['PAYMENT_TIME']) >= 5 ? substr($pRow['PAYMENT_TIME'], 0, 5) : $pRow['PAYMENT_TIME']);

                    // Display the stored payment type (e.g. Carry Forward from March), fallback to "Carry" if empty
                    $payType = ($pRow['PAYMENT_TYPE'] !== null && $pRow['PAYMENT_TYPE'] !== '') ? $pRow['PAYMENT_TYPE'] : 'Carry';

                    $actionContent = '';
                    if ($payStatus === 'Y') {
                        $rollbackBtn = '';
                        if (!$is_player_side) {
                            if ($isLocked) {
                                $rollbackBtn = "<span class='badge pay-badge-locked'><i class='fa-solid fa-lock'></i> Locked</span>";
                            } elseif (!$isCurrentMonth) {
                                $rollbackBtn = "<span class='badge pay-badge-pending'>Pending Carry</span>";
                            } else {
                                $rollbackBtn = "<button class='btn btn-danger btn-sm rollbackBtnnn' data-id='{$payGameId}' data-user='{$user_id}' data-amount='{$payAmount}' data-year='{$year}' data-month='{$month}'>Rollback</button>";
                            }
                        }
                        $actionContent = "
                            <div class='pay-action-group'>
                                <span class='badge pay-badge-paid'>Paid</span>
                                {$rollbackBtn}
                            </div>
                        ";
                    } elseif ($payStatus === 'N') {
                        if ($is_player_side) {
                            $actionContent = "<span class='badge bg-warning text-dark'>Pending Approval</span>";
                        } else {
                            if ($isLocked) {
                                $actionContent = "<span class='badge bg-warning text-dark'>Pending Approval</span> <span class='badge pay-badge-locked'><i class='fa-solid fa-lock'></i> Locked</span>";
                            } elseif (!$isCurrentMonth) {
                                $actionContent = "<span class='badge bg-warning text-dark'>Pending Approval</span> <span class='badge pay-badge-pending'>Pending Carry</span>";
                            } else {
                                $actionContent = "
                                    <div class='pay-action-group' style='display:inline-flex; gap:5px;'>
                                        <span class='badge bg-warning text-dark' style='align-self: center;'>Pending Approval</span>
                                        <button class='btn btn-success btn-sm approveBtnnn' data-id='{$payGameId}' data-payment-id='{$pRow['ID']}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}'>Approve</button>
                                        <button class='btn btn-danger btn-sm rejectBtnnn' data-id='{$payGameId}' data-payment-id='{$pRow['ID']}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}'>Reject</button>
                                    </div>
                                ";
                            }
                        }
                    } elseif ($payStatus === 'R') {
                        $reinitiateBtn = '';
                        if ($is_player_side) {
                            $reinitiateBtn = " <button class='btn btn-warning btn-sm reinitiateBtnnn' data-payment-id='{$pRow['ID']}' data-user='{$user_id}' data-year='{$year}' data-month='{$month}' style='padding: 2px 6px; font-size: 0.68rem; margin-left: 5px; vertical-align: middle;'>Re-initiate</button>";
                        }
                        $actionContent = "
                            <div class='pay-action-group'>
                                <span class='badge bg-danger text-white'>Rejected</span>
                                {$reinitiateBtn}
                            </div>
                        ";
                    }

                    $rowBgColor = '';
                    if ($payStatus === 'Y') {
                        $rowBgColor = 'background-color: #d5ebd5 !important;';
                    } elseif ($payStatus === 'N') {
                        $rowBgColor = 'background-color: #fef3c7 !important;';
                    } elseif ($payStatus === 'R') {
                        $rowBgColor = 'background-color: #fde8e8 !important;';
                    }

                    $html .= "<tr class='pay-row-payment'>
                            <td style='{$rowBgColor}'>{$j}</td>
                            <td style='{$rowBgColor}'>{$payDateTime}</td>
                            <td style='{$rowBgColor}'>" . htmlspecialchars($pRow['VENUE'] ?? '') . "</td>
                            <td style='{$rowBgColor}'>" . htmlspecialchars($payType) . "</td>
                            <td style='{$rowBgColor}'>" . formatPayAmount($payAmount, 'CAD') . "</td>
                            <td style='{$rowBgColor}'>{$actionContent}</td>
                        </tr>";
                    $j++;
                }
            } elseif ($carryPayCount === 0 && $carrySettleCount === 0) {
                $html .= "<tr><td colspan='6' class='text-center'>No payments recorded for this month.</td></tr>";
            }

            $html .= "      <tr class='pay-table-footer'>
                            <th colspan='4'>Total Payments</th>
                            <td>" . formatPayAmount($totalPayments, 'CAD') . "</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>";

            // ==============================================================
            // SECTION 3: Balance
            //   balance = Total Event Cost - Total Payments
            //   balance > 0  →  "Total Due"   "$65.00"  (RED)
            //   balance == 0 →  "Total Due"   "$0.00"
            //   balance < 0  →  "Total Credit" "-$65.00"  (GREEN)
            // ==============================================================
            $totalPayments = $summary['totalPaid'];
            $balance = $summary['balance'];

            $balanceLabel = 'Final Balance';
            $balanceClass = 'pay-balance-due';
            if ($balance < 0) {
                $balanceClass = 'pay-balance-credit';
            }
            $balanceHtml = '$' . number_format(abs($balance), 2);

            // Same lock detection as computed at the top ($isLocked): a carry
            // record exists in the NEXT month referencing the selected month.
            $carryIndicator = '';
            if ($isLocked) {
                $carryIndicator = ' <span class="pay-carry-status" style="font-size: 0.78rem; font-weight: 700; color: #10b981; margin-left: 8px;">(Carry Forward &#10003;)</span>';
            }

            $html .= '<div class="pay-section pay-section-balance">
                <div class="pay-balance-row ' . $balanceClass . '">
                    <span class="pay-balance-label">' . $balanceLabel . $carryIndicator . '</span>
                    <span class="pay-balance-value">' . $balanceHtml . '</span>
                </div>
            </div>';

        } else {
            $html .= '<p class="text-center text-muted py-2" style="font-size: 0.78rem; margin: 0;">No Record(s)</p>';
        }

        $html .= '</div>'; // .pay-history-wrapper

        return $html;
    }

    /**
     * Format amount with symbol only (no "CAD" text): CAD → $, INR → ₹
     */
    function formatPayAmount($amount, $currency)
    {
        $amount = (float) $amount;
        if (strtoupper((string) $currency) === 'INR') {
            return '₹' . number_format($amount, 2);
        }
        return '$' . number_format($amount, 2);
    }
}
