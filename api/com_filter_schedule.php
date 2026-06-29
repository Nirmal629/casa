<?php
session_start();
include('dbConnection.php');
if($_POST['type']=='filter')
{
echo '<table class="table table-success table-striped table-bordered">
        <thead>
            <tr>
                <th scope="col">Sl. No.</th>
                <th scope="col">Date and Time</th>
                <th scope="col">Venue</th>
                <th scope="col">Event Type</th>
                <th scope="col">Event Skill</th>
                <th scope="col">Facility Cost</th>
                <th scope="col">Assessories Cost</th>
                <th scope="col">Snacks Cost</th>
                <th scope="col">Total Event Cost</th>
                <th scope="col">Total Player Cost</th>
                <th scope="col">Profit Loss</th>
                <th scope="col">Total Joined</th>
                <th scope="col">Total Confirmed</th>
                <th scope="col">Played By</th>
                                        <th scope="col">Rollback</th>

                <th scope="col">View</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>';

// Assuming you have a connection to the database ($conn)
if (!empty($_POST['year'])) {
            $conditions[] = "YEAR(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['year']) . "'";
        }
        
        if (!empty($_POST['month'])) {
            $conditions[] = "MONTH(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['month']) . "'";
        }
$sql = "SELECT * FROM ca_events WHERE STATUS!='Active' AND HOST_ID='".$_SESSION['user_id']."'"; // Adjust the query based on your conditions
if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY EVENT_DATE DESC, EVENT_TIME DESC";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $i = 1;
    $totalFacilityCost = 0;
            $totalAccessoriesCost = 0;
            $totalSnacksCost = 0;
            $totalEventCost = 0;
            $totalPlayerCost = 0;
            $totalProfitLoss = 0;
    while ($event = mysqli_fetch_assoc($result)) {
        $event_id = $event['ID'];
        $host_id = $event['HOST_ID'];
        $event_date = date('D, d M Y, h:i A', strtotime($event['EVENT_DATE'] . ' ' . $event['EVENT_TIME']));
        $event_venue = $event['EVENT_VENUE'];
        $event_type = $event['EVENT_TYPE'];
        $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
        $event_status = $event['STATUS'];
        $event_category = $event['EVENT_CATEGORY'];
        $facility_cost = $event['FACILITY_COST'];
        $accessories_cost = $event['ACCESSORIES_COST'];
        $snacks_cost = $event['SNACKS_COST'];
        $total_event_cost = $event['TOTAL_EVENT_COST'];
        $total_player_cost = $event['TOTAL_PLAYER_COST'];
        $profit_loss = $event['PROFIT_LOSS'];


        $status_class = ($event_status == 'Completed') ? 'green' : 'red';
        
        $totalJoin = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."'");
        $countTotalJoin = mysqli_num_rows($totalJoin);
        
        $totalConfirmed = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."' and CONFIRMED='Y'");
        $countTotalConfirmed = mysqli_num_rows($totalConfirmed);
        
        $totalFacilityCost += floatval($facility_cost);
        $totalAccessoriesCost += floatval($accessories_cost);
        $totalSnacksCost += floatval($snacks_cost);
        $totalEventCost += floatval($total_event_cost);
        $totalPlayerCost += floatval($total_player_cost);
        $totalProfitLoss += floatval($profit_loss);

        echo '<tr>
                <th scope="row">'.$i.'</th>
                <td>' . $event_date . '</td>
                <td>' . $event_venue . '</td>
                <td>' . $event_category . '</td>
                <td>' . $gender_skill_level . '</td>
                <td>' . $facility_cost . '</td>
                <td>' . $accessories_cost . '</td>
                <td>' . $snacks_cost . '</td>
                <td>' . $total_event_cost . '</td>
                <td>' . $total_player_cost . '</td>
                <td>' . $profit_loss . '</td>
                <td><span class="slots-count badge bg-warning text-dark rounded-pill">
                        '.$countTotalJoin.' 
                    </span></td>
                <td><span class="slots-count badge bg-success text-light rounded-pill">
                        '.$countTotalConfirmed.'
                    </span></td>
                <td><button type="button" class="btn played_btn playerviewmodal_open" data-id='.$event_id.' data-user-id='.$host_id.'><i class="fa-solid fa-eye"></i></button></td>
