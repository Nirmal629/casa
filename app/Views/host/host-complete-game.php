<!-------host-complete-game----->
<div class="hostcomplete_game">
<?php
use App\Config\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = Database::getMysqli();

$host_id = intval($_SESSION['user_id'] ?? 0);
$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)

// Check if current month has completed events for this host; if not, default to the latest month with completed events
$defaultYear = $currentYear;
$defaultMonth = $currentMonth;

$checkCurrent = mysqli_query($conn, "SELECT ID FROM ca_events WHERE STATUS!='Active' AND HOST_ID='$host_id' AND YEAR(EVENT_DATE)='$currentYear' AND MONTH(EVENT_DATE)='$currentMonth' LIMIT 1");
if (!$checkCurrent || mysqli_num_rows($checkCurrent) == 0) {
    $latestEventCheck = mysqli_query($conn, "SELECT YEAR(EVENT_DATE) as yr, MONTH(EVENT_DATE) as mo FROM ca_events WHERE STATUS!='Active' AND HOST_ID='$host_id' ORDER BY EVENT_DATE DESC LIMIT 1");
    if ($latestEventCheck && mysqli_num_rows($latestEventCheck) > 0) {
        $latestRow = mysqli_fetch_assoc($latestEventCheck);
        $defaultYear = intval($latestRow['yr']);
        $defaultMonth = intval($latestRow['mo']);
    }
}
?>
    <div class="custom_card">
        <!--<h6 class="card_heading">The Completed Games</h6>-->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-primary">The Completed Games</h6>
            <button id="refreshBtn" class="btn btn-sm btn-outline-secondary py-0" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>  
        
        <div class="mb-3">
                <form>
                    <div class="row g-1 align-items-center">
                        <div class="col-auto">
                            <select class="form-select py-0 px-2" id="com_year" aria-label="Default select example" style="width: 75px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                                <option value="">Year</option>
                                <?php
                                $startYear = 2024;
                                $maxYear = max(intval(date('Y')) + 2, 2030);
                                for ($year = $startYear; $year <= $maxYear; $year++) {
                                    $selected = ($year == $defaultYear) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select class="form-select py-0 px-2" id="com_month" aria-label="Default select example" style="width: 70px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                                <option value="">Month</option>
                                <?php
                                foreach (getMonthOptions(true) as $num => $name) {
                                    $selected = ($num == $defaultMonth) ? 'selected' : '';
                                    echo "<option value=\"$num\" $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-auto ms-auto gap-1 d-none">
                            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center" id="com_filter" title="Submit" style="width: 32px; height: 31px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.552 2.55 5.92-5.903z"/>
                                </svg>
                            </button>
                        
                            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center justify-content-center" id="com_reset" title="Reset" style="width: 32px; height: 31px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                                    <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
        </div>

        <div class="table-responsive" id="comp_game" style="font-size: 0.72rem;">
            <table class="table table-success table-striped table-bordered table-sm text-nowrap align-middle">
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
                <tbody>
                    <?php
        $whereConditions = ["STATUS!='Active'", "HOST_ID='$host_id'"];
        if (!empty($defaultYear)) {
            $whereConditions[] = "YEAR(EVENT_DATE) = '$defaultYear'";
        }
        if (!empty($defaultMonth)) {
            $whereConditions[] = "MONTH(EVENT_DATE) = '$defaultMonth'";
        }
        $sql = "SELECT * FROM ca_events WHERE " . implode(" AND ", $whereConditions) . " ORDER BY EVENT_DATE DESC, EVENT_TIME DESC";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $joinCounts = [];
            $countsSql = "SELECT GAME_ID, COUNT(*) AS total_join, SUM(CASE WHEN CONFIRMED = 'Y' THEN 1 ELSE 0 END) AS total_confirmed FROM ca_gamejoin WHERE GAME_ID IN (SELECT ID FROM ca_events WHERE " . implode(" AND ", $whereConditions) . ") GROUP BY GAME_ID";
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
        ?>
                    
                </tbody>
            </table>
        </div>

    </div>
</div>

<!------View-Modal------>
<section class="customModal_wrap matchviewmodal">
    <div class="customModal_body">
        <h6 class="customModal_head">Match Details</h6>
        <button class="customModal_close btn matchviewmodal_close">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content matchdetails_sec">
            <div class="custom_card">
                <div class="sectionheading_wrap mb-4">
                    <div>
                        <h2 class="heading">Badminton play</h2>
                        <h6 class="sub_text">Hosted by Anurag</h6>
                    </div>

                    <div class="hostProfile_big">
                        <img src="../assets/images/profile.jpg" class="img-fluid" alt="..">
                    </div>
                </div>

                <div class="dateandtime_wrap">
                    <i class="fa-regular fa-clock"></i>
                    <div class="">
                        <h4 class="date">Tuesday, 24 Dec 2024</h4>
                        <p class="time">08:00 PM to 09:00 PM</p>
                    </div>
                </div>

                <div class="dateandtime_wrap">
                    <i class="fa-solid fa-location-dot"></i>
                    <div class="">
                        <p class="time">Lorem ipsum dolor sit amet consectetur adipisicing elit</p>
                    </div>
                </div>

                <hr>

                <h4 class="sub_text" style="text-decoration: underline;">Game Instructions</h4>
                <p class="desc" style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-regular fa-hand-point-right"></i>
                    Beginner to Professional
                </p>

                <h4 class="heading">Player List</h4>
                <ul class="playerlist_wrap">
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="../assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="../assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="../assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="../assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                </ul>

                <hr />

                <div class="d-flex align-items-center justify-content-center pt-3 gap-1">
                    <button type="submit" class="Cancelgame_btn btn">Cancel</button>
                    <button type="submit" class="joingame_btn btn">Completed</button>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Unified Completed Game Modal (View Player + View History) -->
<section class="customModal_wrap unified_game_modal hostgamevieww_modal hostgameview_modal playergameview_modal" id="unifiedGameCompleteModal">
    <div class="customModal_body unified_game_modal_body" id="unifiedGameModalBody">
        <div class="unified-modal-header d-flex align-items-center justify-content-between pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <h6 class="customModal_head mb-0" id="unifiedModalTitle">View Player</h6>
                <span class="badge bg-light text-dark border unified-game-id-badge" style="display: none; font-size: 0.8rem;"></span>
            </div>
            <button class="customModal_close btn p-0 text-muted" type="button" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="unified-modal-tabs-bar py-2 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm unified-game-tab-btn active" data-tab="players" id="btnTabPlayers">
                    <i class="fa-solid fa-users me-1"></i> View Player
                    <span class="badge bg-light text-dark rounded-pill ms-1 player-count-badge" style="display: none; font-size: 0.72rem;">0</span>
                </button>
                <button type="button" class="btn btn-sm unified-game-tab-btn" data-tab="history" id="btnTabHistory">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> View History
                    <span class="badge bg-light text-dark rounded-pill ms-1 history-count-badge" style="display: none; font-size: 0.72rem;">0</span>
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" id="unifiedModalRefreshBtn" title="Refresh">
                    <i class="fa-solid fa-arrows-rotate"></i> Refresh
                </button>
            </div>
        </div>

        <div class="customModal_content pt-3">
            <!-- Hidden fallback container for any legacy scripts -->
            <div id="playerList" style="display: none;"></div>

            <!-- Tab Pane 1: View Player -->
            <div class="unified-game-pane" id="panePlayers">
                <div class="table-responsive" id="unifiedPlayerList">
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                        <div>Loading players...</div>
                    </div>
                </div>
            </div>

            <!-- Tab Pane 2: View History -->
            <div class="unified-game-pane" id="paneHistory" style="display: none;">
                <div class="table-responsive" id="unifiedHistoryList">
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                        <div>Loading payment history...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>