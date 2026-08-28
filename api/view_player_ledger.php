<?php
/**
 * Player Financial Ledger (host-side).
 *
 * NEW read-only feature ("View Ledger" button).
 *
 * POST: user_id (required), year, month, page
 *
 * Two modes:
 *   1. FILTERED (year + month both > 0):
 *        Renders the EXISTING month history UI (Quick Actions, Carry/Lock
 *        status, monthly calculations) by delegating 100% to
 *        renderPlayerPayHtml() — no changes to existing logic.
 *   2. FULL LEDGER (default, no filters):
 *        One continuous financial statement from the player's joining date
 *        under this host until today:
 *          Date & Time | Venue | Particulars | Debit | Credit | Running Balance
 *        - Chronological ASC; running balance updates after EVERY row.
 *        - Carry records (PreviousDue events, "Carry Forward from %" expenses,
 *          GAME_ID=0 carry payments) are shown INLINE as
 *          "Previous Due" / "Previous Advance" / "Carry Settlement" rows —
 *          NO separate "Opening Balance (Carry Forward)" section.
 *        - Server-side pagination: only displayed rows change; final totals
 *          are always computed from the COMPLETE ledger.
 *        - Summary at bottom: Total Amount, Total Payment,
 *          Total Due (if player owes host) or Total Advance (if host owes player).
 *        - Read-only: no Quick Actions, no lock/pending banners, no action column.
 *
 * CRITICAL: must NOT change any existing DB logic, queries, workflows, or
 * the $(".patmentTb").html() reload contract.
 */

