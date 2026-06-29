<!----Scheduled-game---->
<section class="">
    <?php
$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
?>
    <div class="custom_card">
        <h6 class="card_heading">Upcoming Game</h6>
        <div class="mb-4">
                <form>
                    <div class="row g-1">
                        <div class="col-auto">
                            <select class="form-select py-0 px-2" id="year" aria-label="Default select example" style="width: 75px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
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
                            <select class="form-select py-0 px-2" id="month" aria-label="Default select example" style="width: 70px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
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

                        <div class="col-auto ms-auto d-flex gap-1">
                            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center" id="filter" title="Submit" style="width: 32px; height: 31px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.552 2.55 5.92-5.903z"/>
                                </svg>
                            </button>
                        
                            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center justify-content-center" id="reset" title="Reset" style="width: 32px; height: 31px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                                    <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        <div class="discoverGames_wraper hostWrapper">


        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        include_once __DIR__ . '/dbConnection.php';
        
        $sql = "SELECT * FROM ca_events WHERE HOST_ID='" . intval($_SESSION['user_id']) . "' and STATUS='Active' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth' ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; // Adjust the query based on your conditions
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while ($event = mysqli_fetch_assoc($result)) {
        $event_id = $event['ID'];
        $host_name = $event['HOST_NAME'];
        $event_date = date('D, d M Y, h:i A', strtotime($event['EVENT_DATE'] . ' ' . $event['EVENT_TIME']));
        $event_venue = $event['EVENT_VENUE'];
        $event_cost = $event['EVENT_COST'];
        $event_court = $event['EVENT_DISCOUNT'];
        $event_currency = $event['EVENT_CURRENCY'];
        $event_category = $event['EVENT_CATEGORY'];
        $gender_category = $event['GENDER_CATEGORY'];
        $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
        $event_description = html_entity_decode($event['EVENT_DESCRIPTION'], ENT_QUOTES, 'UTF-8');
        $event_message = html_entity_decode($event['EVENT_MESSAGE'], ENT_QUOTES, 'UTF-8');
        $event_type = $event['EVENT_TYPE'];
        $event_canceldate = $event['CANCEL_DATE']!='' ? date('D, d M Y', strtotime($event['CANCEL_DATE'])) : '';
        $event_cancelTime = date('h:i A', strtotime($event['CANCEL_TIME']));
        
        $jsonStringy = htmlspecialchars(json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        $words = explode(" ", $event_message); // Split message into words
        $wrapped_message = implode(" ", array_slice($words, 0, 7)); // Get first 5 words
        
        if (count($words) > 7) {
            $wrapped_message .= " ..."; // Append "..." if more words exist
        }
        
        $totalJoin = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."'");
        $countTotalJoin = mysqli_num_rows($totalJoin);
        
        $totalConfirmed = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."' and CONFIRMED='Y'");
        $countTotalConfirmed = mysqli_num_rows($totalConfirmed);

        
        // You can also fetch the image and other dynamic content here.
        $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
        
        $event_datetime = strtotime($event['EVENT_DATE']);
        $today = strtotime(date('Y-m-d'));
        
        $is_today = ($event_datetime === $today);
        
        $words_desc = explode(" ", $event_description); // Split message into words
        $wrapped_message_desc = implode(" ", array_slice($words_desc, 0, 7)); // Get first 5 words
        
        if (count($words_desc) > 7) {
            $wrapped_message_desc .= " ..."; // Append "..." if more words exist
        }

        $event_description_html = nl2br(htmlspecialchars($event_description, ENT_QUOTES, 'UTF-8'));
        
        // $eventData = "Event: $event_category - $gender_category\n" .
        //      "Host: $host_name\n" .
        //      "Date & Time: $event_date to " . date('h:i A', strtotime($event['TO_TIME'])) . "\n" .
        //      "Venue: $event_venue\n" .
        //      "Freeze Date & Time: $event_canceldate $event_cancelTime\n" .
        //      "Total Joined: $countTotalJoin\n" .
        //      "Confirmed: $countTotalConfirmed\n" .
        //      "Skill Level: $gender_skill_level\n" .
        //      "Court: " . ($event_court == 0 ? 'NA' : $event_court) . "\n" .
        //      "Cost: $event_currency $event_cost\n" .
        //      "Event Type: $event_type\n" .
        //      "Message: $event_message\n" .
        //      "Description: $event_description";
        // Format date parts for display
