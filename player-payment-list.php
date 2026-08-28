<?php
// DB connection ($conn) is already provided by inner-header.php
require_once __DIR__ . '/api/helpers/ledger_helper.php';

$currentDate = date('Y-m-d');
$currentTime = date('H:i');

$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)

$select_host = mysqli_query($conn, "select * from ca_users where USERTYPE!='Player' and LOG_STATUS='Y' and DEL_STATUS='N'");
$check_player = mysqli_query($conn, "select * from ca_users where ID='" . $_SESSION['user_id'] . "'");
$fetch_player = mysqli_fetch_assoc($check_player);
$premiumStatus = $fetch_player['PREMIUM'];

?>
<?php if ($premiumStatus != 'Y') { ?>
    <p style="color:red; font-weight:bold;text-align:center">Contact admin to enable the premium account</p>
<?php } ?>
<?php
// Dynamic DB fetch
$hostsOptions = [];
$selectedClubName = 'Unknown Club';
while ($fetchUser = mysqli_fetch_assoc($select_host)) {
    $hostsOptions[] = $fetchUser;
    if ($fetchUser['ID'] == $host_id) {
        $selectedClubName = $fetchUser['NAME'];
    }
}

$player_id = $_SESSION['user_id'];

// 1. Calculate overall summary cards using the shared helper (filtered by current Host ID)
$summary = calculateLedgerSummary($conn, $player_id, $host_id);
$totalGames = $summary['games'];
$totalExpense = $summary['totalExpense'];
$totalPaid = $summary['totalPaid'];
$balance = $summary['balance'];

