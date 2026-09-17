<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
                <th scope="col">Players</th>
                <th scope="col">Rollback</th>
                <th scope="col">View</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>';

if ($result && mysqli_num_rows($result) > 0) {
    $i = 1;
    $totalFacilityCost = 0;
    $totalAccessoriesCost = 0;
    $totalSnacksCost = 0;
    $totalEventCost = 0;
    $totalPlayerCost = 0;
    $totalProfitLoss = 0;
    while ($event = mysqli_fetch_assoc($result)) {
        $event_id = $event['ID'];
        $event_date = date('D, d M Y', strtotime($event['EVENT_DATE']));
        $event_venue = $event['EVENT_VENUE'];
        $event_category = $event['EVENT_CATEGORY'];
        $facility_cost = $event['FACILITY_COST'];
        $accessories_cost = $event['ACCESSORIES_COST'];
        $snacks_cost = $event['SNACKS_COST'];
        $total_event_cost = $event['TOTAL_EVENT_COST'];
        $total_player_cost = $event['TOTAL_PLAYER_COST'];
        $profit_loss = $event['PROFIT_LOSS'];
        $event_status = $event['STATUS'];

        $status_class = ($event_status == 'Completed') ? 'green' : 'red';
        
        $totalJoin = mysqli_query($conn, "SELECT ID FROM ca_gamejoin WHERE GAME_ID='" . $event['ID'] . "'");
        $countTotalJoin = $totalJoin ? mysqli_num_rows($totalJoin) : 0;
        
        $totalConfirmed = mysqli_query($conn, "SELECT ID FROM ca_gamejoin WHERE GAME_ID='" . $event['ID'] . "' AND CONFIRMED='Y'");
        $countTotalConfirmed = $totalConfirmed ? mysqli_num_rows($totalConfirmed) : 0;
        
        $totalFacilityCost += floatval($facility_cost);
        $totalAccessoriesCost += floatval($accessories_cost);
        $totalSnacksCost += floatval($snacks_cost);
        $totalEventCost += floatval($total_event_cost);
        $totalPlayerCost += floatval($total_player_cost);
        $totalProfitLoss += floatval($profit_loss);

        echo '<tr>
                <th scope="row">' . $i . '</th>
                <td>' . htmlspecialchars($event_date) . '</td>
                <td>' . htmlspecialchars($event_venue) . '</td>
                <td>' . htmlspecialchars($event_category) . '</td>
                <td>' . htmlspecialchars($facility_cost) . '</td>
                <td>' . htmlspecialchars($accessories_cost) . '</td>
                <td>' . htmlspecialchars($snacks_cost) . '</td>
                <td>' . htmlspecialchars($total_event_cost) . '</td>
                <td>' . htmlspecialchars($total_player_cost) . '</td>
                <td>' . htmlspecialchars($profit_loss) . '</td>
                <td><span class="slots-count badge bg-warning text-dark rounded-pill">' . $countTotalJoin . '</span></td>
                <td><span class="slots-count badge bg-success text-light rounded-pill">' . $countTotalConfirmed . '</span></td>
                <td><button type="button" class="btn played_btn playerviewmodal_open" data-id=' . $event_id . ' data-user-id=' . $host_id . '><i class="fa-solid fa-eye"></i></button></td>
                <td><button type="button" class="btn btn-primary rollback_btn" data-id=' . $event_id . ' data-user-id=' . $host_id . '>Rollback</button></td>
                <td><button type="button" class="btn eye_btn matchviewmodal_open" data-id="' . $event_id . '" data-user-id="' . $host_id . '"><i class="fa-solid fa-eye"></i></button></td>
                <td><span class="' . $status_class . '">' . htmlspecialchars($event_status) . '</span></td>
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
        <td colspan="6"></td>
      </tr>';
} else {
    echo '<tr><td colspan="16" class="text-center text-muted py-3">No events found.</td></tr>';
}

echo '</tbody></table>';
?>