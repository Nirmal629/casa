<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include('dbConnection.php');
require_once __DIR__ . '/helpers/ledger_helper.php';

// ─── Input sanitization ───────────────────────────────────────────────
$filterYear     = isset($_POST['year'])     && $_POST['year']     !== '' ? (int)$_POST['year']     : 0;
$filterMonth    = isset($_POST['month'])    && $_POST['month']    !== '' ? (int)$_POST['month']    : 0;
$filterPlayerId = isset($_POST['player'])   && $_POST['player']   !== '' ? (int)$_POST['player']   : null;
$searchQuery    = isset($_POST['search'])   ? trim($_POST['search'])                               : '';
$sortBy         = isset($_POST['sort_by'])  ? $_POST['sort_by']                                    : 'name';
$sortDir        = isset($_POST['sort_dir']) && strtolower($_POST['sort_dir']) === 'desc' ? 'desc'  : 'asc';
$page           = isset($_POST['page'])     && (int)$_POST['page'] > 0 ? (int)$_POST['page']      : 1;
$allowedPageSizes = [5, 10, 20, 50, 100];
$perPage        = isset($_POST['pageSize']) && in_array((int)$_POST['pageSize'], $allowedPageSizes) ? (int)$_POST['pageSize'] : 10;

// Whitelist sort columns
$allowedSortBy = ['name', 'games', 'amount', 'paid', 'due'];
if (!in_array($sortBy, $allowedSortBy)) $sortBy = 'name';

$host_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$useDateFilter = ($filterYear > 0 && $filterMonth > 0);

// ─── 1. Fetch players scoped to this host ─────────────────────────────
$sql = "
    SELECT DISTINCT u.*
    FROM ca_users u
    WHERE u.USERTYPE = 'Player'
      AND u.DEL_STATUS = 'N'
      AND (
        EXISTS (SELECT 1 FROM ca_gamejoin cg WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $host_id AND cg.STATUS = 'Y')
        OR EXISTS (SELECT 1 FROM ca_expense ce WHERE ce.USER_ID = u.ID AND ce.HOST_ID = $host_id AND ce.STATUS = 'Y')
      )
";

if ($filterPlayerId) {
    $sql .= " AND u.ID = {$filterPlayerId}";
}

// Search by name, email, phone
if ($searchQuery !== '') {
    $s = $conn->real_escape_string($searchQuery);
    $sql .= " AND (u.NAME LIKE '%{$s}%' OR u.EMAIL LIKE '%{$s}%' OR u.WHATSAPP_NUMBER LIKE '%{$s}%')";
}

$sql .= " ORDER BY u.NAME ASC"; // initial fetch order; we'll sort in PHP

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    if ($searchQuery !== '') {
        echo "<p class='text-center py-3 text-muted'><i class='bi bi-search me-1'></i>No players found matching \"" . htmlspecialchars($searchQuery) . "\".</p>";
    } else {
        echo "<p class='text-center py-3 text-muted'>No players found under this host.</p>";
    }
    $conn->close();
    exit;
}

// ─── 2. Calculate stats for every matching player ─────────────────────
$players = [];
while ($data = $result->fetch_assoc()) {
    $user_id = $data['ID'];

    $summary = calculateLedgerSummary($conn, $user_id, $host_id, $filterYear, $filterMonth);

    $players[] = [
        'data'        => $data,
        'gameCount'   => $summary['games'],
        'totalAmount' => $summary['totalExpense'],
        'totalPaid'   => $summary['totalPaid'],
        'totalDue'    => $summary['totalDue'],
    ];
}

// ─── 3. Sort in PHP ───────────────────────────────────────────────────
usort($players, function($a, $b) use ($sortBy, $sortDir) {
    switch ($sortBy) {
        case 'games':  $cmp = $a['gameCount']   <=> $b['gameCount'];   break;
        case 'amount': $cmp = $a['totalAmount'] <=> $b['totalAmount']; break;
        case 'paid':   $cmp = $a['totalPaid']   <=> $b['totalPaid'];   break;
        case 'due':    $cmp = $a['totalDue']    <=> $b['totalDue'];    break;
        default:       $cmp = strcasecmp($a['data']['NAME'], $b['data']['NAME']); break;
    }
    return $sortDir === 'desc' ? -$cmp : $cmp;
});

// ─── 4. Paginate ──────────────────────────────────────────────────────
$totalPlayers = count($players);
$totalPages   = (int)ceil($totalPlayers / $perPage);
$page         = max(1, min($page, $totalPages));
$offset       = ($page - 1) * $perPage;
$pagePlayers  = array_slice($players, $offset, $perPage);

// ─── 5. Render table ──────────────────────────────────────────────────
// Sort indicator helper
if (!function_exists('sortIcon')) {
    function sortIcon($col, $currentSortBy, $currentSortDir) {
        if ($col !== $currentSortBy) return '<span class="text-muted ms-1" style="font-size:0.7rem;">⇅</span>';
        return $currentSortDir === 'asc'
            ? '<span class="text-warning ms-1" style="font-size:0.7rem;">▲</span>'
            : '<span class="text-warning ms-1" style="font-size:0.7rem;">▼</span>';
    }
}

$startNo = $offset + 1;
echo '<div class="table-responsive" style="font-size:0.75rem;">';
echo '<table class="table table-success table-striped table-bordered table-sm text-nowrap align-middle mb-0">';
echo '<thead class="table-dark text-center">';
echo '<tr>';
echo '<th>Sl. No.</th>';
echo '<th>Profile</th>';