$qHosts = mysqli_query($conn, "
    SELECT COUNT(DISTINCT ce.HOST_ID) as total_hosts
    FROM ca_gamejoin cg
    INNER JOIN ca_events ce ON cg.GAME_ID = ce.ID
    WHERE cg.USER_ID = '$player_id'
      AND ce.HOST_ID = '$host_id'
      AND cg.STATUS = 'Y'
      AND ce.STATUS = 'Completed'
      AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
");
$rHosts = mysqli_fetch_assoc($qHosts);
$totalHosts = (int) ($rHosts['total_hosts'] ?? 0);

$overviewData = [
    'totalGames' => $totalGames,
    'totalHosts' => $totalHosts,
    'totalExpense' => $totalExpense,
    'totalPaid' => $totalPaid,
    'balance' => $balance
];

$gamesQuery = mysqli_query($conn, "
    SELECT 
        cg.ID AS GAME_JOIN_ID, 
        cg.USER_ID, 
        cg.GAME_ID, 
        cg.PRICE, 
        cg.CURRENCY, 
        cg.STATUS AS GAME_JOIN_STATUS, 
        cg.CREATED_AT AS GAME_JOIN_CREATED_AT, 
        ce.ID AS EVENT_ID, 
        ce.HOST_ID,
        ce.HOST_NAME, 
        ce.EVENT_DATE, 
        ce.EVENT_TIME, 
        ce.EVENT_VENUE, 
        ce.EVENT_COST AS EVENT_PRICE, 
        ce.STATUS AS EVENT_STATUS,
        ce.EVENT_CATEGORY
    FROM ca_gamejoin AS cg 
    INNER JOIN ca_events AS ce ON cg.GAME_ID = ce.ID 
    WHERE cg.USER_ID = '$player_id' 
    AND ce.HOST_ID = '$host_id' 
    AND cg.STATUS = 'Y' 
    AND ce.STATUS = 'Completed'
    AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
    ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
");

$dbTransactions = [];
$hostMap = [];

if ($gamesQuery && mysqli_num_rows($gamesQuery) > 0) {
    while ($row = mysqli_fetch_assoc($gamesQuery)) {
        $game_id = $row['GAME_ID'];
        $price = (float) $row['PRICE'];
        
        // Fetch approved payments for this game (Status = Y)
        $payQuery = mysqli_query($conn, "SELECT SUM(AMOUNT) as Total FROM ca_payment WHERE USER_ID='$player_id' AND GAME_ID='$game_id' AND STATUS='Y'");
        $payRow = mysqli_fetch_assoc($payQuery);
        $paid = (float) ($payRow['Total'] ?? 0.0);
        
        $dueAmount = $price - $paid;
        
        // Determine status for the transaction row
        if ($paid <= 0) {
            $pendingQuery = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM ca_payment WHERE USER_ID='$player_id' AND GAME_ID='$game_id' AND STATUS='N'");
            $pendingRow = mysqli_fetch_assoc($pendingQuery);
            $hasPending = ((int)$pendingRow['cnt']) > 0;
            $status = $hasPending ? 'PARTIAL' : 'UNPAID';
        } elseif ($paid < $price) {
            $status = 'PARTIAL';
        } else {
            $status = 'PAID';
        }
        
        $dbTransactions[] = [
            'id' => $row['GAME_JOIN_ID'],
            'gameId' => $row['GAME_ID'],
            'hostId' => $row['HOST_ID'],
            'hostName' => $row['HOST_NAME'],
            'date' => $row['EVENT_DATE'] . 'T' . $row['EVENT_TIME'],
            'venue' => $row['EVENT_VENUE'],
            'type' => ($row['EVENT_CATEGORY'] !== '') ? $row['EVENT_CATEGORY'] : 'Game',
            'amount' => $price,
            'status' => $status,
            'paidAmount' => $paid,
            'currency' => $row['CURRENCY']
        ];
        
        // Aggregate by host
        $hostId = $row['HOST_ID'];
        if (!isset($hostMap[$hostId])) {
            $hostMap[$hostId] = [
                'id' => $hostId,
                'name' => $row['HOST_NAME'],
                'games' => 0,
                'expense' => 0.0,
                'paid' => 0.0,
                'due' => 0.0,
                'lastGame' => $row['EVENT_DATE']
            ];
        }
        if (strtotime($row['EVENT_DATE']) > strtotime($hostMap[$hostId]['lastGame'])) {
            $hostMap[$hostId]['lastGame'] = $row['EVENT_DATE'];
        }
    }
}

foreach ($hostMap as $hId => &$hData) {
    $hSummary = calculateLedgerSummary($conn, $player_id, $hId);
    $hData['games'] = $hSummary['games'];
    $hData['expense'] = $hSummary['totalExpense'];
    $hData['paid'] = $hSummary['totalPaid'];
    $hData['due'] = $hSummary['totalDue'];
}
unset($hData);
$hostSummaries = array_values($hostMap);

// Fetch all payment records for history with reviewer info
$payHistoryQuery = mysqli_query($conn, "
    SELECT p.ID, p.GAME_ID, p.PAYMENT_DATE, p.PAYMENT_TIME, p.PAYMENT_TYPE, p.DETAILS, p.AMOUNT, p.STATUS,
           p.REJECTION_REASON, p.REVIEWED_AT, u.NAME AS REVIEWED_BY_NAME
    FROM ca_payment p
    LEFT JOIN ca_users u ON p.REVIEWED_BY = u.ID
    WHERE p.USER_ID = '$player_id'
    ORDER BY p.PAYMENT_DATE DESC, p.PAYMENT_TIME DESC, p.ID DESC
");
$dbPaymentHistory = [];
if ($payHistoryQuery && mysqli_num_rows($payHistoryQuery) > 0) {
    while ($row = mysqli_fetch_assoc($payHistoryQuery)) {
        $dbPaymentHistory[] = [
            'id' => $row['ID'],
            'gameId' => $row['GAME_ID'],
            'date' => $row['PAYMENT_DATE'] . 'T' . $row['PAYMENT_TIME'],
            'method' => $row['PAYMENT_TYPE'],
            'reference' => ($row['DETAILS'] !== '') ? $row['DETAILS'] : 'PAY-' . $row['ID'],
            'amount' => (float) $row['AMOUNT'],
            'status' => ($row['STATUS'] === 'Y') ? 'Completed' : (($row['STATUS'] === 'R') ? 'Rejected' : 'Pending'),
            'rejectionReason' => $row['REJECTION_REASON'] ?? '',
            'reviewedByName' => $row['REVIEWED_BY_NAME'] ?? '',
            'reviewedAt' => $row['REVIEWED_AT'] ?? ''
        ];
    }
}

// Inline SVGs for PHP rendering
$icons = [
    'Activity' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
    'Users' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'DollarSign' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'CreditCard' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
    'Scale' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="M7 21h10"></path><path d="M12 3v18"></path><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path></svg>',
    'ArrowRight' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
    'FilterX' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.013 3H2l8 9.46V19l4 2v-8.54l.9-1.055"></path><line x1="22" y1="3" x2="16" y2="9"></line><line x1="16" y1="3" x2="22" y2="9"></line></svg>',
];

function formatCardCurrency($amount) {
    return '$' . number_format((float)$amount, 2, '.', ',');
}
if (!function_exists('formatDate')) {
    function formatDate($dateString) {
        return date('M d, Y', strtotime($dateString));
    }
}
?>
<script>
  tailwind = {
    config: {
      corePlugins: {
        preflight: false
      }
    }
  }
</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Smooth fade transition for modals */
    .modal-enter { opacity: 0; transform: scale(0.95); }
    .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.2s ease-out; }
    .modal-exit { opacity: 1; transform: scale(1); }
    .modal-exit-active { opacity: 0; transform: scale(0.95); transition: all 0.2s ease-in; }
    [x-cloak] { display: none !important; }
</style>

<div class="pt-1 pb-6 px-4 sm:px-8" style="<?php echo ($premiumStatus != 'Y') ? 'opacity:0; pointer-events:none;' : ''; ?>">
    <div class="max-w-7xl mx-auto space-y-5">
        
        <!-- --- PAGE HEADER & FILTERS --- -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white py-4 px-6 rounded-2xl shadow-sm border border-slate-200/80 min-h-[80px] md:h-[85px]">
            <div class="flex flex-col justify-center">
                <h1 class="text-[18px] font-bold text-slate-800 tracking-tight leading-none m-0">PAYMENTS & LEDGER</h1>
                <p class="text-[15px] text-slate-500 mt-2.5 mb-0 font-medium">Track games, expenses, payments and outstanding balances</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Host Filter -->
                <div class="relative w-full sm:w-[200px]">
                    <select id="payhost" class="appearance-none w-full h-[42px] bg-slate-50 border border-slate-200 text-slate-700 px-3 pr-8 rounded-[8px] outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-semibold text-sm cursor-pointer">
                        <option value="">All Hosts</option>
                        <?php foreach($hostsOptions as $h): ?>
                            <option value="<?= htmlspecialchars($h['ID']) ?>" <?= $h['ID'] == $host_id ? 'selected' : '' ?>><?= htmlspecialchars($h['NAME']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                <button id="pay_com_reset" class="hidden p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-[8px] transition-colors items-center justify-center border-none bg-none h-[42px] w-[42px]" title="Clear Filters">
                    <?= $icons['FilterX'] ?>
                </button>
            </div>
        </div>

        <!-- --- SECTION 1: PLAYER PAYMENT OVERVIEW --- -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex flex-col justify-between h-32">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-teal-500 animate-pulse"></div>
                    <h3 class="font-bold text-slate-800 text-base truncate m-0"><?= htmlspecialchars($selectedClubName) ?></h3>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">Games</span>
                    <div class="text-2xl font-bold text-slate-900 tabular-nums my-0.5"><?= $overviewData['totalGames'] ?></div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex flex-col justify-between h-32">
                <div class="flex items-center gap-2.5 text-slate-500">
                    <div class="p-1.5 bg-slate-100 text-slate-600 rounded-lg"><?= $icons['DollarSign'] ?></div>
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Expense</span>
                </div>
                <div class="text-2xl font-bold text-slate-900 tabular-nums my-0.5"><?= formatCardCurrency($overviewData['totalExpense']) ?></div>
                <div class="text-[10px] text-slate-400 font-medium">Game & other expenses</div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex flex-col justify-between h-32">
                <div class="flex items-center gap-2.5 text-slate-500">
                    <div class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg"><?= $icons['CreditCard'] ?></div>
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Paid</span>
                </div>
                <div class="text-2xl font-bold text-emerald-600 tabular-nums my-0.5"><?= formatCardCurrency($overviewData['totalPaid']) ?></div>
                <div class="text-[10px] text-slate-400 font-medium">Payments received</div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex flex-col justify-between h-32 border-l-4 <?= $overviewData['balance'] >= 0 ? 'border-l-red-500' : 'border-l-emerald-500' ?>">
                <div class="flex items-center gap-2.5 text-slate-500">
                    <div class="p-1.5 bg-slate-100 text-slate-600 rounded-lg"><?= $icons['Scale'] ?></div>
                    <span class="text-xs font-semibold uppercase tracking-wider">Balance</span>
                </div>
                <div class="text-2xl font-bold tabular-nums my-0.5 <?= $overviewData['balance'] >= 0 ? 'text-red-500' : 'text-emerald-500' ?>">
                    <?= formatCardCurrency(abs($overviewData['balance'])) ?>
                    <span class="text-sm font-semibold ml-0.5">
                        <?= $overviewData['balance'] >= 0 ? 'Due' : 'Adv' ?>
                    </span>
                </div>
                <div class="text-[10px] text-slate-400 font-medium">Outstanding balance</div>
            </div>
        </div>

        <!-- --- SECTION 3: RECENT TRANSACTIONS --- -->
        <style>
            .excel-table th, 
            .excel-table td {
                padding: 6px 12px !important;
                height: 40px !important;
                font-size: 13px !important;
                vertical-align: middle !important;
            }
            .excel-table thead th {
                height: 36px !important;
                padding-top: 4px !important;
                padding-bottom: 4px !important;
            }
            .excel-table tbody tr:hover {
                background-color: #f8fafc !important;
            }
        </style>
            <div class="p-3 border-b border-slate-100 bg-white flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 m-0">Recent Transactions</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">View your latest game payments and transaction activity.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Month Filter -->
                    <div class="relative w-[120px]">
                        <select id="paymonth" class="appearance-none w-full h-[36px] bg-slate-50 border border-slate-200 text-slate-700 px-2.5 pr-7 rounded-[6px] outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-semibold text-xs cursor-pointer">
                            <option value="">Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                        <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>
                    
                    <!-- Year Filter -->
                    <div class="relative w-[90px]">
                        <select id="payyear" class="appearance-none w-full h-[36px] bg-slate-50 border border-slate-200 text-slate-700 px-2.5 pr-7 rounded-[6px] outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-semibold text-xs cursor-pointer">
                            <option value="">Year</option>
                            <?php
                            for ($year = 2024; $year <= 2030; $year++) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                        <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>

                    <button id="viewLedgerBtn" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold h-[36px] px-3.5 rounded-[6px] transition-colors border-none cursor-pointer text-xs shadow-sm flex items-center justify-center">
                        View Monthly Ledger
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto patmentTb w-full">
                <table class="w-full text-sm text-left paymentTab table-fixed min-w-[800px] excel-table">
                    <thead class="bg-slate-50/80 text-slate-500 uppercase font-bold text-[11px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 w-[16%]">Host</th>
                            <th class="px-4 py-3 w-[20%]">Date & Time</th>
                            <th class="px-4 py-3 w-[17%]">Venue</th>
                            <th class="px-4 py-3 w-[21%]">Event Type</th>
                            <th class="px-4 py-3 w-[10%] text-right">Amount</th>
                            <th class="px-4 py-3 w-[10%] text-center">Payment</th>
                            <th class="px-4 py-3 w-[6%] text-center">History</th>
                        </tr>
                    </thead>
                    <tbody id="mainTableBody" class="divide-y divide-slate-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            <div id="mainTablePagination"></div>
        </div>

    </div>
</div>

<!-- --- MODALS (Hidden by default, populated & toggled by JS) --- -->
<div id="modalContainer" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 hidden">
    <div id="modalBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm cursor-pointer"></div>
    
    <!-- Modal Content Shell -->
    <div id="modalContent" class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden transform transition-all modal-enter-active">
        <div class="flex items-center justify-between px-6 py-2.5 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <h2 id="modalTitle" class="text-base sm:text-xl font-bold text-slate-800">Title</h2>
                <div id="modalHeaderFilters" class="hidden items-center gap-1.5">
                    <!-- Month Selector -->
                    <div class="relative w-[95px] sm:w-[110px]">
                        <select id="modalMonthFilter" class="appearance-none w-full h-[28px] sm:h-[32px] bg-white border border-slate-200 text-slate-700 px-2 pr-6 rounded-[6px] outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-semibold text-[10px] sm:text-xs cursor-pointer">
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                        <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>
                    <!-- Year Selector -->
                    <div class="relative w-[70px] sm:w-[80px]">
                        <select id="modalYearFilter" class="appearance-none w-full h-[28px] sm:h-[32px] bg-white border border-slate-200 text-slate-700 px-2 pr-6 rounded-[6px] outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-semibold text-[10px] sm:text-xs cursor-pointer">
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                            <option value="2029">2029</option>
                            <option value="2030">2030</option>
                        </select>
                        <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>
                </div>
            </div>
            <button id="closeModalBtn" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div id="modalBody" class="overflow-y-auto flex-1 p-6">
            <!-- Dynamic Content -->
        </div>
    </div>
</div>

<!-- --- JAVASCRIPT APP LOGIC --- -->
<script>
window.addEventListener('load', function() {
    // --- Data Injected from PHP ---
    const allTransactions = <?= json_encode($dbTransactions) ?>;
    const hostSummaries = <?= json_encode($hostSummaries) ?>;
    const allPayments = <?= json_encode($dbPaymentHistory) ?>;

    // --- State ---
    let mainPage = 1;
    let mainItemsPerPage = 20;
    
    let modalPage = 1;
    let modalItemsPerPage = 20;
    let currentModalTransactions = []; // Holds filtered data for modal pagination
    let activeModalType = null; // 'host', 'monthly', 'history', 'pay'
    let selectedHostId = null;
    let selectedDateFilter = { month: '', year: '' };

    // --- UI Elements ---
    const elements = {
        mainTableBody: document.getElementById('mainTableBody'),
        mainTablePagination: document.getElementById('mainTablePagination'),
        monthFilter: document.getElementById('paymonth'),
        yearFilter: document.getElementById('payyear'),
        hostFilter: document.getElementById('payhost'),
        clearFiltersBtn: document.getElementById('pay_com_reset'),
        modalContainer: document.getElementById('modalContainer'),
        modalBackdrop: document.getElementById('modalBackdrop'),
        modalContent: document.getElementById('modalContent'),
        modalTitle: document.getElementById('modalTitle'),
        modalBody: document.getElementById('modalBody'),
        closeModalBtn: document.getElementById('closeModalBtn'),
    };

    // --- Formatters ---
    const formatCurrency = (amount) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(amount);
    const formatDate = (dateString) => new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
    const formatDateTime = (dateString) => new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
    
    const getBadgeHtml = (status) => {
        const styles = {
            PAID: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            PARTIAL: 'bg-amber-50 text-amber-700 border-amber-200',
            UNPAID: 'bg-red-50 text-red-700 border-red-200',
            DUE: 'bg-red-50 text-red-700 border-red-200',
            PENDING: 'bg-blue-50 text-blue-700 border-blue-200',
            REJECTED: 'bg-rose-50 text-rose-700 border-rose-200',
        };
        const currentStyle = styles[status] || 'bg-slate-50 text-slate-700 border-slate-200';
        return `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide border ${currentStyle}">${status}</span>`;
    };

    const SVGs = {
        ChevronDown: `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>`,
        Activity: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>`,
        DollarSign: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>`,
        ArrowDownToLine: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"></path><path d="m6 11 6 6 6-6"></path><path d="M19 21H5"></path></svg>`
    };

    // --- Pagination Generator ---
    function renderPaginationHtml(totalItems, itemsPerPage, currentPage, onPageChangeStr, onSizeChangeStr) {
        if (totalItems === 0) return '';
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);

        let pages = [];
        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            if (currentPage <= 3) pages.push(1, 2, 3, 4, '...', totalPages);
            else if (currentPage >= totalPages - 2) pages.push(1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages);
            else pages.push(1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages);
        }

        let buttonsHtml = pages.map(num => {
            if (num === '...') return `<button disabled class="px-3.5 py-1.5 rounded-md text-sm font-medium text-slate-400 cursor-default">...</button>`;
            const activeClass = num === currentPage ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100';
            return `<button onclick="${onPageChangeStr}(${num})" class="px-3.5 py-1.5 rounded-md text-sm font-medium transition-colors ${activeClass}">${num}</button>`;
        }).join('');

        const sizes = [5, 10, 20, 50, 100].map(s => `<option value="${s}" ${s === itemsPerPage ? 'selected' : ''}>${s}</option>`).join('');

        return `
            <div class="flex flex-col sm:flex-row items-center justify-between mt-6 px-4 py-3 border-t border-slate-100 text-sm text-slate-600">
                <div class="flex items-center space-x-2 mb-4 sm:mb-0">
                    <span>Rows per page:</span>
                    <div class="relative">
                        <select onchange="${onSizeChangeStr}(Number(this.value))" class="appearance-none bg-slate-50 border border-slate-200 text-slate-700 py-1.5 pl-3 pr-8 rounded-lg outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 cursor-pointer">
                            ${sizes}
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">${SVGs.ChevronDown}</div>
                    </div>
                    <span class="ml-4 text-slate-500">Showing ${startItem}-${endItem} of ${totalItems}</span>
                </div>
                <div class="flex items-center space-x-1">
                    <button onclick="${onPageChangeStr}(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-md hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed font-medium text-slate-700 transition-colors">Previous</button>
                    ${buttonsHtml}
                    <button onclick="${onPageChangeStr}(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-md hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed font-medium text-slate-700 transition-colors">Next</button>
                </div>
            </div>
        `;
    }

    // --- Core Rendering Functions ---
    window.setMainPage = (page) => { mainPage = page; renderMainTable(); };
    window.setMainItemsPerPage = (size) => { mainItemsPerPage = size; mainPage = 1; renderMainTable(); };
    
    function renderMainTable() {
        const startIndex = (mainPage - 1) * mainItemsPerPage;
        const paginatedTxs = allTransactions.slice(startIndex, startIndex + mainItemsPerPage);
        
        elements.mainTableBody.innerHTML = paginatedTxs.map(tx => `
            <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/30">
                <td class="px-4 py-3 font-semibold text-slate-800 truncate">${tx.hostName}</td>
                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">${formatDateTime(tx.date)}</td>
                <td class="px-4 py-3 text-slate-600 truncate">${tx.venue}</td>
                <td class="px-4 py-3 text-slate-600 truncate">${tx.type}</td>
                <td class="px-4 py-3 text-right font-bold text-slate-900 tabular-nums">${formatCurrency(tx.amount)}</td>
                <td class="px-4 py-3 text-center">${getBadgeHtml(tx.status)}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center">
                        <button onclick="openPaymentHistory('${tx.gameId}')" class="flex items-center justify-center w-8 h-8 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-800 rounded-lg shadow-sm border-none cursor-pointer transition-all" title="View Payment History">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        elements.mainTablePagination.innerHTML = renderPaginationHtml(allTransactions.length, mainItemsPerPage, mainPage, 'setMainPage', 'setMainItemsPerPage');
    }

    // --- Modal Handling ---
    function openModal(title, maxWidthClass = 'max-w-4xl') {
        elements.modalTitle.innerText = title;
        elements.modalContent.className = `relative w-full ${maxWidthClass} bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden transform transition-all modal-enter-active`;
        elements.modalContainer.classList.remove('hidden');
    }

    function closeModal() {
        elements.modalContainer.classList.add('hidden');
        elements.modalBody.className = "overflow-y-auto flex-1 p-6";
        const modalHeaderFilters = document.getElementById('modalHeaderFilters');
        if (modalHeaderFilters) {
            modalHeaderFilters.classList.add('hidden');
            modalHeaderFilters.classList.remove('flex');
        }
        if (activeModalType === 'host') {
            elements.hostFilter.value = '';
            selectedHostId = null;
        } else if (activeModalType === 'monthly') {
            elements.monthFilter.value = '';
            elements.yearFilter.value = '';
            selectedDateFilter = {month: '', year: ''};
        }
        activeModalType = null;
        updateClearFilterButton();
    }

    // Modal Pagination hooks
    window.setModalPage = (page) => { modalPage = page; renderCurrentModalView(); };
    window.setModalItemsPerPage = (size) => { modalItemsPerPage = size; modalPage = 1; renderCurrentModalView(); };

    function renderCurrentModalView() {
        if (activeModalType === 'host') renderHostModalBody();
        else if (activeModalType === 'monthly') renderMonthlyModalBody();
    }

    // --- Specific Modals ---
    window.openHostModal = (hostId) => {
        elements.hostFilter.value = hostId;
        elements.monthFilter.value = '';
        elements.yearFilter.value = '';
        selectedHostId = hostId;
        activeModalType = 'host';
        modalPage = 1;
        updateClearFilterButton();
        
        currentModalTransactions = allTransactions.filter(t => t.hostId == hostId);
        const host = hostSummaries.find(h => h.id == hostId);
        if(!host) return;
        
        openModal(`${host.name} — Ledger`, 'max-w-4xl');
        renderHostModalBody(host);
    };

    function renderHostModalBody(host) {
        if (!host) host = hostSummaries.find(h => h.id == selectedHostId);
        const startIndex = (modalPage - 1) * modalItemsPerPage;
        const pageTxs = currentModalTransactions.slice(startIndex, startIndex + modalItemsPerPage);
        
        const dueHtml = host.due > 0 ? `<div class="text-xl font-bold text-red-500">${formatCurrency(host.due)} Due</div>` : 
                        host.due < 0 ? `<div class="text-xl font-bold text-emerald-500">${formatCurrency(Math.abs(host.due))} Adv</div>` : 
                        `<div class="text-xl font-bold text-emerald-500">Settled</div>`;

        let txHtml = pageTxs.map(tx => `
            <tr class="hover:bg-slate-50/50 even:bg-slate-50/30">
                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">${formatDateTime(tx.date)}</td>
                <td class="px-4 py-3 text-slate-700">${tx.venue}</td>
                <td class="px-4 py-3 text-slate-700">${tx.type}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-800 tabular-nums">${formatCurrency(tx.amount)}</td>
                <td class="px-4 py-3 text-center">${getBadgeHtml(tx.status)}</td>
            </tr>
        `).join('');

        elements.modalBody.innerHTML = `
            <div class="space-y-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div><div class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Total Games</div><div class="text-xl font-bold text-slate-800">${host.games}</div></div>
                    <div><div class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Total Expense</div><div class="text-xl font-bold text-slate-800">${formatCurrency(host.expense)}</div></div>
                    <div><div class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Total Paid</div><div class="text-xl font-bold text-emerald-600">${formatCurrency(host.paid)}</div></div>
                    <div><div class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Balance</div>${dueHtml}</div>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-100/50 text-slate-500 uppercase font-semibold text-xs tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3">Date & Time</th><th class="px-4 py-3">Venue</th><th class="px-4 py-3">Event Type</th>
                                    <th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-center">Payment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">${txHtml}</tbody>
                        </table>
                    </div>
                    ${renderPaginationHtml(currentModalTransactions.length, modalItemsPerPage, modalPage, 'setModalPage', 'setModalItemsPerPage')}
                </div>
            </div>
        `;
    }

    window.openMonthlyModal = () => {
        const month = elements.monthFilter.value;
        const year = elements.yearFilter.value;
        if (!month || !year) return;
        
        elements.hostFilter.value = '';
        selectedDateFilter = { month, year };
        activeModalType = 'monthly';
        modalPage = 1;
        updateClearFilterButton();

        const paddedMonth = month.padStart(2, '0');
        const monthName = new Date(`${year}-${paddedMonth}-01`).toLocaleString('default', { month: 'long' });
        openModal(`Monthly Ledger — ${monthName} ${year}`, 'max-w-5xl');
        elements.modalBody.className = "overflow-y-auto flex-1 px-6 pb-6 pt-3";
        
        const modalHeaderFilters = document.getElementById('modalHeaderFilters');
        if (modalHeaderFilters) {
            modalHeaderFilters.classList.remove('hidden');
            modalHeaderFilters.classList.add('flex');
            document.getElementById('modalMonthFilter').value = Number(month);
            document.getElementById('modalYearFilter').value = year;
        }
        
        elements.modalBody.innerHTML = `<div class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500"></div></div>`;
        
        const formData = new FormData();
        formData.append('user_id', '<?php echo $player_id; ?>');
        formData.append('host_id', '<?php echo $host_id; ?>');
        formData.append('year', year);
        formData.append('month', month);
        formData.append('is_player_side', '1');

        fetch('api/view_player_pay.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            elements.modalBody.innerHTML = `<div class="overflow-x-auto p-1">${html}</div>`;
        })
        .catch(err => {
            elements.modalBody.innerHTML = `<div class="text-center py-12 text-rose-500 font-medium">Failed to load ledger: ${err.message}</div>`;
        });
    };

    function renderMonthlyModalBody(monthName = null, year = null) {
        if(!monthName) {
            const paddedMonth = selectedDateFilter.month.padStart(2, '0');
            monthName = new Date(`${selectedDateFilter.year}-${paddedMonth}-01`).toLocaleString('default', { month: 'long' });
            year = selectedDateFilter.year;
        }
        
        let totalExp = 0, totalPd = 0;
        currentModalTransactions.forEach(t => { totalExp += t.amount; totalPd += t.paidAmount; });
        const due = Math.max(0, totalExp - totalPd);
        const advance = Math.max(0, totalPd - totalExp);

        const startIndex = (modalPage - 1) * modalItemsPerPage;
        const pageTxs = currentModalTransactions.slice(startIndex, startIndex + modalItemsPerPage);

        let actionAreaHtml = '';
        if (due > 0) {
            actionAreaHtml = `
                <div class="text-center w-full">
                    <div class="text-slate-300 text-sm mb-2">Amount Payable</div>
                    <button class="PayAmountModal_open w-full bg-teal-500 hover:bg-teal-400 text-slate-900 font-bold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 border-none cursor-pointer" data-id="${pageTxs[0] ? pageTxs[0].gameId : ''}" data-user-id="<?php echo $player_id; ?>">Pay ${formatCurrency(due)}</button>
                </div>`;
        } else if (advance > 0) {
            actionAreaHtml = `
                <div class="text-center">
                    <div class="inline-flex items-center justify-center p-3 bg-emerald-500/20 text-emerald-300 rounded-full mb-2">${SVGs.Activity}</div>
                    <div class="font-semibold text-emerald-400">All Settled</div>
                    <div class="text-xs text-slate-400 mt-1">Advance: ${formatCurrency(advance)}</div>
                </div>`;
        } else {
            actionAreaHtml = `<div class="text-center text-emerald-400 font-bold text-lg">All Settled</div>`;
        }

        let txHtml = pageTxs.length > 0 ? pageTxs.map(tx => `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-4 py-3 text-slate-800 font-semibold truncate">${tx.hostName}</td>
                <td class="px-4 py-3 text-slate-800 font-medium whitespace-nowrap">${formatDateTime(tx.date)}</td>
                <td class="px-4 py-3 text-slate-600">${tx.venue}</td>
                <td class="px-4 py-3 text-slate-600">${tx.type}</td>
                <td class="px-4 py-3 text-right font-bold text-slate-900 tabular-nums">${formatCurrency(tx.amount)}</td>
                <td class="px-4 py-3 text-center">${getBadgeHtml(tx.status)}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button onclick="openPaymentHistory('${tx.gameId}')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded transition-colors border-none cursor-pointer">History</button>
                        ${tx.status !== 'PAID' ? `<button class="PayAmountModal_open text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 px-3 py-1.5 rounded shadow-sm transition-colors border-none cursor-pointer" data-id="${tx.gameId}" data-user-id="<?php echo $player_id; ?>">Pay</button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('') : `<tr><td colspan="7" class="text-center py-8 text-slate-500 font-medium">No transactions found for the selected month.</td></tr>`;

        elements.modalBody.innerHTML = `
            <div class="space-y-6">
                <div class="flex flex-col md:flex-row gap-6 bg-slate-900 text-white p-6 rounded-xl shadow-lg relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-teal-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-4 z-10">
                        <div><div class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Total Games</div><div class="text-xl font-bold">${currentModalTransactions.length}</div></div>
                        <div><div class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Total Expense</div><div class="text-xl font-bold">${formatCurrency(totalExp)}</div></div>
                        <div><div class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Total Paid</div><div class="text-xl font-bold text-emerald-400">${formatCurrency(totalPd)}</div></div>
                        <div>
                            <div class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">${advance > 0 ? 'Advance' : 'Total Due'}</div>
                            <div class="text-xl font-bold ${advance > 0 ? 'text-emerald-400' : 'text-red-400'}">${formatCurrency(advance > 0 ? advance : due)}</div>
                        </div>
                    </div>
                    <div class="flex items-center md:pl-6 md:border-l border-slate-700 z-10 min-w-[200px] justify-center md:justify-end">${actionAreaHtml}</div>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-600 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-4">Host</th>
                                    <th class="px-4 py-4">Date & Time</th>
                                    <th class="px-4 py-4">Venue</th>
                                    <th class="px-4 py-4">Event Type</th>
                                    <th class="px-4 py-4 text-right">Amount</th>
                                    <th class="px-4 py-4 text-center">Status</th>
                                    <th class="px-4 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">${txHtml}</tbody>
                        </table>
                    </div>
                    ${renderPaginationHtml(currentModalTransactions.length, modalItemsPerPage, modalPage, 'setModalPage', 'setModalItemsPerPage')}
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100 font-semibold text-sm text-slate-700">
                    <div>Total Expense: <span class="font-bold text-slate-900">${formatCurrency(totalExp)}</span></div>
                    <div>Total Paid: <span class="font-bold text-emerald-600">${formatCurrency(totalPd)}</span></div>
                    <div>Balance: <span class="font-bold ${due > 0 ? 'text-red-500' : 'text-emerald-600'}">${due > 0 ? formatCurrency(due) + ' Due' : formatCurrency(advance) + ' Adv'}</span></div>
                </div>
            </div>
        `;
    }

    let previousModalType = null;
    window.openPaymentHistory = (gameId) => {
        previousModalType = activeModalType;
        activeModalType = 'history';
        openModal("Payment History", "max-w-4xl");
        
        const txPayments = allPayments.filter(p => p.gameId == gameId);
        const tx = allTransactions.find(t => t.gameId == gameId);
        const totalAmount = tx ? tx.amount : 0;
        const paidAmount = txPayments.reduce((sum, p) => p.status === 'Completed' ? sum + p.amount : sum, 0);
        const remainingAmount = Math.max(0, totalAmount - paidAmount);
        
        let overallStatus = 'DUE';
        if (remainingAmount <= 0) {
            overallStatus = 'PAID';
        } else if (paidAmount > 0) {
            overallStatus = 'PARTIAL';
        }
        
        const sortedPayments = [...txPayments].sort((a, b) => new Date(a.date) - new Date(b.date));
        
        let phHtml = "";
        if (sortedPayments.length > 0) {
            phHtml = sortedPayments.map((ph, index) => {
                const slNo = String(index + 1).padStart(2, '0');
                const rowStatus = ph.status === 'Completed' ? 'PAID' : (ph.status === 'Pending' ? 'PENDING' : 'REJECTED');
                const reviewedBy = ph.reviewedByName ? ph.reviewedByName : '—';
                const reviewedAt = ph.reviewedAt && ph.reviewedAt !== '0000-00-00 00:00:00' ? formatDateTime(ph.reviewedAt) : '—';
                const remarks = ph.rejectionReason ? ph.rejectionReason : '—';
                return `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-center text-xs text-slate-500 font-medium">Attempt #${index + 1}</td>
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">${formatDateTime(ph.date)}</td>
                        <td class="px-4 py-3 text-slate-700">Player</td>
                        <td class="px-4 py-3 font-bold text-slate-900 tabular-nums text-right">${formatCurrency(ph.amount)}</td>
                        <td class="px-4 py-3 text-center">${getBadgeHtml(rowStatus)}</td>
                        <td class="px-4 py-3 text-slate-700">${reviewedBy}</td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">${reviewedAt}</td>
                        <td class="px-4 py-3 text-slate-500 truncate" style="max-width: 150px;" title="${remarks}">${remarks}</td>
                    </tr>
                `;
            }).join('');
        } else {
            phHtml = `<tr><td colspan="8" class="text-center py-8 text-slate-500 font-medium">No payment history available.</td></tr>`;
        }

        const backBtnHtml = previousModalType === 'monthly' ? 
            `<button onclick="openMonthlyModal()" class="mt-4 text-sm text-slate-500 hover:text-slate-800 font-medium border-none bg-none cursor-pointer">← Back to Monthly Ledger</button>` : '';

        elements.modalBody.innerHTML = `
            <div class="space-y-6">
                <!-- Subtitle & Context Line -->
                <div>
                    <p class="text-xs text-slate-500 m-0">Transaction payment details and balance history</p>
                    <p class="text-xs font-semibold text-slate-600 mt-1">${tx ? tx.hostName : ''} &bull; ${tx ? tx.venue : ''} &bull; ${tx ? tx.type : ''}</p>
                </div>

                <!-- Payment Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Expense Card -->
                    <div class="bg-slate-900 text-white rounded-xl p-3 flex flex-col justify-between h-20 shadow-sm border border-slate-800">
                        <span class="text-[10px] uppercase font-bold text-slate-400">Expense</span>
                        <span class="text-lg font-bold">${formatCurrency(totalAmount)}</span>
                    </div>
                    <!-- Paid Card -->
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-xl p-3 flex flex-col justify-between h-20 shadow-sm">
                        <span class="text-[10px] uppercase font-bold text-emerald-600">Paid</span>
                        <span class="text-lg font-bold text-emerald-700">${formatCurrency(paidAmount)}</span>
                    </div>
                    <!-- Balance Card -->
                    <div class="${remainingAmount > 0 ? 'bg-rose-50 border border-rose-200 text-rose-950' : 'bg-emerald-50 border border-emerald-200 text-emerald-950'} rounded-xl p-3 flex flex-col justify-between h-20 shadow-sm">
                        <span class="text-[10px] uppercase font-bold ${remainingAmount > 0 ? 'text-rose-600' : 'text-emerald-600'}">Balance</span>
                        <span class="text-lg font-bold ${remainingAmount > 0 ? 'text-rose-700' : 'text-emerald-700'}">${formatCurrency(remainingAmount)}</span>
                    </div>
                    <!-- Status Card -->
                    <div class="bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-3 flex flex-col justify-between h-20 shadow-sm">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Status</span>
                        <span class="self-start">${getBadgeHtml(overallStatus)}</span>
                    </div>
                </div>

                <!-- History Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold text-xs tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3">Attempt</th>
                                    <th class="px-4 py-3">Date &amp; Time</th>
                                    <th class="px-4 py-3">Requested By</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3">Reviewed By</th>
                                    <th class="px-4 py-3">Reviewed Date/Time</th>
                                    <th class="px-4 py-3">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">${phHtml}</tbody>
                        </table>
                    </div>
                </div>
                ${backBtnHtml}
            </div>
        `;
    };

    function updateClearFilterButton() {
        if (elements.hostFilter.value || elements.monthFilter.value || elements.yearFilter.value) {
            elements.clearFiltersBtn.classList.remove('hidden');
            elements.clearFiltersBtn.classList.add('flex');
        } else {
            elements.clearFiltersBtn.classList.add('hidden');
            elements.clearFiltersBtn.classList.remove('flex');
        }
    }

    function clearFilters() {
        elements.hostFilter.value = '';
        elements.monthFilter.value = '';
        elements.yearFilter.value = '';
        updateClearFilterButton();
        if (activeModalType) closeModal();
        mainPage = 1;
        renderMainTable();
    }

    // --- Event Listeners ---
    elements.hostFilter.addEventListener('change', (e) => {
        if (e.target.value) {
            const url = new URL(window.location.href);
            url.searchParams.set('host_id', e.target.value);
            window.location.href = url.toString();
        } else {
            const url = new URL(window.location.href);
            url.searchParams.delete('host_id');
            window.location.href = url.toString();
        }
    });

    const onDateFilterChange = () => {
        updateClearFilterButton();
    };
    
    elements.monthFilter.addEventListener('change', onDateFilterChange);
    elements.yearFilter.addEventListener('change', onDateFilterChange);
    elements.clearFiltersBtn.addEventListener('click', clearFilters);
    
    elements.closeModalBtn.addEventListener('click', closeModal);
    elements.modalBackdrop.addEventListener('click', closeModal);

    $(document).on('click', '#viewLedgerBtn', function() {
        const month = $('#paymonth').val();
        const year = $('#payyear').val();
        if (!month || !year) {
            alert('Please select both a Month and a Year to view the ledger.');
            return;
        }
        openMonthlyModal();
    });

    $(document).on('change', '#modalMonthFilter, #modalYearFilter', function() {
        const month = $('#modalMonthFilter').val();
        const year = $('#modalYearFilter').val();
        
        $('#paymonth').val(month);
        $('#payyear').val(year);
        
        openMonthlyModal();
    });

    // Init
    renderMainTable();
});
</script>

<!------Player-Modal----->
<section class="customModal_wrap PayAmountModal">
    <div class="customModal_body">
        <h6 class="customModal_head">Enter Your Payment Details</h6>
        <button class="customModal_close btn PayAmountModal_close">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form>
                <div class="d-flex flex-column align-items-center justify-content-end gap-2">
                    <select class="form-control game_dt">
                        <option value=''>Select Game</option>
                        <?php
                        // echo "SELECT * FROM `ca_gamejoin` where USER_ID='".$_SESSION['userid']."'";
                        $select_joined = mysqli_query($conn, "
                                SELECT ce.ID, ce.EVENT_DATE, ce.EVENT_TIME, ce.EVENT_VENUE, ce.HOST_NAME 
                                FROM ca_gamejoin cg
                                INNER JOIN ca_events ce ON cg.GAME_ID = ce.ID
                                WHERE cg.USER_ID = '" . $_SESSION['user_id'] . "' 
                                  AND cg.STATUS = 'Y' 
                                  AND ce.STATUS = 'Completed'
                                  AND ce.HOST_ID = '$host_id'
                                ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
                            ");

                        while ($fetch_events = mysqli_fetch_assoc($select_joined)) {
                            ?>
                        <option value="<?= $fetch_events['ID'] ?>">
                            <?= $fetch_events['EVENT_DATE'] . ' ' . $fetch_events['EVENT_TIME'] . ' ' . $fetch_events['EVENT_VENUE'] . ' (' . $fetch_events['HOST_NAME'] . ')' ?>
                        </option>
                        <?php
                        }
                        ?>

                    </select>
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <span id="tot_amnt">Total Amount: <strong>$0</strong></span>
                        <span id="due">Due: <strong>$0</strong></span>
                    </div>
                    <input type="hidden" id="user_id" value="<?= $_SESSION['user_id'] ?>" />
                    <input type="hidden" id="due_amt" value="" />
                </div>
                <div class="row">
                    <div class="col-md-6 col-12 mb-3">
                        <label for="Amount" class="form-label">Amount<span>*</span></label>
                        <input type="number" class="form-control" id="Amount" placeholder="Enter Payment Amount">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="date" class="form-label">Date<span>*</span></label>
                        <input type="date" class="form-control" id="date" placeholder="Enter Payment date"
                            value="<?= $currentDate ?>">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="time" class="form-label">Time<span>*</span></label>
                        <input type="time" class="form-control" id="time" placeholder="Enter Payment time"
                            value="<?= $currentTime ?>">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="paymentType" class="form-label">Payment Type<span>*</span></label>
                        <select class="form-select form-control" id="paymentType" aria-label="">
                            <option value="Interac">Interac</option>
                            <option value="Cash">Cash</option>
                            <!--<option value="Checkbook">Checkbook</option>-->
                        </select>
                    </div>

                    <div class="col-md-12 col-12 mb-3">
                        <label for="Message" class="form-label">Any Payment Details (Optional)<span></span></label>
                        <textarea class="form-control" id="details" rows="1"
                            placeholder="Enter Any Payment Details"></textarea>
                    </div>

                    <div class="col-md-12 col-12 mb-3">
                        <label for="Message" class="form-label">Message (Optional)<span></span></label>
                        <textarea class="form-control" id="Message" rows="1" placeholder="text....."></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-center mt-2">
                        <button type="button" class="btn btn-primary save_payment">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="customModal_wrap hostgameview_modal hostgameview_modal_payment">
    <div class="customModal_body">
        <h6 class="customModal_head">View History</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <hr />
            <div class="playerList-container" id="playerList_payment">


            </div>

        </div>
    </div>
</section>