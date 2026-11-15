<?php

declare(strict_types=1);

namespace App\Services\Ledger;

require_once __DIR__ . '/../../Helpers/Pagination.php';

use App\Helpers\Pagination;

class LedgerPresenter
{
    public static function formatMoney(float $amount, string $currency = 'CAD'): string
    {
        if (function_exists('formatPayAmount')) {
            return formatPayAmount($amount, $currency);
        }
        if (strtoupper($currency) === 'INR') {
            return '₹' . number_format($amount, 2);
        }
        return '$' . number_format($amount, 2);
    }

    public static function renderMonthlyHtml(array $ledger): string
    {
        if (empty($ledger['success']) && !empty($ledger['message'])) {
            return '<div class="text-center py-12 text-slate-500 font-medium"><i class="fa-solid fa-building-circle-exclamation me-2 text-amber-500"></i> ' . htmlspecialchars($ledger['message']) . '</div>';
        }

        $playerId = (int) ($ledger['player_id'] ?? 0);
        $hostId = (int) ($ledger['host_id'] ?? 0);
        $year = (int) ($ledger['year'] ?? (int) date('Y'));
        $month = (int) ($ledger['month'] ?? (int) date('n'));
        $isPlayerSide = (bool) ($ledger['is_player_side'] ?? false);

        $mState = $ledger['month_state'] ?? [];
        $isLocked = (bool) ($mState['is_locked'] ?? false);
        $isCurrentMonth = (bool) ($mState['is_current_month'] ?? false);
        $isPastMonth = (bool) ($mState['is_past_month'] ?? false);
        $isOverrideActive = (bool) ($mState['is_override_active'] ?? false);
        $isEffectivelyLocked = (bool) ($mState['is_effectively_locked'] ?? false);
        $monthStateStr = (string) ($mState['state'] ?? 'active');

        $perms = $ledger['permissions'] ?? [];
        $canCarry = (bool) ($perms['can_carry'] ?? false);
        $canExpense = (bool) ($perms['can_expense'] ?? false);
        $canPay = (bool) ($perms['can_pay'] ?? false);
        $canOverride = (bool) ($perms['can_override'] ?? false);

        $venues = $ledger['venues'] ?? ['Casa'];
        $opening = $ledger['opening_balance'] ?? null;
        $eventCosts = $ledger['event_costs'] ?? [];
        $payments = $ledger['payments'] ?? [];
        $totals = $ledger['totals'] ?? [];

        $html = '';

        // Wrapper with state attributes
        $html .= '<div class="pay-history-wrapper" data-locked="' . ($isEffectivelyLocked ? '1' : '0') . '" data-state="' . htmlspecialchars($monthStateStr) . '">';

        // Banners
        if ($isEffectivelyLocked) {
            $html .= '<div class="pay-lock-banner">
                <i class="fa-solid fa-lock"></i> This month has already been carried forward and is locked. Payments and expenses cannot be changed.
            </div>';
        } elseif (!$isCurrentMonth && !$isOverrideActive) {
            $isAlreadyCarried = !empty($perms['is_already_carried']);
            $isDownstreamLocked = !empty($perms['is_downstream_locked']);
            $destMonth = (int) ($perms['dest_month'] ?? (($month % 12) + 1));
            $destYear = (int) ($perms['dest_year'] ?? (($month === 12) ? ($year + 1) : $year));

            if ($isAlreadyCarried) {
                $html .= '<div class="pay-lock-banner" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                    <i class="fa-solid fa-circle-check text-success"></i> This month\'s closing balance has already been carried forward to ' . date("F", mktime(0, 0, 0, $destMonth, 10)) . ' ' . $destYear . '.
                </div>';
            } elseif ($isDownstreamLocked) {
                $html .= '<div class="pay-lock-banner">
                    <i class="fa-solid fa-lock"></i> The subsequent month (' . date("F", mktime(0, 0, 0, $destMonth, 10)) . ' ' . $destYear . ') has already been carried forward. This month\'s carry forward is locked.
                </div>';
            } else {
                $html .= '<div class="pay-pending-banner">
                    <i class="fa-solid fa-hourglass-half"></i> This month is pending carry forward. ' . ($isPlayerSide ? '' : 'Only the Carry Forward action is allowed.') . '
                </div>';
            }
        }

        // Quick Actions bar
        $carryDisabledAttr = $canCarry ? '' : 'disabled';
        $carryOrigDisabled = ($isLocked || !$isPastMonth || !empty($perms['is_already_carried']) || !empty($perms['is_downstream_locked'])) ? 'disabled' : '';
        $carryTitle = $perms['carry_title'] ?? ($isCurrentMonth
            ? 'The current month cannot be carried forward until it ends.'
            : ($isEffectivelyLocked ? 'This month is locked (already carried forward).' : 'Carry the closing balance to the next month.'));

        $expenseDisabledAttr = $canExpense ? '' : 'disabled';
        $payDisabledAttr = $canPay ? '' : 'disabled';
        $origLockDisabled = ($isLocked || !$isCurrentMonth) ? 'disabled' : '';
        $lockTitle = $isEffectivelyLocked
            ? 'This month is locked (already carried forward).'
            : 'Only the current month accepts new expenses and payments.';

        $overrideState = $isOverrideActive ? 'on' : 'off';
        $overrideClass = $isOverrideActive ? 'pay-override-on' : '';
        $overrideIcon = $isOverrideActive ? 'fa-lock-open' : 'fa-lock';
        $overrideDisabledAttr = $canOverride ? '' : 'disabled';
        $origOverrideDisabled = (!$isPastMonth || $isLocked) ? 'disabled' : '';
        $overrideTitle = $isOverrideActive
            ? 'Override is Unlocked — click to restore original state'
            : 'Override is Locked — click to unlock all action buttons (testing only)';

        $html .= '<div class="pay-history-actionbar">';
        $html .= '<span class="pay-history-actionbar-title"><i class="fa-solid fa-bolt"></i> Quick Actions</span>';
        $html .= '<div class="pay-history-actionbar-btns">';

        if (!$isPlayerSide) {
            $html .= '<button type="button" class="pay-action-btn pay-action-carry" data-action="carry" data-user="' . $playerId . '" data-year="' . $year . '" data-month="' . $month . '" ' . $carryDisabledAttr . ' data-orig-disabled="' . $carryOrigDisabled . '" title="' . htmlspecialchars($carryTitle) . '">
                <span class="pay-action-ic"><i class="fa-solid fa-forward"></i></span> Carry Forward
            </button>';

            $html .= '<button type="button" class="pay-action-btn pay-action-expense" data-action="expense" data-user="' . $playerId . '" data-year="' . $year . '" data-month="' . $month . '" ' . $expenseDisabledAttr . ' data-orig-disabled="' . $origLockDisabled . '" title="' . htmlspecialchars($lockTitle) . '">
                <span class="pay-action-ic"><i class="fa-solid fa-receipt"></i></span> Add Expense
            </button>';
        }

        $html .= '<button type="button" class="pay-action-btn pay-action-pay" data-action="pay" data-user="' . $playerId . '" data-year="' . $year . '" data-month="' . $month . '" ' . $payDisabledAttr . ' data-orig-disabled="' . $origLockDisabled . '" title="' . htmlspecialchars($lockTitle) . '">
            <span class="pay-action-ic"><i class="fa-solid fa-hand-holding-dollar"></i></span> Pay
        </button>';

        if (!$isPlayerSide) {
            $html .= '<button type="button" class="pay-action-btn pay-action-override ' . $overrideClass . '" data-action="override" data-override="' . $overrideState . '" data-user="' . $playerId . '" data-year="' . $year . '" data-month="' . $month . '" ' . $overrideDisabledAttr . ' data-orig-disabled="' . $origOverrideDisabled . '" title="' . htmlspecialchars($overrideTitle) . '">
                <span class="pay-action-ic"><i class="fa-solid ' . $overrideIcon . '"></i></span> Override
            </button>';
        }

        $html .= '</div>'; // .pay-history-actionbar-btns
        $html .= '</div>'; // .pay-history-actionbar

        // Forms (Add Expense & Add Payment)
        $defaultDt = sprintf('%04d-%02d-01T%02d:%02d', $year, $month, (int) date('H'), (int) date('i'));
        $venueOptionsHtml = '';
        foreach ($venues as $v) {
            $venueOptionsHtml .= '<option value="' . htmlspecialchars($v, ENT_QUOTES) . '">' . htmlspecialchars($v) . '</option>';
        }

        if (!$isPlayerSide && !$isEffectivelyLocked) {
            $html .= '<div class="pay-expense-form-wrap" id="payExpenseFormWrap" style="display:none; margin-top:10px;">
                <div class="pay-expense-form-title"><i class="fa-solid fa-receipt"></i> Add Extra Expense</div>
                <form id="payExpenseForm" class="pay-expense-form">
                    <input type="hidden" name="user_id" value="' . $playerId . '">
                    <input type="hidden" name="host_id" value="' . $hostId . '">
                    <input type="hidden" name="year" value="' . $year . '">
                    <input type="hidden" name="month" value="' . $month . '">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <label class="pay-form-label">Venue</label>
                            <select name="venue" class="form-select form-select-sm">' . $venueOptionsHtml . '</select>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="pay-form-label">Type</label>
                            <input type="text" name="type" class="form-control form-control-sm" placeholder="e.g. Feather, Grip, String" value="Expense">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="pay-form-label">Amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="pay-form-label">Date &amp; Time</label>
                            <input type="datetime-local" name="expense_datetime" class="form-control form-control-sm" value="' . $defaultDt . '">
                        </div>
                        <div class="col-md-2 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill" id="btnSubmitExpense">Submit</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelExpense">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>';
        }

        if (!$isEffectivelyLocked) {
            $html .= '<div class="pay-expense-form-wrap" id="payPaymentFormWrap" style="display:none; margin-top:10px;">
                <div class="pay-expense-form-title" style="color:#059669;"><i class="fa-solid fa-hand-holding-dollar"></i> Add Payment</div>
                <form id="payPaymentForm" class="pay-expense-form">
                    <input type="hidden" name="user_id" value="' . $playerId . '">
                    <input type="hidden" name="host_id" value="' . $hostId . '">
                    <input type="hidden" name="year" value="' . $year . '">
                    <input type="hidden" name="month" value="' . $month . '">
                    <input type="hidden" name="is_player_side" value="' . ($isPlayerSide ? '1' : '0') . '">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <label class="pay-form-label">Venue</label>
                            <select name="venue" class="form-select form-select-sm">' . $venueOptionsHtml . '</select>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="pay-form-label">Type</label>
                            <input type="text" name="payment_type" class="form-control form-control-sm" placeholder="e.g. Interac, Cash, Credit Card" value="Interac">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="pay-form-label">Amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="pay-form-label">Date &amp; Time</label>
                            <input type="datetime-local" name="payment_datetime" class="form-control form-control-sm" value="' . $defaultDt . '">
                        </div>
                        <div class="col-md-2 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-sm flex-fill" id="btnSubmitPayment">Submit</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelPayment">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>';
        }

        // Table
        $html .= '<div class="table-responsive pay-table-responsive mt-1">';
        $html .= '<table class="table pay-monthly-table align-middle">';

        // 1. OPENING BALANCE (CARRY FORWARD) SECTION
        $html .= '<thead><tr class="pay-section-header pay-section-header-carry"><th colspan="7"><span class="pay-sec-icon pay-sec-icon-carry"><i class="fa-solid fa-wallet"></i></span> Opening Balance (Carry Forward)</th></tr>';
        $html .= '<tr class="pay-col-header"><th class="pay-col-sn text-center">SN</th><th class="pay-col-date">Date</th><th class="pay-col-venue">Venue</th><th class="pay-col-item">Item</th><th class="pay-col-amount text-end">Amount</th><th class="pay-col-action text-center">Status / Action</th><th class="pay-col-logs text-center">Logs</th></tr></thead>';
        $html .= '<tbody>';

        if ($opening !== null && (float) $opening['amount'] > 0) {
            $opAmt = (float) $opening['amount'];
            $bType = $opening['balance_type'];
            $badgeClass = ($bType === 'DUE') ? 'pay-badge-due' : 'pay-badge-paid';

            $rollbackHtml = '';
            if (!$isPlayerSide && !empty($opening['can_rollback'])) {
                $rollbackHtml = "<button type='button' class='btn btn-danger btn-sm rollbackBtnnn pay-action-rollback-carry' data-user='{$playerId}' data-year='{$year}' data-month='{$month}'>Rollback</button>";
            }

            $html .= '<tr class="pay-row-carry">';
            $html .= '<td class="text-center text-muted">1</td>';
            $html .= '<td><span class="pay-cell-datetime">' . htmlspecialchars($opening['date_time']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($opening['venue']) . '</td>';
            $html .= '<td><span class="pay-item-label">' . htmlspecialchars($opening['label']) . '</span></td>';
            $html .= '<td class="text-end fw-bold pay-cell-amount">' . self::formatMoney($opAmt) . ' <span class="pay-type-tag">(' . htmlspecialchars($bType) . ')</span></td>';
            $html .= '<td class="text-center"><div class="pay-action-group" style="display:inline-flex; align-items:center; justify-content:center; gap:6px;"><span class="badge ' . $badgeClass . '">Opening ' . htmlspecialchars($bType) . '</span>' . $rollbackHtml . '</div></td>';
            $html .= '<td class="text-center text-muted">—</td>';
            $html .= '</tr>';
        } else {
            $html .= '<tr><td colspan="7" class="text-center text-muted py-3 pay-empty-state"><i class="fa-regular fa-folder-open me-1"></i> No carry forward record for this month.</td></tr>';
        }
        $html .= '</tbody>';

        // 2. EVENT COSTS (GAMES & EXPENSES) SECTION
        $html .= '<thead><tr class="pay-section-header pay-section-header-costs"><th colspan="7"><span class="pay-sec-icon pay-sec-icon-costs"><i class="fa-solid fa-gamepad"></i></span> Event Costs (Games &amp; Expenses)</th></tr>';
        $html .= '<tr class="pay-col-header"><th class="pay-col-sn text-center">SN</th><th class="pay-col-date">Date</th><th class="pay-col-venue">Venue</th><th class="pay-col-item">Item</th><th class="pay-col-amount text-end">Amount</th><th class="pay-col-action text-center">Status / Action</th><th class="pay-col-logs text-center">Logs</th></tr></thead>';
        $html .= '<tbody>';

        if (empty($eventCosts)) {
            $html .= '<tr><td colspan="7" class="text-center text-muted py-3 pay-empty-state"><i class="fa-regular fa-folder-open me-1"></i> No game costs or expenses recorded for this month.</td></tr>';
        } else {
            foreach ($eventCosts as $cost) {
                $gid = (int) $cost['game_id'];
                $price = (float) $cost['price'];
                $paid = (float) $cost['paid'];
                $due = (float) $cost['due'];
                $status = (string) $cost['action_status'];
                $pendingId = (int) ($cost['pending_payment_id'] ?? 0);

                $actionHtml = '';
                if ($status === 'Locked') {
                    $actionHtml = '<span class="badge pay-badge-locked"><i class="fa-solid fa-lock"></i> Locked</span>';
                } elseif ($status === 'Pending Carry') {
                    $actionHtml = '<span class="badge pay-badge-pending">Pending Carry</span>';
                } elseif ($status === 'Paid') {
                    $actionHtml = '<span class="badge pay-badge-paid">Paid</span>';
                } elseif ($status === 'Pending Approval') {
                    if ($isPlayerSide) {
                        $actionHtml = '<span class="badge bg-warning text-dark">Pending Approval</span>';
                    } else {
                        $actionHtml = "<div class='pay-action-group' style='display:inline-flex; gap:4px;'>
                            <button type='button' class='btn btn-success btn-sm approveBtnnn' data-id='{$gid}' data-payment-id='{$pendingId}' data-user='{$playerId}' data-year='{$year}' data-month='{$month}'>Approve</button>
                            <button type='button' class='btn btn-danger btn-sm rejectBtnnn' data-id='{$gid}' data-payment-id='{$pendingId}' data-user='{$playerId}' data-year='{$year}' data-month='{$month}'>Reject</button>
                        </div>";
                    }
                } elseif ($status === 'Partially Paid') {
                    $actionHtml = "<div class='d-inline-flex align-items-center gap-1'>
                        <span class='badge bg-info text-dark' style='font-size:0.75rem;'>Paid: " . self::formatMoney($paid) . "</span>
                        <button type='button' class='btn btn-warning btn-sm payBtnnn' data-id='{$gid}' data-user='{$playerId}' data-due='{$due}' data-year='{$year}' data-month='{$month}'>Pay Due (" . self::formatMoney($due) . ")</button>
                    </div>";
                } elseif ($status === 'Rejected') {
                    if ($isPlayerSide) {
                        $actionHtml = "<span class='badge bg-danger text-white me-1' style='font-size:0.75rem;'>Rejected</span><button type='button' class='btn btn-danger btn-sm payBtnnn pay-btn-repay' data-id='{$gid}' data-user='{$playerId}' data-due='{$due}' data-year='{$year}' data-month='{$month}'>Repay</button>";
                    } else {
                        $actionHtml = "<span class='badge bg-danger text-white' style='font-size:0.75rem;'>Rejected</span>";
                    }
                } else { // Unpaid
                    $actionHtml = "<button type='button' class='btn btn-warning btn-sm payBtnnn' data-id='{$gid}' data-user='{$playerId}' data-due='{$due}' data-year='{$year}' data-month='{$month}'>Pay</button>";
                }

                $html .= '<tr class="pay-row-cost">';
                $html .= '<td class="text-center text-muted">' . $cost['sl'] . '</td>';
                $html .= '<td><span class="pay-cell-datetime">' . htmlspecialchars($cost['date_time']) . '</span></td>';
                $html .= '<td>' . htmlspecialchars($cost['venue']) . '</td>';
                $html .= '<td><span class="pay-item-title">' . htmlspecialchars($cost['cost_type']) . '</span></td>';
                $html .= '<td class="text-end fw-semibold pay-cell-amount">' . self::formatMoney($price) . '</td>';
                $html .= '<td class="text-center">' . $actionHtml . '</td>';
                $html .= '<td class="text-center text-muted">—</td>';
                $html .= '</tr>';
            }
        }

        // Subtotal row for Event Costs
        $html .= '<tr class="pay-row-subtotal fw-bold">';
        $html .= '<td colspan="4" class="text-end pay-subtotal-label">Total Event Cost:</td>';
        $html .= '<td class="text-end pay-subtotal-value">' . self::formatMoney((float) ($totals['total_event_cost'] ?? 0)) . '</td>';
        $html .= '<td colspan="2"></td>';
        $html .= '</tr>';
        $html .= '</tbody>';

        // 3. PAYMENTS SECTION
        $html .= '<thead><tr class="pay-section-header pay-section-header-payments"><th colspan="7"><span class="pay-sec-icon pay-sec-icon-payments"><i class="fa-solid fa-money-bill-wave"></i></span> Payments</th></tr>';
        $html .= '<tr class="pay-col-header"><th class="pay-col-sn text-center">SN</th><th class="pay-col-date">Date</th><th class="pay-col-venue">Venue</th><th class="pay-col-item">Item</th><th class="pay-col-amount text-end">Amount</th><th class="pay-col-action text-center">Status / Action</th><th class="pay-col-logs text-center">Logs</th></tr></thead>';
        $html .= '<tbody>';

        if (empty($payments)) {
            $html .= '<tr><td colspan="7" class="text-center text-muted py-3 pay-empty-state"><i class="fa-regular fa-folder-open me-1"></i> No payments recorded for this month.</td></tr>';
        } else {
            foreach ($payments as $p) {
                $pId = (int) $p['id'];
                $pGameId = (int) $p['game_id'];
                $pAmount = (float) $p['amount'];
                $pStatus = (string) $p['status'];

                $actionHtml = '';
                if ($pStatus === 'Y') {
                    $rollbackBtn = '';
                    if (!empty($p['can_rollback'])) {
                        $rollbackBtn = "<button type='button' class='btn btn-danger btn-sm rollbackBtnnn' data-id='{$pGameId}' data-payment-id='{$pId}' data-user='{$playerId}' data-amount='{$pAmount}' data-year='{$year}' data-month='{$month}'>Rollback</button>";
                    }
                    $actionHtml = "<div class='pay-action-group' style='display:inline-flex; align-items:center; gap:6px;'>
                        <span class='badge pay-badge-paid'>Paid</span>
                        {$rollbackBtn}
                    </div>";
                } elseif ($pStatus === 'N') {
                    if ($isPlayerSide) {
                        $actionHtml = "<span class='badge bg-warning text-dark'>Pending Approval</span>";
                    } else {
                        $actionHtml = "<div class='pay-action-group' style='display:inline-flex; align-items:center; gap:4px;'>
                            <span class='badge bg-warning text-dark'>Pending Approval</span>
                            <button type='button' class='btn btn-success btn-sm approveBtnnn' data-id='{$pGameId}' data-payment-id='{$pId}' data-user='{$playerId}' data-year='{$year}' data-month='{$month}'>Approve</button>
                            <button type='button' class='btn btn-danger btn-sm rejectBtnnn' data-id='{$pGameId}' data-payment-id='{$pId}' data-user='{$playerId}' data-year='{$year}' data-month='{$month}'>Reject</button>
                        </div>";
                    }
                } elseif ($pStatus === 'R') {
                    $reinitBtn = '';
                    if (!empty($p['can_reinitiate'])) {
                        $reinitBtn = "<button type='button' class='btn btn-warning btn-sm reinitiateBtnnn' data-payment-id='{$pId}' data-user='{$playerId}' data-host='{$hostId}' data-year='{$year}' data-month='{$month}' style='padding:2px 8px; font-size:0.72rem; margin-left:6px; border-radius:6px;'>Re-initiate</button>";
                    }
                    $actionHtml = "<div class='pay-action-group' style='display:inline-flex; align-items:center; gap:6px;'>
                        <span class='badge bg-danger text-white'>Rejected</span>
                        {$reinitBtn}
                    </div>";
                }

                $logBtn = "<button type='button' class='pay-history-detail-btn' data-payment-id='{$pId}' data-user='{$playerId}' title='View History'><i class='fa-solid fa-clock-rotate-left'></i></button>";

                $html .= '<tr class="pay-row-payment">';
                $html .= '<td class="text-center text-muted">' . $p['sl'] . '</td>';
                $html .= '<td><span class="pay-cell-datetime">' . htmlspecialchars($p['date_time']) . '</span></td>';
                $html .= '<td>' . htmlspecialchars($p['venue']) . '</td>';
                $html .= '<td><span class="pay-payment-type">' . htmlspecialchars($p['payment_type']) . '</span></td>';
                $html .= '<td class="text-end fw-semibold pay-cell-amount text-success">' . self::formatMoney($pAmount) . '</td>';
                $html .= '<td class="text-center">' . $actionHtml . '</td>';
                $html .= '<td class="text-center">' . $logBtn . '</td>';
                $html .= '</tr>';
            }
        }

        // Subtotal row for Payments
        $html .= '<tr class="pay-row-subtotal fw-bold">';
        $html .= '<td colspan="4" class="text-end pay-subtotal-label">Total Payments (Approved):</td>';
        $html .= '<td class="text-end pay-subtotal-value text-success">' . self::formatMoney((float) ($totals['approved_payments'] ?? 0)) . '</td>';
        $html .= '<td colspan="2"></td>';
        $html .= '</tr>';
        $html .= '</tbody>';

        // 4. CLOSING BALANCE SUMMARY ROW
        $closingBal = (float) ($totals['closing_balance'] ?? 0);
        $amountDue = (float) ($totals['amount_due'] ?? 0);
        $balClass = ($closingBal > 0) ? 'text-danger' : (($closingBal < 0) ? 'text-success' : 'text-primary');
        $balLabel = ($closingBal > 0) ? 'Amount Due' : (($closingBal < 0) ? 'Advance Credit' : 'Balanced');
        $footerClass = ($closingBal > 0) ? 'pay-footer-due' : (($closingBal < 0) ? 'pay-footer-advance' : 'pay-footer-balanced');
        $badgeColorClass = ($closingBal > 0) ? 'pay-badge-closing-due' : (($closingBal < 0) ? 'pay-badge-closing-advance' : 'pay-badge-closing-balanced');

        $html .= '<tfoot>';
        $html .= '<tr class="pay-footer-closing ' . $footerClass . ' fw-bold">';
        $html .= '<td colspan="4" class="text-end pay-closing-label">Closing Balance (' . $balLabel . '):</td>';
        $html .= '<td class="text-end pay-closing-amount ' . $balClass . '">' . self::formatMoney(abs($closingBal)) . '</td>';
        $html .= '<td colspan="2" class="text-center"><span class="badge ' . $badgeColorClass . '">' . $balLabel . '</span></td>';
        $html .= '</tr>';
        $html .= '</tfoot>';

        $html .= '</table>';
        $html .= '</div>'; // .table-responsive

        $html .= '</div>'; // .pay-history-wrapper

        return $html;
    }

    public static function renderModalFilterBar(int $playerId, int $selectedYear, int $selectedMonth): string
    {
        if (!function_exists('renderYearOptions') || !function_exists('renderMonthOptions')) {
            require_once __DIR__ . '/../../Helpers/calendar_helper.php';
        }

        $currentYear = (int) date('Y');
        $startYear = min(2024, $selectedYear);
        $endYear = max($currentYear + 2, 2030);

        $html = '<div class="pay-ledger-filter">';
        $html .= '<span class="pay-ledger-filter-title"><i class="fa-solid fa-book-open"></i> Financial Ledger</span>';

        $html .= '<select class="form-select form-select-sm pay-ledger-year" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar" data-user="' . $playerId . '" title="Filter by year">';
        $html .= renderYearOptions($selectedYear, $startYear, $endYear);
        $html .= '</select>';

        $html .= '<select class="form-select form-select-sm pay-ledger-month" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar-days" data-user="' . $playerId . '" title="Filter by month">';
        $html .= renderMonthOptions($selectedMonth, false);
        $html .= '</select>';

        $html .= '<button type="button" class="btn btn-sm pay-ledger-full" data-user="' . $playerId . '" title="Show the complete ledger from joining date"><i class="fa-solid fa-list"></i> Full Ledger</button>';
        $html .= '</div>';
        return $html;
    }

    public static function renderHistoryHtml(array $historyData): string
    {
        $items = $historyData['items'] ?? [];
        $page = (int) ($historyData['page'] ?? 1);
        $totalPages = (int) ($historyData['total_pages'] ?? 1);
        $totalItems = (int) ($historyData['total_items'] ?? 0);
        $playerId = (int) ($historyData['player_id'] ?? 0);
        $pageSize = (int) ($historyData['pageSize'] ?? 20);
        $offset = (int) ($historyData['offset'] ?? (($page - 1) * $pageSize));
        $playerName = $historyData['player_name'] ?? 'Player';
        $firstDate = $historyData['first_date'] ?? '';
        $lastDate = $historyData['last_date'] ?? '';
        $dateRange = ($totalItems > 0 && !empty($firstDate)) ? ($firstDate . ' → ' . $lastDate) : '—';

        $closing = (float) ($historyData['closing_balance'] ?? 0.0);
        $totalDebit = (float) ($historyData['total_debit'] ?? 0.0);
        $totalCredit = (float) ($historyData['total_credit'] ?? 0.0);

        $html = '<div class="pay-history-wrapper">';

        // Header: Player Name + Scope + Back to Financial Ledger button
        $html .= '<div class="pay-ledger-head d-flex align-items-center justify-content-between mb-2">';
        $html .= '<div class="d-flex align-items-center gap-3">';
        $html .= '<span class="pay-ledger-player"><i class="fa-solid fa-user text-teal-600"></i> ' . htmlspecialchars($playerName) . '</span>';
        $html .= '<span class="pay-ledger-scope text-muted"><i class="fa-solid fa-calendar-days me-1"></i> ' . $totalItems . ' entries · ' . htmlspecialchars($dateRange) . '</span>';
        $html .= '</div>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-secondary pay-ledger-back-to-financial" data-user="' . $playerId . '" title="Back to Financial Ledger"><i class="fa-solid fa-arrow-left me-1"></i> Back to Financial Ledger</button>';
        $html .= '</div>';

        // Table
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-bordered pay-ledger-table">';
        $html .= '<thead>';
        $html .= '<tr class="pay-ledger-header">';
        $html .= '<th>Sl.</th>';
        $html .= '<th>Date &amp; Time</th>';
        $html .= '<th>Venue</th>';
        $html .= '<th>Particulars</th>';
        $html .= '<th class="text-end">Debit</th>';
        $html .= '<th class="text-end">Credit</th>';
        $html .= '<th class="text-end">Running Balance</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        if (empty($items)) {
            $html .= '<tr><td colspan="7" class="text-center text-muted py-3">No historical transactions found.</td></tr>';
        } else {
            $kindBadge = [
                'game'         => ['', ''],
                'expense'      => ['', ''],
                'prev_due'     => ['Previous Due', 'k-prevdue'],
                'prev_adv'     => ['Previous Advance', 'k-advance'],
                'carry_settle' => ['Carry Settlement', 'k-settle'],
                'payment'      => ['Payment',      'k-pay'],
            ];

            $i = $offset + 1;
            foreach ($items as $r) {
                $timeStr = (string) ($r['time'] ?? '');
                $dt = htmlspecialchars(($r['date'] ?? '') . ' ' . (strlen($timeStr) >= 5 ? substr($timeStr, 0, 5) : $timeStr));
                $badge = $kindBadge[$r['kind'] ?? 'game'] ?? ['', ''];
                $bal = (float) ($r['running'] ?? 0.0);
                $balClass = ($bal < 0) ? ' pay-ledger-balance-neg' : ' pay-ledger-balance-pos';
                $venue = (!empty($r['venue']) && $r['venue'] !== '—') ? htmlspecialchars($r['venue']) : '—';

                $debitVal = (float) ($r['debit'] ?? 0.0);
                $creditVal = (float) ($r['credit'] ?? 0.0);

                $debitCell  = $debitVal > 0 ? '$' . number_format($debitVal, 2) : '—';
                $creditCell = $creditVal > 0 ? '$' . number_format($creditVal, 2) : '—';
                $balCell    = ($bal < 0 ? '−' : '') . '$' . number_format(abs($bal), 2);

                $badgeHtml = !empty($badge[0]) ? " <span class='pay-ledger-kind {$badge[1]}'>{$badge[0]}</span>" : '';

                $html .= "<tr class='pay-ledger-row'>
                    <td>{$i}</td>
                    <td>{$dt}</td>
                    <td>{$venue}</td>
                    <td><span class='pay-ledger-particulars'>" . htmlspecialchars((string) ($r['particulars'] ?? '')) . "</span>{$badgeHtml}</td>
                    <td class='text-end pay-ledger-debit'>{$debitCell}</td>
                    <td class='text-end pay-ledger-credit'>{$creditCell}</td>
                    <td class='text-end pay-ledger-balance{$balClass}'>{$balCell}</td>
                </tr>";
                $i++;
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        // Summary Cards
        $dueClass = ($closing > 0) ? 'due' : (($closing < 0) ? 'adv' : '');
        $dueLabel = ($closing > 0) ? 'Total Due' : (($closing < 0) ? 'Total Advance' : 'Total Due');
        $dueVal   = ($closing < 0 ? '−' : '') . '$' . number_format(abs($closing), 2);
        $dueSub   = ($closing > 0 ? 'Player owes Host' : ($closing < 0 ? 'Host owes Player' : 'Account Settled'));

        $html .= '<div class="pay-ledger-summary">
            <div class="pay-ledger-summary-item">
                <div class="lbl">TOTAL AMOUNT</div>
                <div class="val">$' . number_format($totalDebit, 2) . '</div>
                <div class="sub">All Debits (events + expenses)</div>
            </div>
            <div class="pay-ledger-summary-item">
                <div class="lbl">TOTAL PAYMENT</div>
                <div class="val">$' . number_format($totalCredit, 2) . '</div>
                <div class="sub">All Credits (payments received)</div>
            </div>
            <div class="pay-ledger-summary-item ' . $dueClass . '">
                <div class="lbl">' . $dueLabel . ' (CLOSING BALANCE)</div>
                <div class="val">' . $dueVal . '</div>
                <div class="sub">' . $dueSub . '</div>
            </div>
        </div>';

        // Pagination Bar
        if ($totalItems > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 px-1 pay-ledger-pagination-container">';
            
            // Rows per page dropdown
            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= '<label class="mb-0 text-muted" style="font-size: 0.8rem; white-space: nowrap;">Rows per page:</label>';
            $html .= '<select class="form-select form-select-sm pay-ledger-pagesize" style="width: auto; height: 31px;" data-user="' . $playerId . '">';
            foreach ([5, 10, 20, 50, 100] as $sz) {
                $sel = ($sz === $pageSize) ? ' selected' : '';
                $html .= "<option value=\"$sz\"$sel>$sz</option>";
            }
            $html .= '</select>';
            $html .= '</div>';

            if ($totalPages > 1) {
                $html .= '<nav class="pay-ledger-pagination"><ul class="pagination pagination-sm justify-content-center mb-0">';
                $prevPage = $page - 1;
                $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">
                    <a class="page-link pay-ledger-page" href="javascript:void(0)" data-user="' . $playerId . '" data-page="' . max(1, $prevPage) . '">‹ Prev</a></li>';
                for ($pg = 1; $pg <= $totalPages; $pg++) {
                    if ($totalPages > 12 && abs($pg - $page) > 4 && $pg !== 1 && $pg !== $totalPages) {
                        if ($pg === 2 || $pg === $totalPages - 1) {
                            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
                        }
                        continue;
                    }
                    $active = ($pg === $page) ? ' active' : '';
                    $html .= '<li class="page-item' . $active . '">
                        <a class="page-link pay-ledger-page" href="javascript:void(0)" data-user="' . $playerId . '" data-page="' . $pg . '">' . $pg . '</a></li>';
                }
                $nextPage = $page + 1;
                $html .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">
                    <a class="page-link pay-ledger-page" href="javascript:void(0)" data-user="' . $playerId . '" data-page="' . min($totalPages, $nextPage) . '">Next ›</a></li>';
                $html .= '</ul></nav>';
            }
            
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function renderFilterBar(int $playerId, int $year, int $month): string
    {
        if (!function_exists('renderYearOptions') || !function_exists('renderMonthOptions')) {
            require_once __DIR__ . '/../../Helpers/calendar_helper.php';
        }

        $currentYear = (int) date('Y');
        $startYear = min(2024, $year);
        $endYear = max($currentYear + 2, 2030);

        $filterBar = '<div class="pay-ledger-filter">';
        $filterBar .= '<span class="pay-ledger-filter-title"><i class="fa-solid fa-book-open"></i> Financial Ledger</span>';

        // Year select using calendar_helper and casa-dropdown
        $filterBar .= '<select class="form-select form-select-sm pay-ledger-year" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar" data-user="' . $playerId . '" title="Filter by year">';
        $filterBar .= renderYearOptions($year, $startYear, $endYear);
        $filterBar .= '</select>';

        // Month select using calendar_helper and casa-dropdown
        $filterBar .= '<select class="form-select form-select-sm pay-ledger-month" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar-days" data-user="' . $playerId . '" title="Filter by month">';
        $filterBar .= renderMonthOptions($month, false);
        $filterBar .= '</select>';

        $filterBar .= '<button type="button" class="btn btn-sm pay-ledger-full" data-user="' . $playerId . '" title="Show the complete ledger history"><i class="fa-solid fa-list"></i> Full Ledger</button>';
        $filterBar .= '</div>';

        return $filterBar;
    }

    public static function renderEmptyPaymentListHtml(string $searchQuery = ''): string
    {
        if ($searchQuery !== '') {
            return "<p class='text-center py-3 text-muted'><i class='bi bi-search me-1'></i>No players found matching \"" . htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') . "\".</p>";
        }
        return "<p class='text-center py-3 text-muted'>No players found under this host.</p>";
    }

    public static function sortIcon(string $col, string $currentSortBy, string $currentSortDir): string
    {
        if ($col !== $currentSortBy) {
            return '<span class="text-muted ms-1" style="font-size:0.7rem;">⇅</span>';
        }
        return $currentSortDir === 'asc'
            ? '<span class="text-warning ms-1" style="font-size:0.7rem;">▲</span>'
            : '<span class="text-warning ms-1" style="font-size:0.7rem;">▼</span>';
    }

    public static function renderPaymentListTableHtml(
        array $pagePlayers,
        Pagination|int $paginationOrTotal,
        mixed $arg3 = 1,
        mixed $arg4 = 10,
        mixed $arg5 = 'name',
        mixed $arg6 = 'asc',
        mixed $arg7 = 0,
        mixed $arg8 = [5, 10, 20, 50, 100]
    ): string {
        if ($paginationOrTotal instanceof Pagination) {
            $pagination = $paginationOrTotal;
            $sortBy = is_string($arg3) ? $arg3 : 'name';
            $sortDir = is_string($arg4) ? $arg4 : 'asc';
            $allowedPageSizes = is_array($arg5) ? $arg5 : (is_array($arg8) ? $arg8 : [5, 10, 20, 50, 100]);
        } else {
            $totalPlayers = (int) $paginationOrTotal;
            $page = (int) $arg3;
            $perPage = (int) $arg4;
            $sortBy = is_string($arg5) ? $arg5 : 'name';
            $sortDir = is_string($arg6) ? $arg6 : 'asc';
            $offset = (int) $arg7;
            $allowedPageSizes = is_array($arg8) ? $arg8 : [5, 10, 20, 50, 100];
            $pagination = Pagination::make($totalPlayers, $page, $perPage);
        }

        $totalPlayers = $pagination->getTotal();
        $page = $pagination->getCurrentPage();
        $perPage = $pagination->getPerPage();
        $offset = $pagination->getOffset();

        $html = '<div class="table-responsive" style="font-size:0.75rem;">';
        $html .= '<table class="table table-success table-striped table-bordered table-sm text-nowrap align-middle mb-0">';
        $html .= '<thead class="table-dark text-center">';
        $html .= '<tr>';
        $html .= '<th>Sl. No.</th>';
        $html .= '<th>Profile</th>';
        $html .= "<th class='hp-sort-col' data-sort-by='name' style='cursor:pointer;'>Player Name " . self::sortIcon('name', $sortBy, $sortDir) . "</th>";
        $html .= "<th>Email/Phone</th>";
        $html .= "<th>IS PREMIUM</th>";
        $html .= "<th class='hp-sort-col' data-sort-by='games'  style='cursor:pointer;'>Total Game " . self::sortIcon('games', $sortBy, $sortDir) . "</th>";
        $html .= "<th class='hp-sort-col' data-sort-by='amount' style='cursor:pointer;'>Total Amount " . self::sortIcon('amount', $sortBy, $sortDir) . "</th>";
        $html .= "<th class='hp-sort-col' data-sort-by='paid'   style='cursor:pointer;'>Total Payment " . self::sortIcon('paid', $sortBy, $sortDir) . "</th>";
        $html .= "<th class='hp-sort-col' data-sort-by='due'    style='cursor:pointer;'>Total Due " . self::sortIcon('due', $sortBy, $sortDir) . "</th>";
        $html .= "<th>Action</th>";
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $currencyCode = (string) ($filters['currency'] ?? 'CAD');
        $currency = function_exists('formatPayAmount')
            ? preg_replace('/[0-9.,\s]/', '', formatPayAmount(0, $currencyCode))
            : '$';
        if ($currency === '') {
            $currency = '$';
        }
        foreach ($pagePlayers as $idx => $p) {
            $data = $p['data'] ?? [];
            $userId = (int) ($data['ID'] ?? 0);
            $profilePic = !empty($data['PROFILE_IMAGE']) ? '../profile_img/' . htmlspecialchars((string) $data['PROFILE_IMAGE'], ENT_QUOTES, 'UTF-8') : '../assets/images/profile.jpg';
            $isPremium = (isset($data['PREMIUM']) && strtoupper((string) $data['PREMIUM']) === 'Y');
            $premiumChecked = $isPremium ? 'checked' : '';
            $switchId = 'premium-switch-' . $userId;
            $sl = $offset + $idx + 1;
            $name = htmlspecialchars((string) ($data['NAME'] ?? ''), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars((string) ($data['EMAIL'] ?? ''), ENT_QUOTES, 'UTF-8');
            $whatsapp = htmlspecialchars((string) ($data['WHATSAPP_NUMBER'] ?? ''), ENT_QUOTES, 'UTF-8');
            $gameCount = (int) ($p['gameCount'] ?? 0);
            $totalAmount = (float) ($p['totalAmount'] ?? 0.0);
            $totalPaid = (float) ($p['totalPaid'] ?? 0.0);
            $totalDue = (float) ($p['totalDue'] ?? 0.0);

            $dueHtml = $totalDue > 0
                ? "<span class='text-danger fw-semibold'>{$currency} " . number_format($totalDue, 2) . "</span>"
                : "<span class='text-success fw-semibold'>{$currency} " . number_format($totalDue, 2) . "</span>";

            $html .= "<tr>
                <th scope='row'>{$sl}</th>
                <td><div class='profile_pic'><img src='{$profilePic}' class='img-fluid' alt='profile pic' /></div></td>
                <td><strong>{$name}</strong></td>
                <td>
                    <span style='color:#1a73e8;'>{$email}</span><br>
                    <span style='color:#34a853;'>{$whatsapp}</span>
                </td>
                <td>
                    <div class='form-check form-switch mb-0'>
                        <input
                            class='form-check-input premium-switch'
                            type='checkbox'
                            id='{$switchId}'
                            data-user-id='{$userId}'
                            {$premiumChecked}
                            style='cursor:pointer'
                        >
                    </div>
                </td>
                <td class='text-center'>{$gameCount}</td>
                <td>{$currency} " . number_format($totalAmount, 2) . "</td>
                <td>{$currency} " . number_format($totalPaid, 2) . "</td>
                <td>{$dueHtml}</td>
                <td><button type='button' class='playPaymentModal_open btn btn-primary btn-sm' data-id='{$userId}'>View Ledger</button></td>
            </tr>";
        }

        $html .= '</tbody></table></div>';

        // Footer: count info + generic pagination via unified Pagination helper
        $html .= $pagination->renderFooter([
            'item_name' => 'player' . ($totalPlayers !== 1 ? 's' : ''),
            'show_per_page' => true,
            'per_page_options' => $allowedPageSizes,
            'per_page_select_id' => 'hp-pagesize',
            'per_page_label' => 'Rows per page:',
            'ul_class' => 'hp-pagination',
            'is_ajax' => true,
            'size' => 'sm',
            'justify' => '',
            'show_first_last' => false,
            'show_prev_next' => true,
            'prev_text' => '‹',
            'next_text' => '›',
            'on_each_side' => 2,
            'data_attributes' => [
                'sort-by' => $sortBy,
                'sort-dir' => $sortDir,
            ],
        ]);

        return $html;
    }
}