$dayName = date('l', strtotime($event['EVENT_DATE']));         // Monday
$dateFormatted = date('d|m|Y', strtotime($event['EVENT_DATE'])); 
$timeFormatted = date('h:i A', strtotime($event['EVENT_TIME'])) . 
                 " to " . 
                 date('h:i A', strtotime($event['TO_TIME']));

// Build player list with numbering
$playerList = "";
$sl = 1;

$playersQuery = mysqli_query($conn, "SELECT ca_users.NAME, ca_gamejoin.CONFIRMED 
                                     FROM ca_gamejoin 
                                     INNER JOIN ca_users ON ca_gamejoin.USER_ID = ca_users.ID
                                     WHERE ca_gamejoin.GAME_ID = '" . $event['ID'] . "' 
                                     ORDER BY ca_users.NAME ASC");

                                    while ($p = mysqli_fetch_assoc($playersQuery)) {
                                        $status = ($p['CONFIRMED'] == 'Y') ? "Confirmed" : "Joined";
                                        $playerList .= sprintf("%02d) %s (%s)\n", $sl, $p['NAME'], $status);
                                        $sl++;
                                    }
                                    
                                    $JOIN_MESSAGE = $event['JOIN_MESSAGE'];
                                    
                                    // Final formatted message
                                    $eventData = <<<EOT
                                    🏸*Casa Badminton Club*

                                    Host:                $host_name
                                    Game:                $event_category
                                    Category:            $gender_category
                                    Level:               $gender_skill_level
                                    Day:                 $dayName
                                    Date:                $dateFormatted
                                    Time:                $timeFormatted
                                    Venue:               $event_venue
                                    ___________
                                    $JOIN_MESSAGE
                                    __________________________________
                                    Please join the game using the portal.
                                    __________________________________
                                    DM admin for manual-join/queries/suggestions.
                                    __________________________________
                                    📞+1 437 981 0512
                                    🌐https://casainfotech.com/staging
                                    EOT;




        echo "
        <div class='discoverGames_card'>
            <div class='d-flex flex-wrap align-items-center justify-content-between gap-2' style='margin-bottom: 8px; background-color: #0d6efda1; padding: 8px 10px; border-radius: 8px 8px 0px 0px;'>
                <p class='text-white desc fw-bold m-0' style='font-size: 85%;'>$event_category - $gender_category</p>
                <div class='accessories_wrap d-flex gap-1'>
                    <span class='view_btn badge btn-success d-flex align-items-center justify-content-center' style='width: 28px; height: 28px; padding: 0;' title='Join Now' data-id='$event_id'><i class='fa fa-user-plus'></i></span>
                    <a href='javascript:void(0)' class='badge btn-info copy_event d-flex align-items-center justify-content-center text-white' style='width: 28px; height: 28px; padding: 0;' title='Copy' data-id='$event_id'>
                        <i class='fa-regular fa-copy'></i>
                    </a>
                    <a href='javascript:void(0)' class='edit_btn badge btn-secondary d-flex align-items-center justify-content-center' style='width: 28px; height: 28px; padding: 0;' title='Edit' data-id='$event_id'>
                        <i class='fa-regular fa-pen-to-square'></i>
                    </a>
                    <a href='javascript:void(0)' data-id='$event_id' class='delete_btn badge btn-danger d-flex align-items-center justify-content-center' style='width: 28px; height: 28px; padding: 0;' title='Delete'>
                        <i class='fa-regular fa-trash-can'></i>
                    </a>
                    <input type='hidden' id='data_$event_id' value='$jsonStringy'/>
                    <input type='hidden' id='event_data_$event_id' value='" . htmlspecialchars($eventData, ENT_QUOTES) . "'>
                </div>
            </div>
           <div class='d-flex flex-column justify-content-between gap-2 mb-2 px-2'>
                <div class='flex-grow-1'>
                    <div class='d-inline-flex align-items-center gap-2 mb-2 p-1 px-2' style='border: 1px solid #dc3545; border-radius: 6px; background: #fff5f5;'>
                        <i class='fa-solid fa-clock' style='font-size: 90%; color: #dc3545;'></i>
                        <h4 class='date_time mb-0' style='color: #dc3545; font-size: 0.85rem; font-weight: 600;'>$event_date<span class='fw-normal text-muted mx-1'>|</span>" . date('h:i A', strtotime($event['TO_TIME'])) . "</h4>
                    </div>
                    
                    <p class='location mb-1 text-truncate' style='max-width: 100%; font-size: 0.85rem; color: #333;'>
                        <i class='fa-solid fa-location-dot text-secondary me-1'></i>
                        $event_venue
                    </p>
                    <p class='text-secondary' style='font-size: 0.7rem; margin-bottom: 5px;'><i class='fa-solid fa-snowflake me-1'></i>Freeze: $event_canceldate $event_cancelTime</p>
                </div>
                <div class='d-flex flex-column gap-2 w-100'>
                    <div class='d-flex gap-2 w-100'>
                        <span class='badge bg-light text-dark border d-flex align-items-center justify-content-between p-2 flex-grow-1'
                              tabindex='0'
                              data-bs-toggle='popover'
                              data-bs-trigger='click'
                              data-bs-placement='top'
                              data-bs-content='Total Joined: $countTotalJoin'>
                            <span class='text-muted fw-normal'>Joined</span> <strong class='fs-6' style='line-height: 1;'>$countTotalJoin</strong>
                        </span>
                        
                        <span class='badge bg-light text-dark border d-flex align-items-center justify-content-between p-2 flex-grow-1'
                              tabindex='0'
                              data-bs-toggle='popover'
                              data-bs-trigger='click'
                              data-bs-placement='top'
                              data-bs-content='Total Confirmed: $countTotalConfirmed'>
                            <span class='text-muted fw-normal'>Confirmed</span> <strong class='fs-6' style='line-height: 1;'>$countTotalConfirmed</strong>
                        </span>
                    </div>

                    <span class='badge bg-dark d-flex align-items-center justify-content-between p-2 w-100 joined_btn' 
                          style='cursor: pointer;' title='View' data-id='$event_id'>
                        <span class='fw-normal'>Players</span> <i class='fa-regular fa-eye fs-6' style='line-height: 1;'></i>
                    </span>
                </div>
            </div>
             <div class='px-2 mb-2'>
                <p class='gamesms_text m-0 p-2 rounded' style='background-color:#e9f2fb; font-size: 0.8rem; color: #0d6efd; border-left: 3px solid #0d6efd;'>$wrapped_message</p>
            </div>
            <div class='d-flex flex-wrap align-items-center justify-content-start gap-1 px-2 pb-2'>
                <span class='badge bg-secondary rounded-pill fw-normal'>$gender_skill_level</span>
                <span class='badge bg-light text-dark border rounded-pill fw-normal'>Court: ". ($event_court == 0 ?'NA':$event_court)."</span>
                <span class='badge bg-light text-dark border rounded-pill fw-normal'>$event_currency $event_cost</span>
                <span class='badge rounded-pill fw-normal text-white " . ($event_type == 'Public' ? 'bg-primary' : 'bg-success') . "'>$event_type</span>
            </div>
            " . (
    !empty($event['JOIN_MESSAGE'])
    ? "<div class='d-flex align-items-center justify-content-start mt-1'
        style='background:#d1e7dd; text-align:center; padding:2px; border-radius:6px;'>
        <span style='color:#0f5132; font-size:10px; font-weight:600'>"
        . htmlspecialchars($event['JOIN_MESSAGE']) .
        "</span>
      </div>"
    : ""
) . "
        </div>";
            }
        } else {
            echo "<p>No events found.</p>";
        }
        ?>

        </div>
    </div>