// Sortable column headers (data-sort-by used by JS)
$cols = [
    'name'   => 'Player Name',
    'email'  => 'Email/Phone',
    'games'  => 'Total Game',
    'amount' => 'Total Amount',
    'paid'   => 'Total Payment',
    'due'    => 'Total Due',
];
// Email/Phone is not sortable — we list it separately
echo "<th class='hp-sort-col' data-sort-by='name' style='cursor:pointer;'>Player Name " . sortIcon('name', $sortBy, $sortDir) . "</th>";
echo "<th>Email/Phone</th>";
echo "<th>IS PREMIUM</th>";
echo "<th class='hp-sort-col' data-sort-by='games'  style='cursor:pointer;'>Total Game "    . sortIcon('games',  $sortBy, $sortDir) . "</th>";
echo "<th class='hp-sort-col' data-sort-by='amount' style='cursor:pointer;'>Total Amount "  . sortIcon('amount', $sortBy, $sortDir) . "</th>";
echo "<th class='hp-sort-col' data-sort-by='paid'   style='cursor:pointer;'>Total Payment " . sortIcon('paid',   $sortBy, $sortDir) . "</th>";
echo "<th class='hp-sort-col' data-sort-by='due'    style='cursor:pointer;'>Total Due "     . sortIcon('due',    $sortBy, $sortDir) . "</th>";
echo "<th>Action</th>";
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($pagePlayers as $idx => $p) {
    $data        = $p['data'];
    $user_id     = $data['ID'];
    $profile_pic = !empty($data['PROFILE_IMAGE']) ? 'profile_img/' . $data['PROFILE_IMAGE'] : '../assets/images/profile.jpg';
    $isPremium   = (isset($data['PREMIUM']) && $data['PREMIUM'] === 'Y');
    $premiumChecked = $isPremium ? 'checked' : '';
    $switchId    = 'premium-switch-' . $user_id;
    $sl          = $offset + $idx + 1;
    $currency    = '$';

    echo "<tr>
        <th scope='row'>{$sl}</th>
        <td><div class='profile_pic'><img src='{$profile_pic}' class='img-fluid' alt='profile pic' /></div></td>
        <td><strong>{$data['NAME']}</strong></td>
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
        <td class='text-center'>{$p['gameCount']}</td>
        <td>{$currency} " . number_format($p['totalAmount'], 2) . "</td>
        <td>{$currency} " . number_format($p['totalPaid'],   2) . "</td>
        <td>" . ($p['totalDue'] > 0
            ? "<span class='text-danger fw-semibold'>{$currency} " . number_format($p['totalDue'], 2) . "</span>"
            : "<span class='text-success fw-semibold'>{$currency} " . number_format($p['totalDue'], 2) . "</span>")
        . "</td>
        <td><button type='button' class='playPaymentModal_open btn btn-primary btn-sm' data-id='{$user_id}'>View Ledger</button></td>
    </tr>";
}

echo '</tbody></table></div>';

// ─── 6. Footer: count info + pagination ──────────────────────────────
$from = $totalPlayers > 0 ? $offset + 1 : 0;
$to   = min($offset + $perPage, $totalPlayers);

echo "<div class='d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 px-1'>";
echo "<div class='d-flex align-items-center gap-2'>";
echo "<small class='text-muted me-1'>Showing {$from}–{$to} of {$totalPlayers} player" . ($totalPlayers !== 1 ? 's' : '') . "</small>";
echo "<label class='mb-0 text-muted ms-2' style='font-size: 0.8rem; white-space: nowrap;'>Rows per page:</label>";
echo "<select id='hp-pagesize' class='form-select form-select-sm' style='width: auto; height: 31px;'>";
foreach ($allowedPageSizes as $sz) {
    $sel = ($sz === $perPage) ? ' selected' : '';
    echo "<option value='{$sz}'{$sel}>{$sz}</option>";
}
echo "</select>";
echo "</div>";

if ($totalPages > 1) {
    echo "<nav><ul class='pagination pagination-sm mb-0 hp-pagination'>";

    // Prev
    $prevDisabled = $page <= 1 ? 'disabled' : '';
    echo "<li class='page-item {$prevDisabled}'><a class='page-link' href='#' data-page='" . ($page - 1) . "' data-sort-by='{$sortBy}' data-sort-dir='{$sortDir}'>‹</a></li>";

    // Page numbers (show up to 5 around current)
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    if ($start > 1) {
        echo "<li class='page-item'><a class='page-link' href='#' data-page='1' data-sort-by='{$sortBy}' data-sort-dir='{$sortDir}'>1</a></li>";
        if ($start > 2) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? 'active' : '';
        echo "<li class='page-item {$active}'><a class='page-link' href='#' data-page='{$i}' data-sort-by='{$sortBy}' data-sort-dir='{$sortDir}'>{$i}</a></li>";
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
        echo "<li class='page-item'><a class='page-link' href='#' data-page='{$totalPages}' data-sort-by='{$sortBy}' data-sort-dir='{$sortDir}'>{$totalPages}</a></li>";
    }

    // Next
    $nextDisabled = $page >= $totalPages ? 'disabled' : '';
    echo "<li class='page-item {$nextDisabled}'><a class='page-link' href='#' data-page='" . ($page + 1) . "' data-sort-by='{$sortBy}' data-sort-dir='{$sortDir}'>›</a></li>";

    echo "</ul></nav>";
}

echo "</div>";

$conn->close();
