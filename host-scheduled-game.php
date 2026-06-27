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
                    <div class="row">
                        <div class="col-auto">
                            <select class="form-select" id="year" aria-label="Default select example">
                                <option value="">Select the Year</option>
                                <?php
                                for ($year = 2024; $year <= 2030; $year++) {
                                    $selected = ($year == $currentYear) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" id="month" aria-label="Default select example">
                                <option value="">Select the Month</option>
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
                            <button type="button" class="btn btn-primary" id="filter">Submit</button>
                            <button type="button" class="btn btn-danger" id="reset">Reset</button>
                        </div>
                    </div>
                </form>
            </div>

        <div class="discoverGames_wraper hostWrapper">


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
        // Assuming you have a connection to the database ($conn)
        $sql = "SELECT * FROM ca_events WHERE HOST_ID='".$_SESSION['user_id']."' and STATUS='Active' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth' ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; // Adjust the query based on your conditions
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
                <div class='accessories_wrap'>
                    <span class='joined_btn badge btn-success' title='Join Now' data-id='$event_id'><i class='fa fa-user-plus'></i></span>
                    <a href='javascript:void(0)' class='badge btn-info copy_event' title='Copy' data-id='$event_id'>
                        <i class='fa-regular fa-copy'></i>
                      </a>
                    <a href='javascript:void(0)' class='edit_btn badge btn-secondary' title='Edit' data-id='$event_id'>
                        <i class='fa-regular fa-pen-to-square'></i>
                    </a>
                    <a href='javascript:void(0)' data-id='$event_id' class='delete_btn badge btn-danger' title='Delete'>
                        <i class='fa-regular fa-trash-can'></i>
                    </a>
                    <input type='hidden' id='data_$event_id' value='$jsonStringy'/>
                    <input type='hidden' id='event_data_$event_id' value='" . htmlspecialchars($eventData, ENT_QUOTES) . "'>

                </div>
            </div>
           <div class='d-flex align-items-center justify-content-between gap-1 mb-2'>
                <div class=''>
                    <div class='d-flex align-items-start gap-1 mb-2 p-1' style='border: 1px solid red; border-radius: 6px;'>
                        <i class='fa-solid fa-clock' style='font-size: 80%; color: red; margin-top: 3px;'></i>
                        <h4 class='date_time mb-0' style='color: red;'><span>$event_date</span><span> to </span><span>" . date('h:i A', strtotime($event['TO_TIME'])) . "</span></h4>
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
                    <!----<div class='d-flex align-items-center justify-content-between gap-1 mb-1 game-info-wrapper' 
                         data-bs-toggle='popover' 
                         data-bs-html='true'
                         data-bs-content='<?php echo $event_description_html; ?>'
                         style='cursor: pointer;'>
                        <span>Game Info:</span>
                        <span class='badge bg-info m-0'>
                            <i class='fa-solid fa-info'></i>
                        </span>
                    </div>--->

                    <div class='d-flex align-items-center justify-content-between gap-1'>
                        <span>View Players:</span>
                        <span class='badge btn-dark view_btn' style='cursor: pointer;' title='View' data-id='$event_id'><i class='fa-regular fa-eye'></i></span>
                    </div>
                </div>
            </div>
             <div class='d-flex align-items-center' style='gap: 15px; margin-bottom: 10px;'>
                <div class='d-flex align-items-center'>
                    <p class='gamesms_text'>$wrapped_message</p>
                </div>
                <!----<div class='Slots_book' style='visibility:hidden'>Only 2 Slots</div>--->
            </div>
            <div class='d-flex align-items-center justify-content-start gap-1'>
                <div class='play_status'>
                    <span>$gender_skill_level</span>
                </div>
                <div class='play_status d-flex flex-wrap align-items-start gap-1'>
                    <span>
                    Court: ". ($event_court == 0 ?'NA':$event_court)."
                    </span>
                    <span class='amount'>
                        $event_currency $event_cost
                    </span>
                    <span class='text-white " . ($event_type == 'Public' ? 'bg-primary' : 'bg-success') . "'>
                       $event_type
                    </span>
                </div>
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
        <h6 class="customModal_head">View Game</h6>
        <button type="submit" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="row align-items-end g-2">
            <div class="col-md-3 col-12">
                <input type="text" name="search" id="search" placeholder="Enter text to search" class="form-text" />
                <input type="hidden" id="playdt" data-game-id="" data-host-id="" />
            </div>
        
            <div class="col-md-3 col-12">
                <label for="sgenderCategoryy" class="form-label">Gender Category<span>*</span></label>
                <select class="form-select" id="sgenderCategoryy">
                    <option selected value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Mix">Mix</option>
                    <option value="Kid">Kids</option>
                </select>
            </div>
        
            <div class="col-md-3 col-12">
                <label for="sgenderSkillLevell" class="form-label">Gender Skill Level<span>*</span></label>
                <select class="form-select" id="sgenderSkillLevell">
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
        <h6 class="customModal_head">View All Player's Joined</h6>
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
                <div class="row">
                    <div class="col-md-4 col-4 sm-2">
                        <label for="host-name" class="form-label">Host Name<span>*</span></label>
                        <input type="text" class="form-control" id="host-namee" placeholder="Enter Full Name" value="">
                        <input type="hidden" id="evnt_id" value=""/>
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="eventCountry" class="form-label">Event Country<span>*</span></label>
                        <input type="text" class="form-control" id="eventCountryy" placeholder="Event Country">
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="eventProvince" class="form-label">Event Province<span>*</span></label>
                        <input type="text" class="form-control" id="eventProvincee" placeholder="Event Province">
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="eventCity" class="form-label">Event City<span>*</span></label>
                        <input type="text" class="form-control" id="eventCityy" placeholder="Event City">
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="eventCurrency" class="form-label">Event Currency<span>*</span></label>
                        <!--<input type="text" class="form-control" id="eventCurrencyy" placeholder="Event Currency">-->
                        <select class="form-select form-control" id="eventCurrencyy" name="event_currency" required>
                            <!--<option value="USD">USD</option>-->
                            <option value="INR">INR</option>
                            <!--<option value="EUR">EUR</option>-->
                            <!--<option value="GBP">GBP</option>-->
                            <option value="CAD">CAD</option>
                            <!-- Add more currencies as needed -->
                        </select>
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="eventVenue" class="form-label">Event Venue<span>*</span></label>
                        <!--<input type="text" class="form-control" id="eventVenuee" placeholder="Event Venue">-->
                        <select class="form-select form-control" id="eventVenuee" name="eventVenue" required>
                        <!--<option value="Epic Badminton">Epic Badminton</option>-->
                        <!--<option value="Hymus Sports">Hymus Sports</option>-->
                        <!--<option value="KeralaNook">Kerala Nook</option>-->
                        <!--<option value="WillieStout">Willie Stout</option>-->
                        <!--<option value="CornerBank">Corner Bank</option>-->
                        <?php
                    $sqlVenue = "SELECT NAME FROM ca_venue ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
            
                            // If you want to pre-select based on edit mode:
                            $selected = ($venueName == $event_venue) ? "selected" : "";
            
                            echo "<option value=\"$venueName\" $selected>$venueName</option>";
                        }
                    }
                    ?>

                   
                </select>
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="eventCategory" class="form-label">Event Category<span>*</span></label>
                        <select class="form-select form-control" id="eventCategoryy" aria-label="">
                            <!--<option selected value="Badminton">Badminton Game</option>-->
                            <!--<option disabled value="Tennis">Tennis Game</option>-->
                            <!--<option disabled value="Cricket">Cricket Game</option>-->
                            <!--<option disabled value="Football">Football Game</option>-->
                            <?php if ($_SESSION['usertype'] === 'Host'): ?>
                            <!--<option value="Badminton Game">Badminton Game</option>-->
                            <!--<option value="Tennis Game">Tennis Game</option>-->
                            <!--<option value="Cricket Game">Cricket Game</option>-->
                            <!--<option value="Football Game">Football Game</option>-->
                            <!--<option value="Snacks at Kerala Knook">Snacks at Kerala Knook</option>-->
                            <!--<option value="Outing">Outing</option>-->
                            <!--<option value="Service">Service</option>-->
                            <?php
                    $sqlVenue = "SELECT NAME FROM ca_event_category ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
            
                            // If you want to pre-select based on edit mode:
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

                    <div class="col-md-4 col-4 sm-2">
                        <label for="genderCategory" class="form-label">Gender Category<span>*</span></label>
                        <select class="form-select form-control" id="genderCategoryy" aria-label="">
                            <option selected value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Mix">Mix</option>
                            <option value="Kid">Kids</option>
                            <!--<option value="Training">Training</option>-->
                            <!--<option value="Training">Kids + Training</option>-->
                        </select>
                    </div>

                    <div class="col-md-4 col-4 sm-2">
                        <label for="genderSkillLevel" class="form-label">Gender Skill Level<span>*</span></label>
                        <select class="form-select form-control" id="genderSkillLevell" aria-label="">
                            <option selected value="Beginner">Beginner</option>
                            <option value="Amateur">Amateur</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Intermediate +">Intermediate+</option>
                            <option value="Advance">Advance</option>
                            <option value="Mix">Mix</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-6 mb-3">
                        <label for="eventType" class="form-label">Event Type<span>*</span></label>
                        <select class="form-select form-control" id="eventTypee" aria-label="">
                            <option selected value="Public">Public</option>
                            <option value="Invite">Invite Only</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-6 mb-3">
                        <label for="eventDate" class="form-label">Event Date<span>*</span></label>
                        <input type="date" class="form-control" id="eventDatee" placeholder="Event Date">
                    </div>

                    <!--<div class="col-md-6 col-12 mb-3">-->
                    <!--    <label for="eventTime" class="form-label">Event Time<span>*</span></label>-->
                    <!--    <input type="time" class="form-control" id="eventTimee" placeholder="Event Time">-->
                    <!--</div>-->
                    
            <div class="col-md-6 col-6 mb-3">
                        <label for="eventDate" class="form-label">Freeze Date<span>*</span></label>
                        <input type="date" class="form-control" id="freezeDatee" placeholder="Freeze Date">
                    </div>

            <div class="col-md-6 col-6 mb-3">
                <label for="eventTime" class="form-label">Freeze Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="freezeTime" name="freeze_time" required step="1800">-->
                                <select class="form-control" id="freezeTimee" name="freeze_time" required></select>

            </div>

                    

                    <div class="col-md-6 col-6 mb-3">
                        <label for="eventDescription" class="form-label">Event Description<span>*</span></label>
                        <textarea class="form-control" id="eventDescriptionn" rows="5" placeholder="Bring own bat, shoe, guards....."></textarea>
                    </div>
                    <div class="col-md-6 col-6 mb-3">
                        <label for="eventMessage" class="form-label">Event Message<span>*</span></label>
                        <textarea class="form-control" id="eventMessagee" rows="5" placeholder="For Participants to put note....."></textarea>
                    </div>
                    <div class="col-md-6 col-6 mb-3">
                        <label for="eventDiscount" class="form-label">Event Court<span>*</span></label>
                        <input type="text" class="form-control" id="eventDiscountt" placeholder="Event Court">
                    </div>
                    <div class="col-md-6 col-6 mb-3">
                        <label for="genderSkillLevel" class="form-label">Status<span>*</span></label>
                        <select class="form-select form-control" id="statusevent" aria-label="">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div style="width:100%;background-color:#eacc7f;display:flex;gap:10px;padding-bottom:10px;margin-bottom:2px;">
                         <div class="col-md-2 col-2 sm-2">
                            <label for="eventTime" class="form-label">From Time<span>*</span></label>
                            <!--<input type="time" class="form-control" id="eventTime" name="event_time" required step="00:15">-->
                            <select class="form-control" id="eventTimee" name="event_time" required></select>
                        </div>
                         <div class="col-md-2 col-2 sm-2">
                            <label for="eventTime" class="form-label">To Time<span>*</span></label>
                            <!--<input type="time" class="form-control" id="toTime" name="to_time" required step="1800">-->
                            <select class="form-control" id="toTimee" name="to_Time" required ></select>
    
                        </div>
                        <div class="col-md-2 col-2 sm-2">
                            <label for="eventTime" class="form-label">Hours<span></span></label>
                            <input type="text" class="form-control" id="hours" name="hours" readonly>
    
                        </div>
                        <div class="col-md-2 col-2 sm-2">
                            <label for="eventCost" class="form-label">Cost Per Hour</label>
                            <input type="text" class="form-control" id="facilitycostperhour" placeholder="Facility Cost Per Hour">
                        </div>
                        <div class="col-md-2 col-2 sm-2">
                            <label for="eventCost" class="form-label">Court Confirmed</label>
                            <input type="text" class="form-control" id="courtconfirmed" placeholder="Court Confirmed">
                        </div>
                        <div class="col-md-2 col-2 sm-2">
                            <label for="eventCost" class="form-label">Facility Cost ($)</label>
                            <input type="text" class="form-control" id="facilitycost" placeholder="Event Facility Cost">
                        </div>
                    </div>
                    <div style="width:100%;background-color:#0000001f;display:flex;gap:10px;padding-bottom:10px;">
                        <div class="col-md-3 col-3 mb-2">
                            <label for="birdieUsed" class="form-label">Birdie Used<span>*</span></label>
                            <input type="number" class="form-control" id="birdieUsed" placeholder="Birdie Used">
                        </div>
                        <div class="col-md-3 col-3 mb-2">
                            <label for="birdieUsed" class="form-label">Birdie Price<span>*</span></label>
                            <input type="number" class="form-control" id="nobirdieUsed" placeholder="Birdie Price">
                        </div>
                        
                        <div class="col-md-3 col-3 mb-2">
                            <label for="eventDiscount" class="form-label">Accessories Cost</label>
                            <input type="text" class="form-control" id="accessoriesCost" placeholder="Event Accessories Cost">
                        </div>
                    </div>
                    <div style="width:100%;background-color:#d5eaae;display:flex;gap:10px;padding-bottom:10px;margin-bottom:2px;">
                        <div class="col-md-3 col-3 mb-2">
                                <label for="clubClost" class="form-label">Club Cost<span>*</span></label>
                                <input type="number" class="form-control" id="clubClost" placeholder="Club Cost">
                            </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label for="eventDiscount" class="form-label">Snacks Cost</label>
                            <input type="text" class="form-control" id="snackscost" placeholder="Event Snacks Cost">
                        </div>
                        <div class="col-md-3 col-4 sm-2">
                            <label for="eventCost" class="form-label">Total Event Cost ($)</label>
                            <input type="text" class="form-control" id="eventtotalCostt" placeholder="Event Total Cost">
                        </div>
                    </div>
                    <div style="width:100%;background-color:#78e1f7;display:flex;gap:10px;padding-bottom:10px;margin-bottom:2px;">
                    <div class="col-md-2 col-2 mb-2">
                            <label for="eventCost" class="form-label">Players Joined</label>
                            <input type="text" class="form-control" id="playersJoined" placeholder="Players Joined" readonly>
                        </div>
                         <div class="col-md-2 col-2 mb-2">
                            <label for="eventCost" class="form-label">Players Confirmed</label>
                            <input type="text" class="form-control" id="playersConfirmed" placeholder="Players Confirmed" readonly>
                        </div>
                        <div class="col-md-3 col-3 mb-3">
                            <label for="eventCost" class="form-label">Player Cost ($)<span>*</span></label>
                            <input type="text" class="form-control" id="eventCostt" placeholder="Event Cost">
                        </div>
                         <div class="col-md-3 col-3 sm-3">
                            <label for="eventCost" class="form-label">Total Player Cost ($)</label>
                            <input type="text" class="form-control" id="eventtotalplayerCostt" placeholder="Event Total Player Cost">
                        </div>
                    </div>

            <div class="row align-items-end"
                 style="width:100%; background-color:#bfd6f7; padding:10px 8px; margin-bottom:2px;margin-left:2px">
            
                <!-- Automation -->
                <div class="col-md-4 col-12">
                    <div class="form-check d-flex align-items-center gap-2 mt-4">
                        <input class="form-check-input" type="checkbox" id="autoConfirm">
                        <label class="form-check-label mb-0" for="autoConfirm">
                            Automation On
                        </label>
                    </div>
            </div>
                <div class="col-md-4 col-12">
                        <div class="form-check d-flex align-items-center gap-2 mt-4">
                        <input class="form-check-input" type="checkbox" id="updatePlayerPrice">
                        <label class="form-check-label mb-0" for="updatePlayerPrice">
                            Update Player Price
                        </label>
                    </div>
                </div>

                <!-- Profit Loss -->
                <div class="col-md-4 col-12">
                    <label for="profitloss" class="form-label mb-1">Profit / Loss</label>
                    <input type="text" class="form-control" id="profitloss" placeholder="Profit Loss">
                </div>
                
                </div>

                       
    
                       
            
                    <input type="hidden" id="EVENT_IDD">
                    

                    <div class="col-auto m-auto">
                        <button type="button" class="btn btn-primary" id="save_btn">Submit</button>
                    </div>
                    <div class="col-auto m-auto">
                        <button type="button" class="btn btn-primary" id="copy_btn">Copy Game</button>
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