</section>



<!-----View-modal------->
<section class="customModal_wrap hostgameview_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Add Players</h6>
        <button type="submit" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="row align-items-end g-2">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label" style="visibility:hidden">Search</label>
                <input type="text" name="search" id="search" placeholder="Enter text to search" class="form-control" />
                <input type="hidden" id="playdt" data-game-id="" data-host-id="" />
            </div>
        
            <div class="col-6 col-md-4">
                <label for="sgenderCategoryy" class="form-label">Gender<span>*</span></label>
                <select class="form-select form-control" id="sgenderCategoryy">
                    <option selected value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Mix">Mix</option>
                    <option value="Kid">Kids</option>
                </select>
            </div>
        
            <div class="col-6 col-md-4">
                <label for="sgenderSkillLevell" class="form-label">Level<span>*</span></label>
                <select class="form-select form-control" id="sgenderSkillLevell">
                    <option selected value="Beginner">Beginner</option>
                    <option value="Amateur">Amateur</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Intermediate +">Intermediate+</option>
                    <option value="Advance">Advance</option>
                    <option value="Mix">Mix</option>
                </select>
            </div>
        </div>

        <div class="customModal_content">
            <hr />

            <!--<div class="sectionheading_wrap mb-4">-->
            <!--    <div>-->
            <!--        <h2 class="heading">Badminton play</h2>-->
            <!--        <h6 class="sub_text">Hosted by Anurag</h6>-->
            <!--    </div>-->

            <!--    <div class="hostProfile_big">-->
            <!--        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">-->
            <!--    </div>-->
            <!--</div>-->

            <!--<div class="dateandtime_wrap">-->
            <!--    <i class="fa-regular fa-clock"></i>-->
            <!--    <div class="">-->
            <!--        <h4 class="date">Tuesday, 24 Dec 2024</h4>-->
            <!--        <p class="time">08:00 PM to 09:00 PM</p>-->
            <!--    </div>-->
            <!--</div>-->

            <!--<div class="dateandtime_wrap">-->
            <!--    <i class="fa-solid fa-location-dot"></i>-->
            <!--    <div class="">-->
            <!--        <p class="time">Lorem ipsum dolor sit amet consectetur adipisicing elit</p>-->
            <!--    </div>-->
            <!--</div>-->

            <!--<h4 class="sub_text mt-2" style="text-decoration: underline;">Game Instructions</h4>-->
            <!--<p class="desc" style="display: flex; align-items: center; gap: 10px;">-->
            <!--    <i class="fa-regular fa-hand-point-right"></i>-->
            <!--    Beginner to Professional-->
            <!--</p>-->

            <!--<hr />-->

            <div class="" id="playerList">
                <!--<h4 class="sub_text" style="text-decoration: underline;">Invite Player List</h4>-->

                <!--<div id="search-wrapper">-->
                <!--    <i class="search-icon fas fa-search"></i>-->
                <!--    <input type="text" id="search" placeholder="Search........">-->
                <!--    <button id="search-button">Search</button>-->
                <!--</div>-->

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

                <!--<div class="Profiletable_wrap">-->
                <!--    <div class="hostProfile_small">-->
                <!--        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">-->
                <!--    </div>-->
                <!--    <div class="plyardetails">-->
                <!--        <h6 class="name">Nirmal</h6>-->
                <!--        <div class="invite_btn">-->
                <!--            <span>Invite</span>-->
                <!--            <input type="checkbox" />-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!--<div class="Profiletable_wrap">-->
                <!--    <div class="hostProfile_small">-->
                <!--        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">-->
                <!--    </div>-->
                <!--    <div class="plyardetails">-->
                <!--        <h6 class="name">shyam Roy</h6>-->
                <!--        <div class="invite_btn">-->
                <!--            <span>Invite</span>-->
                <!--            <input type="checkbox" />-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!--<div class="Profiletable_wrap">-->
                <!--    <div class="hostProfile_small">-->
                <!--        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">-->
                <!--    </div>-->
                <!--    <div class="plyardetails">-->
                <!--        <h6 class="name">Susavon</h6>-->
                <!--        <div class="invite_btn">-->
                <!--            <span>Invite</span>-->
                <!--            <input type="checkbox" />-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!--<div class="Profiletable_wrap">-->
                <!--    <div class="hostProfile_small">-->
                <!--        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">-->
                <!--    </div>-->
                <!--    <div class="plyardetails">-->
                <!--        <h6 class="name">Tanmay thakur</h6>-->
                <!--        <div class="invite_btn">-->
                <!--            <span>Invite</span>-->
                <!--            <input type="checkbox" />-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!--<hr />-->

                <!--<div class="d-flex align-items-center justify-content-center pt-3">-->
                <!--    <button type="submit" class="joingame_btn btn">Update Now</button>-->
                <!--</div>-->

            </div>

        </div>
    </div>
