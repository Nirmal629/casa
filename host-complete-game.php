<!-------host-complete-game----->
<div class="hostcomplete_game">
     <?php
$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
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
                        <!--<div class="col-auto">-->
                        <!--    <select class="form-select" id="com_year" aria-label="Default select example">-->
                        <!--        <option value="">Select Year</option>-->
                        <!--        <option value="2024">2024</option>-->
                        <!--        <option value="2025">2025</option>-->
                        <!--        <option value="2026">2026</option>-->
                        <!--        <option value="2027">2027</option>-->
                        <!--        <option value="2028">2028</option>-->
                        <!--        <option value="2029">2029</option>-->
                        <!--        <option value="2030">2030</option>-->
                        <!--    </select>-->
                        <!--</div>-->
                        <!--<div class="col-auto">-->
                        <!--    <select class="form-select" id="com_month" aria-label="Default select example">-->
                        <!--        <option value="">Select the Month</option>-->
                        <!--        <option value="1">January</option>-->
                        <!--        <option value="2">February</option>-->
                        <!--        <option value="3">March</option>-->
                        <!--        <option value="4">April</option>-->
                        <!--        <option value="5">May</option>-->
                        <!--        <option value="6">June</option>-->
                        <!--        <option value="7">July</option>-->
                        <!--        <option value="8">August</option>-->
                        <!--        <option value="9">September</option>-->
                        <!--        <option value="10">October</option>-->
                        <!--        <option value="11">November</option>-->
                        <!--        <option value="12">December</option>-->
                        <!--    </select>-->
                        <!--</div>-->
                        <div class="col-auto">

                            <select class="form-select py-0 px-2" id="com_year" aria-label="Default select example" style="width: 75px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                                <option value="">Year</option>
                                <?php
                                for ($year = 2025; $year <= $currentYear; $year++) {
                                    $selected = ($year == $currentYear) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select class="form-select py-0 px-2" id="com_month" aria-label="Default select example" style="width: 70px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                                <option value="">Month</option>
                                <?php
                                $months = [
                                    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                                ];
                                foreach ($months as $num => $name) {
                                    $selected = ($num == $currentMonth) ? 'selected' : '';
                                    echo "<option value=\"$num\" $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!--<div class="col-auto">-->
                        <!--    <button type="button" class="btn btn-primary" id="com_filter">Submit</button>-->
                        <!--    <button type="button" class="btn btn-danger" id="com_reset">Reset</button>-->
                        <!--</div>-->
                        <div class="col-auto ms-auto d-flex gap-1">
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
                        <!--<th scope="col">Skill</th>-->
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
                <tbody>
                    <?php
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    include_once __DIR__ . '/dbConnection.php';
        
        $sql = "SELECT * FROM ca_events WHERE STATUS!='Active' AND HOST_ID='" . intval($_SESSION['user_id']) . "' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth' ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; // Adjust the query based on your conditions
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
        $host_name = $event['HOST_NAME'];
        // $event_date = date('D, d M Y, h:i A', strtotime($event['EVENT_DATE'] . ' ' . $event['EVENT_TIME'])); -- Anurag
        $event_date = date('D, d M Y', strtotime($event['EVENT_DATE']));
        $event_venue = $event['EVENT_VENUE'];
        $event_cost = $event['EVENT_COST'];
        $event_currency = $event['EVENT_CURRENCY'];
        $event_category = $event['EVENT_CATEGORY'];
        $gender_category = $event['GENDER_CATEGORY'];
        $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
        $event_description = $event['EVENT_DESCRIPTION'];
        $event_message = $event['EVENT_MESSAGE'];
        $event_type = $event['EVENT_TYPE'];
        $event_status = $event['STATUS'];
        $facility_cost = $event['FACILITY_COST'];
        $accessories_cost = $event['ACCESSORIES_COST'];
        $snacks_cost = $event['SNACKS_COST'];
        $total_event_cost = $event['TOTAL_EVENT_COST'];
        $total_player_cost = $event['TOTAL_PLAYER_COST'];
        $profit_loss = $event['PROFIT_LOSS'];
        
        $jsonStringy = json_encode($event);
        
        // You can also fetch the image and other dynamic content here.
        $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
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
                <td><button type="button" class="btn eye_btn matchviewmodal_open" data-id='.$event_id.' data-user-id='.$host_id.'><i class="fa-solid fa-eye"></i></button></td>
                <td><span class="' . $status_class . '">' . $event_status . '</span></td>
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
            echo "<p>No events found.</p>";
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
                        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">
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
                                <img src="assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="assets/images/profile.jpg" class="img-fluid" alt="..">
                            </div>
                            <div class="plyardetails">
                                <h6 class="name">kartik gg</h6>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="Profiletable_wrap">
                            <div class="hostProfile_small">
                                <img src="assets/images/profile.jpg" class="img-fluid" alt="..">
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

<section class="customModal_wrap hostgamevieww_modal">
    <div class="customModal_body" id="hostplaypay">
        <h6 class="customModal_head">View History</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <hr />
            <div class="" id="playerList">
                

            </div>

        </div>
    </div>
</section>

<section class="customModal_wrap playergameview_modal">
    <div class="customModal_body" id="playerplaypay" style='max-width:70%'>
        <h6 class="customModal_head">View Player</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <hr />
            <div class="" id="playerList">
                

            </div>

        </div>
    </div>
</section>