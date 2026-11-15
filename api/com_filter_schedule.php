<?php
require_once __DIR__ . '/_security.php';
requireApiUser(['Host','Trainer']);
if (session_status() === PHP_SESSION_NONE) {

}
include('dbConnection.php');

$host_id = intval($_SESSION['user_id'] ?? 0);

$conditions = [];
if (!empty($_POST['year'])) {
    $conditions[] = "YEAR(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['year']) . "'";
}

if (!empty($_POST['month'])) {
    $conditions[] = "MONTH(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['month']) . "'";
}

$sql = "SELECT * FROM ca_events WHERE STATUS!='Active' AND HOST_ID='$host_id'";
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY EVENT_DATE DESC, EVENT_TIME DESC";
$result = mysqli_query($conn, $sql);

echo '<table class="table table-success table-striped table-bordered table-sm text-nowrap align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th scope="col">SN</th>
                <th scope="col">Date</th>
                <th scope="col">Venue</th>
                <th scope="col">Type</th>
                <th scope="col">Facility$</th>
                <th scope="col">Accessory$</th>
                <th scope="col">Snack$</th>
                <th scope="col">Event$</th>
                <th scope="col">Player$</th>
                <th scope="col">P&L</th>
                <th scope="col">Joined</th>
                <th scope="col">Confirmed</th>
                <th scope="col">View</th>
                <th scope="col">Rollback</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>';

if ($result && mysqli_num_rows($result) > 0) {
    $whereEvents = "STATUS!='Active' AND HOST_ID='$host_id'";
    if (!empty($conditions)) {
        $whereEvents .= " AND " . implode(" AND ", $conditions);
    }
    $joinCounts = [];
    $countsSql = "SELECT GAME_ID, COUNT(*) AS total_join, SUM(CASE WHEN CONFIRMED = 'Y' THEN 1 ELSE 0 END) AS total_confirmed FROM ca_gamejoin WHERE GAME_ID IN (SELECT ID FROM ca_events WHERE $whereEvents) GROUP BY GAME_ID";
    $countsRes = mysqli_query($conn, $countsSql);
    if ($countsRes) {
        while ($cRow = mysqli_fetch_assoc($countsRes)) {
            $joinCounts[(int)$cRow['GAME_ID']] = [
                'total_join' => (int)$cRow['total_join'],
                'total_confirmed' => (int)$cRow['total_confirmed']
            ];
        }
    }

    $i = 1;
    $totalFacilityCost = 0;
    $totalAccessoriesCost = 0;
    $totalSnacksCost = 0;
    $totalEventCost = 0;
    $totalPlayerCost = 0;
    $totalProfitLoss = 0;
    while ($event = mysqli_fetch_assoc($result)) {
        $event_id = (int)($event['ID'] ?? 0);
        $event_host_id = (int)($event['HOST_ID'] ?? $host_id);
        $event_date = !empty($event['EVENT_DATE']) ? date('D, d M Y', strtotime($event['EVENT_DATE'])) : '';
        $event_venue = (string)($event['EVENT_VENUE'] ?? '');
        $event_category = (string)($event['EVENT_CATEGORY'] ?? '');
        $facility_cost = (string)($event['FACILITY_COST'] ?? '0.00');
        $accessories_cost = (string)($event['ACCESSORIES_COST'] ?? '0.00');
        $snacks_cost = (string)($event['SNACKS_COST'] ?? '0.00');
        $total_event_cost = (string)($event['TOTAL_EVENT_COST'] ?? '0.00');
        $total_player_cost = (string)($event['TOTAL_PLAYER_COST'] ?? '0.00');
        $profit_loss = (string)($event['PROFIT_LOSS'] ?? '0.00');
        $event_status = (string)($event['STATUS'] ?? '');

        $status_class = ($event_status === 'Completed') ? 'green' : 'red';
        $pnlClass = ((float)$profit_loss < 0) ? 'text-danger fw-bold' : (((float)$profit_loss > 0) ? 'text-success fw-bold' : '');

        $countTotalJoin = (int)($joinCounts[$event_id]['total_join'] ?? 0);
        $countTotalConfirmed = (int)($joinCounts[$event_id]['total_confirmed'] ?? 0);

        $totalFacilityCost += (float)$facility_cost;
        $totalAccessoriesCost += (float)$accessories_cost;
        $totalSnacksCost += (float)$snacks_cost;
        $totalEventCost += (float)$total_event_cost;
        $totalPlayerCost += (float)$total_player_cost;
        $totalProfitLoss += (float)$profit_loss;

        echo '<tr>
                <th scope="row" class="text-center">' . $i . '</th>
                <td>' . htmlspecialchars($event_date, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($event_venue, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($event_category, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($facility_cost, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($accessories_cost, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($snacks_cost, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($total_event_cost, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($total_player_cost, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="' . $pnlClass . '">' . htmlspecialchars($profit_loss, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="text-center"><span class="slots-count badge bg-warning text-dark rounded-pill">' . $countTotalJoin . '</span></td>
                <td class="text-center"><span class="slots-count badge bg-success text-light rounded-pill">' . $countTotalConfirmed . '</span></td>
                <td class="text-center"><button type="button" class="btn comp-view-btn playerviewmodal_open matchviewmodal_open" data-id="' . $event_id . '" data-user-id="' . $event_host_id . '" title="View Players & History"><i class="fa-solid fa-eye"></i></button></td>
                <td class="text-center"><button type="button" class="btn btn-sm comp-rollback-btn rollback_btn" data-id="' . $event_id . '" data-user-id="' . $event_host_id . '">Rollback</button></td>
                <td class="text-center"><span class="' . $status_class . '">' . htmlspecialchars($event_status, ENT_QUOTES, 'UTF-8') . '</span></td>
            </tr>';
        $i++;
    }
    echo '<tr style="font-weight:bold; background:#d4edda;">
        <td colspan="4" class="text-end">Total</td>
        <td>' . number_format($totalFacilityCost, 2) . '</td>
        <td>' . number_format($totalAccessoriesCost, 2) . '</td>
        <td>' . number_format($totalSnacksCost, 2) . '</td>
        <td>' . number_format($totalEventCost, 2) . '</td>
        <td>' . number_format($totalPlayerCost, 2) . '</td>
        <td>' . number_format($totalProfitLoss, 2) . '</td>
        <td colspan="5"></td>
      </tr>';
} else {
    echo '<tr><td colspan="15" class="text-center text-muted py-3">No events found.</td></tr>';
}

echo '</tbody></table>';
?>