<td><button type="button" class="btn btn-primary rollback_btn" data-id='.$event_id.' data-user-id='.$host_id.'>Rollback</button></td>
                <td><button type="button" class="btn eye_btn matchviewmodal_open" data-id="'.$event_id.'" data-user-id="'.$host_id.'"><i class="fa-solid fa-eye"></i></button></td>
                <td><span class="' . $status_class . '">' . $event_status . '</span></td>
            </tr>';
        
        $i++;
    }
    echo '<tr style="font-weight:bold; background:#d4edda;">
        <td colspan="5" class="text-end">Total</td>
        <td>' . number_format($totalFacilityCost, 2) . '</td>
        <td>' . number_format($totalAccessoriesCost, 2) . '</td>
        <td>' . number_format($totalSnacksCost, 2) . '</td>
        <td>' . number_format($totalEventCost, 2) . '</td>
        <td>' . number_format($totalPlayerCost, 2) . '</td>
        <td>' . number_format($totalProfitLoss, 2) . '</td>
        <td colspan="6"></td>
      </tr>';
} else {
    echo '<tr><td colspan="7" class="text-center">No events found.</td></tr>';
}

echo '</tbody></table>';
}
else
{
    $currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
    echo '<table class="table table-success table-striped table-bordered">
        <thead>
            <tr>
                <th scope="col">Sl. No.</th>
                <th scope="col">Date and Time</th>
                <th scope="col">Venue</th>
                <th scope="col">Event Type</th>
                <th scope="col">Event Skill</th>
                <th scope="col">Facility Cost</th>
                <th scope="col">Assessories Cost</th>
                <th scope="col">Snacks Cost</th>
                <th scope="col">Total Event Cost</th>
                <th scope="col">Total Player Cost</th>
                <th scope="col">Profit Loss</th>
                <th scope="col">Total Joined</th>
                <th scope="col">Total Confirmed</th>
                <th scope="col">Played By</th>
                                        <th scope="col">Rollback</th>

                <th scope="col">View</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>';

// Assuming you have a connection to the database ($conn)
$sql = "SELECT * FROM ca_events WHERE STATUS!='Active' AND HOST_ID='".$_SESSION['user_id']."' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth' ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; // Adjust the query based on your conditions
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $i = 1;
    while ($event = mysqli_fetch_assoc($result)) {
        $event_id = $event['ID'];
        $host_id = $event['HOST_ID'];
        $event_date = date('D, d M Y, h:i A', strtotime($event['EVENT_DATE'] . ' ' . $event['EVENT_TIME']));
        $event_venue = $event['EVENT_VENUE'];
        $event_type = $event['EVENT_TYPE'];
        $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
        $event_status = $event['STATUS'];

        $status_class = ($event_status == 'Completed') ? 'green' : 'red';
        
        $totalJoin = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."'");
        $countTotalJoin = mysqli_num_rows($totalJoin);
        
        $totalConfirmed = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."' and CONFIRMED='Y'");
        $countTotalConfirmed = mysqli_num_rows($totalConfirmed);
        $event_category = $event['EVENT_CATEGORY'];
        $facility_cost = $event['FACILITY_COST'];
        $accessories_cost = $event['ACCESSORIES_COST'];
        $snacks_cost = $event['SNACKS_COST'];
        $total_event_cost = $event['TOTAL_EVENT_COST'];
        $total_player_cost = $event['TOTAL_PLAYER_COST'];
        $profit_loss = $event['PROFIT_LOSS'];

        echo '<tr>
                <th scope="row">'.$i.'</th>
                <td>' . $event_date . '</td>
                <td>' . $event_venue . '</td>
                <td>' . $event_category . '</td>
                <td>' . $gender_skill_level . '</td>
                <td>' . $facility_cost . '</td>
                <td>' . $accessories_cost . '</td>
                <td>' . $snacks_cost . '</td>
                <td>' . $total_event_cost . '</td>
                <td>' . $total_player_cost . '</td>
                <td>' . $profit_loss . '</td>
                <td><span class="slots-count badge bg-warning text-dark rounded-pill">
                        '.$countTotalJoin.' 
                    </span></td>
                <td><span class="slots-count badge bg-success text-light rounded-pill">
                        '.$countTotalConfirmed.'
                    </span></td>
                                    <td><button type="button" class="btn played_btn playerviewmodal_open" data-id='.$event_id.' data-user-id='.$host_id.'><i class="fa-solid fa-eye"></i></button></td>
<td><button type="button" class="btn btn-primary rollback_btn" data-id='.$event_id.' data-user-id='.$host_id.'>Rollback</button></td>
                <td><button type="button" class="btn eye_btn matchviewmodal_open" data-id="'.$event_id.'" data-user-id="'.$host_id.'"><i class="fa-solid fa-eye"></i></button></td>
                <td><span class="' . $status_class . '">' . $event_status . '</span></td>
            </tr>';
        
        $i++;
    }
} else {
    echo '<tr><td colspan="7" class="text-center">No events found.</td></tr>';
}

echo '</tbody></table>';
}

?>