session_start();
include('dbConnection.php');
require_once __DIR__ . '/helpers/ledger_helper.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = (int) ($_POST['user_id'] ?? 0);
    $year    = (int) ($_POST['year']  ?? 0);
    $month   = (int) ($_POST['month'] ?? 0);
    $page    = max(1, (int) ($_POST['page'] ?? 1));

    $host_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if (isset($_POST['host_id']) && (int)$_POST['host_id'] > 0) {
        $host_id = (int)$_POST['host_id'];
    } elseif (isset($_GET['host_id']) && (int)$_GET['host_id'] > 0) {
        $host_id = (int)$_GET['host_id'];
    }

    // Shared renderer — gives us formatPayAmount() and (for filtered mode)
    // the existing month-history renderer. function_exists-guarded, safe.
    require_once __DIR__ . '/render_player_pay_html.php';

    // ── Shared filter bar (Year / Month selects + Full Ledger button) ──
    $filterBar = '<div class="pay-ledger-filter">';
    $filterBar .= '<span class="pay-ledger-filter-title"><i class="fa-solid fa-book-open"></i> Financial Ledger</span>';
    $filterBar .= '<select class="form-select form-select-sm pay-ledger-year" data-user="' . $user_id . '" title="Filter by year (with a month to open that month\'s history)">';
    $filterBar .= '<option value="">Year</option>';
    for ($y = 2025; $y <= (int) date('Y'); $y++) {
        $sel = ($y === $year) ? ' selected' : '';
        $filterBar .= "<option value=\"$y\"$sel>$y</option>";
    }
    $filterBar .= '</select>';
    $filterBar .= '<select class="form-select form-select-sm pay-ledger-month" data-user="' . $user_id . '" title="Filter by month">';
    $filterBar .= '<option value="">Month</option>';
    for ($m = 1; $m <= 12; $m++) {
        $sel = ($m === $month) ? ' selected' : '';
        $filterBar .= "<option value=\"$m\"$sel>" . date('M', mktime(0, 0, 0, $m, 1)) . "</option>";
    }
    $filterBar .= '</select>';
    $filterBar .= '<button type="button" class="btn btn-sm pay-ledger-full" data-user="' . $user_id . '" title="Show the complete ledger from joining date"><i class="fa-solid fa-list"></i> Full Ledger</button>';
    $filterBar .= '</div>';

    if ($user_id <= 0) {
        echo $filterBar . '<p class="text-center text-muted py-2" style="font-size:0.8rem; margin:0;">No Record(s)</p>';
        mysqli_close($conn);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════
    // MODE 1 — FILTERED: Year + Month → reuse the existing Month History UI
    // ══════════════════════════════════════════════════════════════════
    if ($year > 0 && $month > 0) {
        echo $filterBar . renderPlayerPayHtml($conn, $user_id, $year, $month);
        mysqli_close($conn);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════
    // MODE 2 — FULL LEDGER (default): joining date → today, read-only
    // ══════════════════════════════════════════════════════════════════

    // Player name (header context)
    $playerName = '';
    $nameRes = mysqli_query($conn, "SELECT NAME FROM ca_users WHERE ID = $user_id LIMIT 1");
    if ($nameRes && $nameRow = mysqli_fetch_assoc($nameRes)) {
        $playerName = $nameRow['NAME'];
    }

    // Build the chronological row set (ALL records, incl. carry records).
    $rows = [];

    // --- Games (ALL categories — PreviousDue / "Carry Forward from %" included) ---
    $gamesQuery = "
        SELECT
            cg.GAME_ID,
            cg.PRICE,
            cg.CURRENCY,
            ce.EVENT_DATE,
            ce.EVENT_TIME,
            ce.EVENT_VENUE,
            ce.EVENT_CATEGORY
        FROM ca_gamejoin cg
        INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
        WHERE cg.USER_ID = '$user_id'
            AND cg.HOST_ID = '$host_id'
            AND cg.STATUS = 'Y'
            AND ce.STATUS = 'Completed'
        ORDER BY ce.EVENT_DATE ASC, ce.EVENT_TIME ASC
    ";
    $gamesResult = mysqli_query($conn, $gamesQuery);
    if ($gamesResult) {
        while ($g = mysqli_fetch_assoc($gamesResult)) {
            $cat = $g['EVENT_CATEGORY'];
            $isCarry = ($cat === 'PreviousDue' || (is_string($cat) && strpos($cat, 'Carry Forward from ') === 0));
            if ($isCarry) continue;
            $rows[] = [
                'date'  => $g['EVENT_DATE'],
                'time'  => $g['EVENT_TIME'],
                'venue' => $g['EVENT_VENUE'],
                'particulars' => ($cat !== null && $cat !== '') ? $cat : 'Game',
                'kind'  => 'game',
                'debit' => (float) $g['PRICE'],
                'credit' => 0.0,
                'currency' => $g['CURRENCY'],
            ];
        }
    }

    // --- Expenses (ALL types — carry records included) ---
    $expenseQuery = "
        SELECT
            ID,
            VENUE,
            TYPE,
            AMOUNT,
            CURRENCY,
            EXPENSE_DATE,
            EXPENSE_TIME
        FROM ca_expense
        WHERE USER_ID = '$user_id'
            AND HOST_ID = '$host_id'
            AND STATUS = 'Y'
        ORDER BY EXPENSE_DATE ASC, EXPENSE_TIME ASC
    ";
    $expenseResult = mysqli_query($conn, $expenseQuery);
    if ($expenseResult) {
        while ($e = mysqli_fetch_assoc($expenseResult)) {
            $type = $e['TYPE'];
            $isCarry = ($type === 'PreviousDue' || (is_string($type) && strpos($type, 'Carry Forward from ') === 0));
            if ($isCarry) continue;
            $rows[] = [
                'date'  => $e['EXPENSE_DATE'],
                'time'  => $e['EXPENSE_TIME'],
                'venue' => $e['VENUE'],
                'particulars' => ($type !== null && $type !== '') ? $type : 'Expense',
                'kind'  => 'expense',
                'debit' => (float) $e['AMOUNT'],
                'credit' => 0.0,
                'currency' => ($e['CURRENCY'] !== null && $e['CURRENCY'] !== '') ? $e['CURRENCY'] : 'CAD',
            ];
        }
    }

    // --- Payments (ALL — incl. GAME_ID=0 carry payments and carry settlements) ---
    $paymentsQuery = "
        SELECT
            p.ID,
            p.GAME_ID,
            p.AMOUNT,
            p.PAYMENT_DATE,
            p.PAYMENT_TIME,
            p.PAYMENT_TYPE,
            p.STATUS,
            COALESCE(e.EVENT_VENUE, ex.VENUE, '') AS VENUE,
            e.EVENT_CATEGORY,
            ex.TYPE AS EXPENSE_TYPE
        FROM ca_payment p
        LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
        LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
        WHERE p.USER_ID = '$user_id'
            AND p.STATUS = 'Y'
            AND (
                (p.GAME_ID > 0 AND e.HOST_ID = '$host_id')
                OR (p.GAME_ID < 0 AND ex.HOST_ID = '$host_id')
                OR (p.GAME_ID = 0 AND p.REVIEWED_BY = '$host_id')
            )
        ORDER BY p.PAYMENT_DATE ASC, p.PAYMENT_TIME ASC
    ";
    $paymentsResult = mysqli_query($conn, $paymentsQuery);
    if ($paymentsResult) {
        while ($p = mysqli_fetch_assoc($paymentsResult)) {
            $gameId = (int) $p['GAME_ID'];
            $pType = $p['PAYMENT_TYPE'];
            $evCat = $p['EVENT_CATEGORY'];
            $exType = $p['EXPENSE_TYPE'];

            // Previous Advance = carry payment record (GAME_ID = 0)
            $isCarryPay = ($gameId === 0
                && ($pType === 'Carry' || (is_string($pType) && strpos($pType, 'Carry Forward from ') === 0)));

            if ($isCarryPay) continue;

            // Carry Settlement = real payment made against a carry record
            $isSettle = (($gameId > 0 && ($evCat === 'PreviousDue' || (is_string($evCat) && strpos($evCat, 'Carry Forward from ') === 0)))
                 || ($gameId < 0 && ($exType === 'PreviousDue' || (is_string($exType) && strpos($exType, 'Carry Forward from ') === 0))));

            $kind = $isSettle ? 'carry_settle' : 'payment';

            $rows[] = [
                'date'  => $p['PAYMENT_DATE'],
                'time'  => $p['PAYMENT_TIME'],
                'venue' => ($p['VENUE'] !== '') ? $p['VENUE'] : '—',
                'particulars' => ($pType !== null && $pType !== '') ? $pType : 'Payment',
                'kind'  => $kind,
                'debit' => 0.0,
                'credit' => (float) $p['AMOUNT'],
                'currency' => 'CAD',
            ];
        }
    }

    // Chronological sort: date ASC, time ASC (carry markers share their date/time)
    usort($rows, function ($a, $b) {
        $cmp = strcmp((string) $a['date'], (string) $b['date']);
        if ($cmp !== 0) return $cmp;
        return strcmp((string) $a['time'], (string) $b['time']);
    });

    // Find the earliest month/year transaction to retrieve opening balance
    $earliestYear = 9999;
    $earliestMonth = 12;
    foreach ($rows as $r) {
        $ty = (int)date('Y', strtotime($r['date']));
        $tm = (int)date('n', strtotime($r['date']));
        if ($ty < $earliestYear) {
            $earliestYear = $ty;
            $earliestMonth = $tm;
        } elseif ($ty === $earliestYear && $tm < $earliestMonth) {
            $earliestMonth = $tm;
        }
    }

    $startBal = 0.0;
    if ($earliestYear !== 9999) {
        $opQuery = mysqli_query($conn, "SELECT opening_balance, balance_type FROM host_player_carry_forward WHERE host_id = $host_id AND player_id = $user_id AND carry_month = $earliestMonth AND carry_year = $earliestYear LIMIT 1");
        if ($opQuery && $opRow = mysqli_fetch_assoc($opQuery)) {
            $opAmt = (float)$opRow['opening_balance'];
            $startBal = ($opRow['balance_type'] === 'DUE') ? $opAmt : -$opAmt;
        }
    }

    // ── Running balance + totals over the COMPLETE ledger ──
    $balance     = $startBal;
    foreach ($rows as &$r) {
        $balance += $r['debit'] - $r['credit'];
        $r['running'] = $balance;
    }
    unset($r);

    $summary = calculateLedgerSummary($conn, $user_id, $host_id);
    $totalDebit  = $summary['totalExpense'] - $summary['openingDue'];
    $totalCredit = $summary['totalPaid'];
    $closing     = $summary['balance'];

    $totalRows  = count($rows);
    $allowedPageSizes = [5, 10, 20, 50, 100];
    $perPage = (int) ($_POST['pageSize'] ?? 20);
    if (!in_array($perPage, $allowedPageSizes)) {
        $perPage = 20;
    }
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset  = ($page - 1) * $perPage;
    $pageRows = array_slice($rows, $offset, $perPage);

    // ── Render ──
    $html = $filterBar;

    $html .= '<div class="pay-ledger-head">
        <span class="pay-ledger-player"><i class="fa-solid fa-user"></i> ' . htmlspecialchars($playerName !== '' ? $playerName : 'Player') . '</span>
        <span class="pay-ledger-scope"><i class="fa-solid fa-calendar-days"></i> ' . $totalRows . ' entries · ' . ($totalRows > 0 ? $rows[0]['date'] . ' → ' . $rows[$totalRows - 1]['date'] : '—') . '</span>
    </div>';

    if ($totalRows === 0) {
        $html .= '<p class="text-center text-muted py-2" style="font-size:0.8rem; margin:0;">No Record(s)</p>';
        echo $html;
        mysqli_close($conn);
        exit;
    }

    $html .= '<div class="table-responsive"><table class="table table-bordered pay-ledger-table">
        <thead>
            <tr class="pay-ledger-header">
                <th>Sl.</th>
                <th>Date &amp; Time</th>
                <th>Venue</th>
                <th>Particulars</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
                <th class="text-end">Running Balance</th>
            </tr>
        </thead>
        <tbody>';

    $kindBadge = [
        'game'         => ['Event Cost',   'k-event'],
        'expense'      => ['Event Cost',   'k-event'],
        'prev_due'     => ['Previous Due', 'k-prevdue'],
        'prev_adv'     => ['Previous Advance', 'k-advance'],
        'carry_settle' => ['Carry Settlement', 'k-settle'],
        'payment'      => ['Payment',      'k-pay'],
    ];

    $i = $offset + 1;
    foreach ($pageRows as $r) {
        $dt = $r['date'] . ' ' . (strlen((string) $r['time']) >= 5 ? substr($r['time'], 0, 5) : $r['time']);
        $badge = $kindBadge[$r['kind']] ?? ['', ''];
        $balClass = ($r['running'] < 0) ? ' pay-ledger-balance-neg' : ' pay-ledger-balance-pos';
        $venue = ($r['venue'] !== null && $r['venue'] !== '') ? htmlspecialchars($r['venue']) : '—';

        $debitCell  = $r['debit']  > 0 ? formatPayAmount($r['debit'],  $r['currency']) : '—';
        $creditCell = $r['credit'] > 0 ? formatPayAmount($r['credit'], $r['currency']) : '—';
        $balCell    = ($r['running'] < 0 ? '−' : '') . formatPayAmount(abs($r['running']), 'CAD');

        $html .= "<tr class='pay-ledger-row'>
                <td>{$i}</td>
                <td>{$dt}</td>
                <td>{$venue}</td>
                <td><span class='pay-ledger-particulars'>" . htmlspecialchars($r['particulars']) . "</span>
                    <span class='pay-ledger-kind {$badge[1]}'>{$badge[0]}</span></td>
                <td class='text-end pay-ledger-debit'>{$debitCell}</td>
                <td class='text-end pay-ledger-credit'>{$creditCell}</td>
                <td class='text-end pay-ledger-balance{$balClass}'>{$balCell}</td>
            </tr>";
        $i++;
    }

    $html .= '</tbody></table></div>';

    // ── Summary (from ALL ledger records, not the visible page) ──
    $dueClass = ($closing > 0) ? 'due' : (($closing < 0) ? 'adv' : '');
    $dueLabel = ($closing > 0) ? 'Total Due' : (($closing < 0) ? 'Total Advance' : 'Total Due');
    $dueVal   = ($closing < 0 ? '−' : '') . formatPayAmount(abs($closing), 'CAD');

    $html .= '<div class="pay-ledger-summary">
        <div class="pay-ledger-summary-item">
            <div class="lbl">Total Amount</div>
            <div class="val">' . formatPayAmount($totalDebit, 'CAD') . '</div>
            <div class="sub">All Debits (events + expenses)</div>
        </div>
        <div class="pay-ledger-summary-item">
            <div class="lbl">Total Payment</div>
            <div class="val">' . formatPayAmount($totalCredit, 'CAD') . '</div>
            <div class="sub">All Credits (payments received)</div>
        </div>
        <div class="pay-ledger-summary-item ' . $dueClass . '">
            <div class="lbl">' . $dueLabel . ' (Closing Balance)</div>
            <div class="val">' . $dueVal . '</div>
            <div class="sub">' . ($closing > 0 ? 'Player owes Host' : ($closing < 0 ? 'Host owes Player' : 'Account Settled')) . '</div>
        </div>
    </div>';

    // ── Pagination (only affects displayed rows) ──
    if ($totalRows > 0) {
        $html .= '<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 px-1 pay-ledger-pagination-container">';
        
        // Rows per page dropdown
        $html .= '<div class="d-flex align-items-center gap-2">';
        $html .= '<label class="mb-0 text-muted" style="font-size: 0.8rem; white-space: nowrap;">Rows per page:</label>';
        $html .= '<select class="form-select form-select-sm pay-ledger-pagesize" style="width: auto; height: 31px;" data-user="' . $user_id . '">';
        foreach ($allowedPageSizes as $sz) {
            $sel = ($sz === $perPage) ? ' selected' : '';
            $html .= "<option value=\"$sz\"$sel>$sz</option>";
        }
        $html .= '</select>';
        $html .= '</div>';

        if ($totalPages > 1) {
            $html .= '<nav class="pay-ledger-pagination"><ul class="pagination pagination-sm justify-content-center mb-0">';
            $prevPage = $page - 1;
            $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">
                <a class="page-link pay-ledger-page" href="javascript:void(0)" data-user="' . $user_id . '" data-page="' . max(1, $prevPage) . '">‹ Prev</a></li>';
            for ($pg = 1; $pg <= $totalPages; $pg++) {
                if ($totalPages > 12 && abs($pg - $page) > 4 && $pg !== 1 && $pg !== $totalPages) {
                    if ($pg === 2 || $pg === $totalPages - 1) {
                        $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    }
                    continue;
                }
                $active = ($pg === $page) ? ' active' : '';
                $html .= '<li class="page-item' . $active . '">
                    <a class="page-link pay-ledger-page" href="javascript:void(0)" data-user="' . $user_id . '" data-page="' . $pg . '">' . $pg . '</a></li>';
            }
            $nextPage = $page + 1;
            $html .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">
                <a class="page-link pay-ledger-page" href="javascript:void(0)" data-user="' . $user_id . '" data-page="' . min($totalPages, $nextPage) . '">Next ›</a></li>';
            $html .= '</ul></nav>';
        }
        
        $html .= '</div>';
    }

    echo $html;

    mysqli_close($conn);
}