</section>

<!---view joined --->
<section class="customModal_wrap hostgameviewjoined_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Joined Players</h6>
        <button type="submit" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="customModal_content">
            <hr />

           

            <div class="" id="playerList_joined">
                <!--<h4 class="sub_text" style="text-decoration: underline;">Invite Player List</h4>-->

                <!--<div id="search-wrapper">-->
                <!--    <i class="search-icon fas fa-search"></i>-->
                <!--    <input type="text" id="search" placeholder="Search........">-->
                <!--    <button id="search-button">Search</button>-->
                <!--</div>-->

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

<!-----Edit-modal--------->
<section class="customModal_wrap hostgameupdate_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Update Game</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form>
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label for="host-namee" class="form-label">Host Name<span>*</span></label>
                        <input type="text" class="form-control" id="host-namee" placeholder="Enter Full Name" value="">
                        <input type="hidden" id="evnt_id" value=""/>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventCountryy" class="form-label">Event Country<span>*</span></label>
                        <input type="text" class="form-control" id="eventCountryy" placeholder="Country">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventProvincee" class="form-label">Event Province<span>*</span></label>
                        <input type="text" class="form-control" id="eventProvincee" placeholder="Province">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventCityy" class="form-label">Event City<span>*</span></label>
                        <input type="text" class="form-control" id="eventCityy" placeholder="City">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventCurrencyy" class="form-label">Currency<span>*</span></label>
                        <select class="form-select form-control" id="eventCurrencyy" name="event_currency" required>
                            <option value="INR">INR</option>
                            <option value="CAD">CAD</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="eventVenuee" class="form-label">Event Venue<span>*</span></label>
                        <select class="form-select form-control" id="eventVenuee" name="eventVenue" required>
                        <?php
                    $sqlVenue = "SELECT NAME FROM ca_venue ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
                            $selected = ($venueName == $event_venue) ? "selected" : "";
                            echo "<option value=\"$venueName\" $selected>$venueName</option>";
                        }
                    }
                    ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="eventCategoryy" class="form-label">Event Category<span>*</span></label>
                        <select class="form-select form-control" id="eventCategoryy" aria-label="">
                            <?php if ($_SESSION['usertype'] === 'Host'): ?>
                            <?php
                    $sqlVenue = "SELECT NAME FROM ca_event_category ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
                            $selected = ($venueName == $event_venue) ? "selected" : "";
                            echo "<option value=\"$venueName\" $selected>$venueName</option>";
                        }
                    }
                    ?>
                        <?php elseif ($_SESSION['usertype'] === 'Trainer'): ?>
                            <option value="Badminton Training">Badminton Training</option>
                            <option value="Tennis Training">Tennis Training</option>
                            <option value="Cricket Training">Cricket Training</option>
                            <option value="Football Training">Football Training</option>
                            <option value="Badminton Game and Training">Badminton Game + Training</option>
                        <?php else: ?>
                            <option disabled selected>Please select a valid user type</option>
                        <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="genderCategoryy" class="form-label">Gender<span>*</span></label>
                        <select class="form-select form-control" id="genderCategoryy" aria-label="">
                            <option selected value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Mix">Mix</option>
                            <option value="Kid">Kids</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="genderSkillLevell" class="form-label">Skill Level<span>*</span></label>
                        <select class="form-select form-control" id="genderSkillLevell" aria-label="">
                            <option selected value="Beginner">Beginner</option>
                            <option value="Amateur">Amateur</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Intermediate +">Intermediate+</option>
                            <option value="Advance">Advance</option>
                            <option value="Mix">Mix</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventTypee" class="form-label">Event Type<span>*</span></label>
                        <select class="form-select form-control" id="eventTypee" aria-label="">
                            <option selected value="Public">Public</option>
                            <option value="Invite">Invite Only</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="statusevent" class="form-label">Status<span>*</span></label>
                        <select class="form-select form-control" id="statusevent" aria-label="">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventDatee" class="form-label">Event Date<span>*</span></label>
                        <input type="date" class="form-control" id="eventDatee" placeholder="Event Date">
                    </div>
                    
                    <div class="col-6 col-md-4">
                        <label for="freezeDatee" class="form-label">Freeze Date<span>*</span></label>
                        <input type="date" class="form-control" id="freezeDatee" placeholder="Freeze Date">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="freezeTimee" class="form-label">Freeze Time<span>*</span></label>
                        <select class="form-select form-control" id="freezeTimee" name="freeze_time" required></select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventDiscountt" class="form-label">Event Court<span>*</span></label>
                        <input type="text" class="form-control" id="eventDiscountt" placeholder="Event Court">
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="eventDescriptionn" class="form-label">Event Description<span>*</span></label>
                        <textarea class="form-control" id="eventDescriptionn" rows="3" placeholder="Bring own bat, shoe, guards....."></textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="eventMessagee" class="form-label">Event Message<span>*</span></label>
                        <textarea class="form-control" id="eventMessagee" rows="3" placeholder="For Participants to put note....."></textarea>
                    </div>

                    <!-- Time & Facility Block -->
                    <div class="col-12 mt-3">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#eacc7f;">
                             <div class="col-6 col-sm-4 col-md-2">
                                <label for="eventTimee" class="form-label text-dark">From Time<span>*</span></label>
                                <select class="form-select form-control" id="eventTimee" name="event_time" required></select>
                            </div>
                             <div class="col-6 col-sm-4 col-md-2">
                                <label for="toTimee" class="form-label text-dark">To Time<span>*</span></label>
                                <select class="form-select form-control" id="toTimee" name="to_Time" required ></select>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="hours" class="form-label text-dark">Hours<span></span></label>
                                <input type="text" class="form-control" id="hours" name="hours" readonly>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="facilitycostperhour" class="form-label text-dark">Cost Per Hour</label>
                                <input type="text" class="form-control" id="facilitycostperhour" placeholder="Cost Per Hour">
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="courtconfirmed" class="form-label text-dark">Court Confirmed</label>
                                <input type="text" class="form-control" id="courtconfirmed" placeholder="Court Confirmed">
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="facilitycost" class="form-label text-dark">Facility Cost ($)</label>
                                <input type="text" class="form-control" id="facilitycost" placeholder="Facility Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Birdie & Accessories Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#0000001f;">
                            <div class="col-12 col-sm-4">
                                <label for="birdieUsed" class="form-label text-dark">Birdie Used<span>*</span></label>
                                <input type="number" class="form-control" id="birdieUsed" placeholder="Birdie Used">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="nobirdieUsed" class="form-label text-dark">Birdie Price<span>*</span></label>
                                <input type="number" class="form-control" id="nobirdieUsed" placeholder="Birdie Price">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="accessoriesCost" class="form-label text-dark">Accessories Cost</label>
                                <input type="text" class="form-control" id="accessoriesCost" placeholder="Accessories Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Snacks & Event Cost Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#d5eaae;">
                            <div class="col-12 col-sm-4">
                                <label for="clubClost" class="form-label text-dark">Club Cost<span>*</span></label>
                                <input type="number" class="form-control" id="clubClost" placeholder="Club Cost">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="snackscost" class="form-label text-dark">Snacks Cost</label>
                                <input type="text" class="form-control" id="snackscost" placeholder="Snacks Cost">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="eventtotalCostt" class="form-label text-dark">Total Event Cost ($)</label>
                                <input type="text" class="form-control" id="eventtotalCostt" placeholder="Total Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Player Info Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#78e1f7;">
                            <div class="col-6 col-sm-3">
                                <label for="playersJoined" class="form-label text-dark">Players Joined</label>
                                <input type="text" class="form-control" id="playersJoined" placeholder="Players Joined" readonly>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="playersConfirmed" class="form-label text-dark">Players Confirmed</label>
                                <input type="text" class="form-control" id="playersConfirmed" placeholder="Confirmed" readonly>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="eventCostt" class="form-label text-dark">Player Cost ($)<span>*</span></label>
                                <input type="text" class="form-control" id="eventCostt" placeholder="Event Cost">
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="eventtotalplayerCostt" class="form-label text-dark">Total Player Cost ($)</label>
                                <input type="text" class="form-control" id="eventtotalplayerCostt" placeholder="Total Player Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Automation & Profit Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2 align-items-end" style="background-color:#bfd6f7;">
                            <!-- Automation -->
                            <div class="col-12 col-sm-4">
                                <div class="form-check d-flex align-items-center gap-2 mb-2">
                                    <input class="form-check-input" type="checkbox" id="autoConfirm">
                                    <label class="form-check-label mb-0 text-dark" for="autoConfirm">
                                        Automation On
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="form-check d-flex align-items-center gap-2 mb-2">
                                    <input class="form-check-input" type="checkbox" id="updatePlayerPrice">
                                    <label class="form-check-label mb-0 text-dark" for="updatePlayerPrice">
                                        Update Player Price
                                    </label>
                                </div>
                            </div>

                            <!-- Profit Loss -->
                            <div class="col-12 col-sm-4">
                                <label for="profitloss" class="form-label mb-1 text-dark">Profit / Loss</label>
                                <input type="text" class="form-control" id="profitloss" placeholder="Profit Loss">
                            </div>
                        </div>
                    </div>
            
                    <input type="hidden" id="EVENT_IDD">
                    
                    <div class="col-12 d-flex flex-wrap justify-content-center gap-2 mt-3 mb-2">
                        <button type="button" class="btn btn-primary px-4" id="save_btn">Submit</button>
                        <button type="button" class="btn btn-primary px-4" id="copy_btn">Copy Game</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<script type="text/javascript">
   document.addEventListener('click', function(event) {
    // Check if the click is inside a .hostgameview_modal
    const modal = event.target.closest('.hostgameview_modal');

    if (modal) {
        // Check if the clicked element is the .customModal_close button or inside it
        const closeBtn = event.target.closest('.customModal_close');
        
        if (closeBtn) {
            event.preventDefault();
            console.log("Modal close button clicked");

            // Close only this modal
            modal.classList.remove('open');

            // Optional redirect
            // window.location.href = '/host-dashboard.php';
            
            // Get filter values
            const year = document.getElementById("year")?.value || "";
            const month = document.getElementById("month")?.value || "";
    
            // AJAX (vanilla fetch)
            fetch('api/filter_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}&type=filter`
            })
            .then(response => response.text())
            .then(data => {
                const wrapper = document.querySelector(".hostWrapper");
                if (wrapper) wrapper.innerHTML = data;
            })
            .catch(error => console.error("Fetch error:", error));
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // Close any already open popovers when opening a new one
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');

    popoverTriggerList.forEach(triggerEl => {
      const popover = new bootstrap.Popover(triggerEl);

      triggerEl.addEventListener('click', function () {
        // Hide other popovers
        popoverTriggerList.forEach(otherEl => {
          if (otherEl !== triggerEl) {
            bootstrap.Popover.getInstance(otherEl)?.hide();
          }
        });
      });
    });
});
document.addEventListener('click', function(e) {
    const copyBtn = e.target.closest('.copy_event');
    if (!copyBtn) return;

    const eventId = copyBtn.getAttribute('data-id');
    const dataInput = document.getElementById('event_data_' + eventId);

    if (dataInput) {
        const data = dataInput.value;

        if (navigator.clipboard && window.isSecureContext) {
            // Modern clipboard API
            navigator.clipboard.writeText(data).then(() => {
                console.log('Event details copied!');
            }).catch(err => console.error('Clipboard error:', err));
        } else {
            // Fallback for older browsers
            const temp = document.createElement('textarea');
            temp.value = data;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            console.log('Event details copied (fallback)!');
        }
    } else {
        console.error('Event data not found for ID:', eventId);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all popovers manually
    var popoverWrappers = document.querySelectorAll('.game-info-wrapper');

    popoverWrappers.forEach(function(wrapper) {
        var popover = new bootstrap.Popover(wrapper, {
            trigger: 'manual',
            html: true,
            placement: 'top'
        });

        wrapper.addEventListener('click', function(e) {
            // Close all other popovers
            popoverWrappers.forEach(function(otherWrapper) {
                if (otherWrapper !== wrapper) {
                    bootstrap.Popover.getInstance(otherWrapper)?.hide();
                }
            });

            // Toggle current popover
            popover.toggle();
        });
    });

    // Close popovers when clicking outside
    document.addEventListener('click', function(e) {
        popoverWrappers.forEach(function(wrapper) {
            if (!wrapper.contains(e.target)) {
                bootstrap.Popover.getInstance(wrapper)?.hide();
            }
        });
    });
});

document.getElementById('autoConfirm').addEventListener('change', function () {
    const automationValue = this.checked ? 'Y' : 'N';
    const eventID = document.getElementById("EVENT_IDD").value;
    console.log("eventID",eventID)

    hitAutomationAPI(automationValue,eventID);
});

function hitAutomationAPI(value,eventID) {
    fetch('https://casainfotech.com/staging/api/update-automation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            AUTOMATION: value,
            eventID:eventID
        })
    })
    .then(response => response.json())
    .then(data => {
        location.reload()
        console.log('Automation updated:', data);
    })
    .catch(error => {
        location.reload()
    });
}

</script>

