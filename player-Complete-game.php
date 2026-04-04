<?php
date_default_timezone_set('America/Toronto');
 const DATABASE_NAME='casa_test';
        const USERNAME="casa_test";
        const PASSWORD="casa_test123#";
        
        // Database configuration
        $host = "localhost"; // Database host (e.g., localhost)
        
        // Create connection
        $conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
        $select_host = mysqli_query($conn,"select * from ca_users where USERTYPE!='Player' and LOG_STATUS='Y' and DEL_STATUS='N'");
        $check_player = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
        $fetch_player = mysqli_fetch_assoc($check_player);
        $premiumStatus = $fetch_player['PREMIUM'];
?>
<!----player-Complete-game-------->
<?php if ($premiumStatus != 'Y') { ?>
            <p style="color:red; font-weight:bold;text-align:center">Contact admin to enable the premium account</p>
        <?php } ?>
<div style="<?php echo ($premiumStatus != 'Y') ? 'opacity:0; pointer-events:none;' : ''; ?>">
    <div class="mb-4" >
        
                    <form>
                        <div class="row">
                                <div class="col-auto">
                                <select class="form-select" id="host" aria-label="Default select example">
                                    <option value="">All</option>
                                    <?php
                                    while ($fetchUser = mysqli_fetch_assoc($select_host)) {
                                        $selected = ($fetchUser['ID'] == 21) ? 'selected' : ''; // Check if ID is 7
                                    ?>
                                        <option value="<?= $fetchUser['ID'] ?>"><?= $fetchUser['NAME'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <!--<div class="col-auto">-->
                            <!--    <select class="form-select" id="comyear" aria-label="Default select example">-->
                            <!--        <option value="">Select the Year</option>-->
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
                            <!--    <select class="form-select" id="commonth" aria-label="Default select example">-->
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
                                <select class="form-select" id="comyear" aria-label="Default select example">
                                    <option value="">Year</option>
                                    <?php
                                    for ($year = 2024; $year <= 2030; $year++) {
                                        $selected = ($year == $currentYear) ? 'selected' : '';
                                        echo "<option value=\"$year\" $selected>$year</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" id="commonth" aria-label="Default select example">
                                    <option value="">Month</option>
                                    <?php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                    foreach ($months as $num => $name) {
                                        $selected = ($num == $currentMonth) ? 'selected' : '';
                                        echo "<option value=\"$num\" $selected>$name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
    
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" id="play_com_filter">Submit</button>
                                <button type="button" class="btn btn-danger" id="play_com_reset">Reset</button>
                            </div>
                        </div>
                    </form>
            </div>
    <div class="discoverGames_wraper playCom plyerGame_wrapper">
        <!--<div class="discoverGames_card">-->
        <!--    <a href="player-match.php">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">Singles regular</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
    
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book">Only 1 Slots</div>-->
        <!--        </div>-->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="d-flex align-items-center justify-content-between gap-1 w-100">-->
        <!--            <div class="play_status">-->
        <!--                <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--                <span>Beginner - Professional</span>-->
        <!--            </div>-->
        <!--            <div class="play_status">-->
        <!--                <span style="background-color: green; color: #fff;">Completed</span>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="player-match.php">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">tournament</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book"><b>2</b>/5 Going</div>-->
        <!--        </div>-->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="d-flex align-items-center justify-content-between gap-1 w-100">-->
        <!--            <div class="play_status">-->
        <!--                <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--                <span>Beginner - Professional</span>-->
        <!--            </div>-->
        <!--            <div class="play_status">-->
        <!--                <span style="background-color: green; color: #fff;">Completed</span>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="player-match.php">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">Singles regular</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book"><b>2</b>/4 Going</div>-->
        <!--        </div>-->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="d-flex align-items-center justify-content-between gap-1 w-100">-->
        <!--            <div class="play_status">-->
        <!--                <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--                <span>Beginner - Professional</span>-->
        <!--            </div>-->
        <!--            <div class="play_status">-->
        <!--                <span style="background-color: green; color: #fff;">Completed</span>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="player-match.php">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">Singles regular</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book"><b>12</b>/16 Going</div>-->
        <!--        </div>-->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="d-flex align-items-center justify-content-between gap-1 w-100">-->
        <!--            <div class="play_status">-->
        <!--                <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--                <span>Beginner - Professional</span>-->
        <!--            </div>-->
        <!--            <div class="play_status">-->
        <!--                <span style="background-color: green; color: #fff;">Completed</span>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="player-match.php">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">tournament</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book">Only 2 Slots</div>-->
        <!--        </div>-->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="d-flex align-items-center justify-content-between gap-1 w-100">-->
        <!--            <div class="play_status">-->
        <!--                <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--                <span>Beginner - Professional</span>-->
        <!--            </div>-->
        <!--            <div class="play_status">-->
        <!--                <span style="background-color: green; color: #fff;">Completed</span>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
         <?php
            // Assuming you have a connection to the database ($conn)
            $sql = "SELECT * FROM ca_events WHERE STATUS='Completed' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth'  ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; // Adjust the query based on your conditions
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) > 0) {
                while ($event = mysqli_fetch_assoc($result)) {
            $event_id = $event['ID'];
            $host_name = $event['HOST_NAME'];
            $event_date = date('D, d M Y, h:i A', strtotime($event['EVENT_DATE'] . ' ' . $event['EVENT_TIME']));
            $event_venue = $event['EVENT_VENUE'];
            $event_cost = $event['EVENT_COST'];
            $event_currency = $event['EVENT_CURRENCY'];
            $event_category = $event['EVENT_CATEGORY'];
            $gender_category = $event['GENDER_CATEGORY'];
            $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
            $event_description = $event['EVENT_DESCRIPTION'];
            $event_message = $event['EVENT_MESSAGE'];
            $event_type = $event['EVENT_TYPE'];
            $event_canceldate = $event['CANCEL_DATE']!='' ? date('D, d M Y', strtotime($event['CANCEL_DATE'])) : '';
            $event_cancelTime = date('h:i A', strtotime($event['CANCEL_TIME']));
            $event_toTime = $event['TO_TIME'];
    
            
            $jsonStringy = json_encode($event);
            $words = explode(" ", $event_message); // Split message into words
            $wrapped_message = implode(" ", array_slice($words, 0, 7)); // Get first 5 words
            
            if (count($words) > 7) {
                $wrapped_message .= " ..."; // Append "..." if more words exist
            }
            
            // You can also fetch the image and other dynamic content here.
            $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
            // echo "select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'";
            // echo "select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'";
            $selectJoin = mysqli_query($conn,"select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'");
            $countRows = mysqli_num_rows($selectJoin);
            $fetchRows = mysqli_fetch_assoc($selectJoin);
            if($countRows > 0 )
            {
                $bgClass = ''; // default
                if ($countRows > 0) {
                    if ($fetchRows['CONFIRMED'] == 'Y') {
                        $bgClass = '#1987543b'; // Joined & Confirmed = green
                    } else {
                        $bgClass = '#ffc10738'; // Joined but not Confirmed = orange
                    }
                }
                
                $totalJoin = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."'");
                $countTotalJoin = mysqli_num_rows($totalJoin);
                
                $totalConfirmed = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."' and CONFIRMED='Y'");
                $countTotalConfirmed = mysqli_num_rows($totalConfirmed);
            echo "
            <div class='discoverGames_card player_cards' style='background-color:$bgClass'>
                <div class='d-flex flex-wrap align-items-center justify-content-between gap-2' style='margin-bottom: 8px; background-color: #0d6efda1; padding: 8px 10px; border-radius: 8px 8px 0px 0px;'>
                    <p class='text-white desc fw-bold m-0' style='font-size: 85%;'>$event_category - $gender_category</p>
                    <div class='accessories_wrap'>
                    
                        <span style='padding: 2px 6px;' class='btn text-white " . ($event_type == 'Public' ? 'bg-info' : 'bg-success') . "'>
                            $event_type
                        </span>
                        
                            <input type='hidden' id='data_$event_id' value='$jsonStringy'/>
                            <input type='hidden' id='user_$event_id' value='" . $_SESSION['user_id'] . "'/>
                    </div>
                </div>
                <div class='d-flex align-items-center justify-content-between gap-1 mb-2'>
                    <div>
                        <!--<div class='d-flex align-items-center'>
                            <div class='profile_pic'><img src='$profile_pic' alt='profile' /></div>
                            <div class='profile_pic2'><img src='$profile_pic' alt='profile' /></div>
                        </div>-->
                        <p style='color:black'><strong>Host: $host_name</strong></p>
                        
                        <div class='d-flex align-items-start gap-1 mb-1 p-1' style='border: 1px solid red; border-radius: 6px;'>
                            <i class='fa-solid fa-clock' style='font-size: 80%; color: red; margin-top: 3px;'></i>
                            <h4 class='date_time m-0' style='color: red;'><span>$event_date</span><span> to </span><span>" . date('h:i A', strtotime($event_toTime)) . "</span></h4>
                        </div>
                        <p class='location mb-1'>
                            <i class='fa-solid fa-location-dot'></i>
                            $event_venue
                        </p>
                        <p style='font-size:60%; margin-bottom: 5px;'>Freeze Date & Time: $event_canceldate $event_cancelTime</p>
                    </div>
                    <div class='access_datebox'>
                        <div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                            <span>Total Joined:</span>
                            <span class='slots-count badge bg-secondary m-0'
                                  tabindex='0'
                                  data-bs-toggle='popover'
                                  data-bs-trigger='click'
                                  data-bs-placement='top'
                                  data-bs-content='Total Joined: $countTotalJoin'>
                                $countTotalJoin
                            </span>
                        </div>
                        
                        <div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                            <span>Confirmed:</span>
                            <span class='slots-count badge bg-secondary m-0'
                                  tabindex='0'
                                  data-bs-toggle='popover'
                                  data-bs-trigger='click'
                                  data-bs-placement='top'
                                  data-bs-content='Total Confirmed: $countTotalConfirmed'>
                                $countTotalConfirmed
                            </span>
                        </div>
                         
                        <div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                            <span>Game Info:</span>
                            <span class='badge bg-info m-0' style='cursor: pointer;'
                                  tabindex='0'
                                  data-bs-toggle='popover'
                                  data-bs-trigger='click'
                                  data-bs-placement='top'
                                  data-bs-html='true'
                                  data-bs-content='$event_description_html'>
                                <i class='fa-solid fa-info'></i>
                            </span>
                        </div>
                        
                    </div>
                </div>
                
                
                <div class='d-flex align-items-center' style='gap: 15px; margin-bottom: 10px;'>
                    <div class='d-flex align-items-center'>
                        <p class='gamesms_text'>$wrapped_message</p>
                    </div>
                    <!--<div class='Slots_book' style='visibility:hidden'>Only 2 Slots</div>-->
                </div>
    
                <div class='d-flex align-items-center justify-content-start gap-1'>
                    <div class='play_status'>
                        <img src='assets/images/Icons/SP5.png' class='img-fluid' alt='Game Icon' />
                        <span>$gender_skill_level</span>
                    </div>
                    <div class='play_status d-flex flex-wrap align-items-start gap-1'>
                    <span>
                        Court: " . ($event_court == 0 ? 'NA' : $event_court) . "
                        </span>
            
                        <span>
                            $event_currency $event_cost
                        </span>
                    
                    </div>
                </div>
            </div>";
                }
                
                }
            } else {
                echo "<p>No events found.</p>";
            }
            ?>
    </div>
</div>
<section class="customModal_wrap hostgameview_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">View Players</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
<hr />
        <div class="customModal_content">
            <div class="" id="playerList">
                <div class="Profiletable_wrap">
                    <div class="hostProfile_small">
                        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">
                    </div>
                    <div class="plyardetails">
                        <h6 class="name">kartik gg</h6>
                        <div class="invite_btn">
                            <span>Invite</span>
                            <input type="checkbox" />
                        </div>
                    </div>
                </div>


            </div>

        </div>
    </div>
</section>