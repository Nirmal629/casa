<?php
// DB connection ($conn) is already provided by inner-header.php
        
        $eventsByDay = [
            'MONDAY'    => [],
            'TUESDAY'   => [],
            'WEDNESDAY' => [],
            'THURSDAY'  => [],
            'FRIDAY'    => [],
            'SATURDAY'  => [],
            'SUNDAY'    => []
        ];
        
        $dayColors = [
            'MONDAY'    => 'bg-primary text-white',
            'TUESDAY'   => 'bg-success text-white',
            'WEDNESDAY' => 'bg-warning text-dark',
            'THURSDAY'  => 'bg-info text-dark',
            'FRIDAY'    => 'bg-secondary text-white',
            'SATURDAY'  => 'bg-dark text-white',
            'SUNDAY'    => 'bg-danger text-white',
        ];
        
        $sql = "SELECT * FROM ca_events_default WHERE 1";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while ($event = mysqli_fetch_assoc($result)) {
                $day = strtoupper($event['DAY']);
                if (isset($eventsByDay[$day])) {
                    $eventsByDay[$day][] = $event;
                }
            }
    }
?>

<section class="">
    <div class="custom_card">
        <h6 class="card_heading">Weekly Game Schedule</h6>

        <?php foreach ($eventsByDay as $day => $dayEvents): ?>
            <?php if (!empty($dayEvents)): ?>
                <div class="mb-4">
                    <h4 class="text-uppercase fw-bold mb-3"><?php echo $day; ?></h4>
                    <div class="discoverGames_wraper subscriptionWrapper">
                        <?php foreach ($dayEvents as $event): 
                            $event_id = $event['ID'];
                            $event_day = $event['DAY'];
                            $event_category = $event['EVENT_CATEGORY'];
                            $gender_category = $event['GENDER_CATEGORY'];
                            $event_message = html_entity_decode($event['EVENT_MESSAGE'], ENT_QUOTES, 'UTF-8');
                            $event_description = html_entity_decode($event['EVENT_DESCRIPTION'], ENT_QUOTES, 'UTF-8');
                            $jsonStringy = htmlspecialchars(json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                            $wrapped_message = implode(" ", array_slice(explode(" ", $event_message), 0, 7));
                            if (str_word_count($event_message) > 7) $wrapped_message .= " ...";

                            $wrapped_message_desc = implode(" ", array_slice(explode(" ", $event_description), 0, 7));
                            if (str_word_count($event_description) > 7) $wrapped_message_desc .= " ...";

                            $dayColorClass = isset($dayColors[$event_day]) ? $dayColors[$event_day] : '';

                            $eventTimeFrom = date('h:i A', strtotime($event['EVENT_TIME']));
                            $eventTimeTo = date('h:i A', strtotime($event['TO_TIME']));
                            $event_venue = $event['EVENT_VENUE'];
                            $event_cost = $event['EVENT_COST'];
                            $event_currency = $event['EVENT_CURRENCY'];
                            $event_type = $event['EVENT_TYPE'];
                            $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
                        ?>

                        <div class='discoverGames_card_sub card shadow-sm mb-3' style='padding: 0; border-radius: 12px; overflow: hidden; border: 1px solid #e0e0e0;'>
                            <!-- Top Bar -->
                            <div class='d-flex align-items-start justify-content-between p-3 pb-2 gap-2'>
                                <p class='desc fw-bold m-0 text-dark' style='font-size: 1.05rem; line-height: 1.3;'><?php echo "$event_category - $gender_category"; ?></p>
                                <div class='d-flex align-items-center justify-content-end gap-1 flex-shrink-0'>
                                    <span class='badge bg-info text-white rounded-circle d-flex align-items-center justify-content-center' style='width: 28px; height: 28px; cursor:help;' title='<?php echo htmlspecialchars($event_description, ENT_QUOTES); ?>'><i class='fa-solid fa-info'></i></span>
                                    <span class='badge bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center view_btn' data-id='<?php echo $event_id; ?>' style='width: 28px; height: 28px; cursor:pointer'><i class='fa fa-user-plus'></i></span>
                                    <a href='javascript:void(0)' class='badge bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center edit_btn' data-id='<?php echo $event_id; ?>' style='width: 28px; height: 28px; text-decoration:none;'>
                                        <i class='fa-regular fa-pen-to-square'></i>
                                    </a>
                                    <input type='hidden' id='data_<?php echo $event_id; ?>' value='<?php echo $jsonStringy; ?>'/>
                                </div>
                            </div>

                            <!-- Day and Time Banner -->
                            <div class='<?php echo $dayColorClass; ?> px-3 py-2'>
                                <h5 class='m-0 fw-bold text-uppercase' style='font-size: 0.85rem; letter-spacing: 0.5px;'><?php echo "$event_day - $eventTimeFrom to $eventTimeTo"; ?></h5>
                            </div>

                            <div class='p-3 pt-2 d-flex flex-column h-100'>
                                <!-- Location -->
                                <p class='location text-muted mb-2' style='font-size: 0.85rem;'>
                                    <i class='fa-solid fa-location-dot me-1'></i>
                                    <?php echo $event_venue; ?>
                                </p>

                                <!-- Message -->
                                <p class='mb-3 text-secondary' style='font-size: 0.9rem; line-height: 1.4;'><?php echo $event_message; ?></p>

                                <!-- Bottom row: Tags and Create button -->
                                <div class='d-flex align-items-end justify-content-between gap-2 mt-auto'>
                                    <div class='d-flex flex-wrap gap-1'>
                                        <span class='badge bg-secondary rounded-pill fw-normal px-2 py-1' style='font-size: 0.75rem;'><?php echo $gender_skill_level; ?></span>
                                        <span class='badge <?php echo $event_type == 'Public' ? 'bg-primary' : 'bg-success'; ?> rounded-pill fw-normal px-2 py-1' style='font-size: 0.75rem;'><?php echo $event_type; ?></span>
                                        <span class='badge bg-light text-dark border rounded-pill fw-normal px-2 py-1' style='font-size: 0.75rem;'><?php echo "$event_currency $event_cost"; ?></span>
                                    </div>
                                    <button id="create_btn" class="btn btn-success btn-sm fw-bold px-3 rounded-pill flex-shrink-0" style='font-size: 0.85rem;' data-id='<?php echo $event_id; ?>'>Create</button>
                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>



<!-----View-modal------->
<section class="customModal_wrap hostgameview_modal_sub">
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

            <div class="" id="playerList_sub">
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
        <h6 class="customModal_head">Create Game</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form>
                <div class="row">
                    <div class="col-md-6 col-12 mb-3">
                        <label for="host-name" class="form-label">Host Name<span>*</span></label>
                        <input type="text" class="form-control" id="host-nameee" placeholder="Enter Full Name" value="">
                        <input type="hidden" id="evnt_id" value=""/>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCountry" class="form-label">Event Country<span>*</span></label>
                        <input type="text" class="form-control" id="eventCountryyy" placeholder="Event Country">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventProvince" class="form-label">Event Province<span>*</span></label>
                        <input type="text" class="form-control" id="eventProvinceee" placeholder="Event Province">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCity" class="form-label">Event City<span>*</span></label>
                        <input type="text" class="form-control" id="eventCityyy" placeholder="Event City">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCurrency" class="form-label">Event Currency<span>*</span></label>
                        <!--<input type="text" class="form-control" id="eventCurrencyy" placeholder="Event Currency">-->
                        <select class="form-select form-control" id="eventCurrencyyy" name="event_currency" required>
                            <!--<option value="USD">USD</option>-->
                            <option value="INR">INR</option>
                            <!--<option value="EUR">EUR</option>-->
                            <!--<option value="GBP">GBP</option>-->
                            <option value="CAD">CAD</option>
                            <!-- Add more currencies as needed -->
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventVenue" class="form-label">Event Venue<span>*</span></label>
                        <!--<input type="text" class="form-control" id="eventVenuee" placeholder="Event Venue">-->
                        <select class="form-select form-control" id="eventVenueee" name="eventVenue" required>
                        <?php
                    $sqlVenue = "SELECT NAME FROM ca_venue ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = trim(htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8'));
            
                            // If you want to pre-select based on edit mode:
                            $selected = ($venueName == $event_venue) ? "selected" : "";
            
                            echo "<option value=\"$venueName\" $selected>$venueName</option>";
                        }
                    }
                    ?>
                   
                </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCategory" class="form-label">Event Category<span>*</span></label>
                        <select class="form-select form-control" id="eventCategoryyy" aria-label="">
                            <!--<option selected value="Badminton">Badminton Game</option>-->
                            <!--<option disabled value="Tennis">Tennis Game</option>-->
                            <!--<option disabled value="Cricket">Cricket Game</option>-->
                            <!--<option disabled value="Football">Football Game</option>-->
                            <?php if ($_SESSION['usertype'] === 'Host'): ?>
                            <?php
                    $sqlCat = "SELECT NAME FROM ca_event_category ORDER BY NAME ASC";
                    $resCat = mysqli_query($conn, $sqlCat);
            
                    if ($resCat && mysqli_num_rows($resCat) > 0) {
                        while ($rowCat = mysqli_fetch_assoc($resCat)) {
                            $ecatName = htmlspecialchars($rowCat['NAME'], ENT_QUOTES, 'UTF-8');
            
                            // If you want to pre-select based on edit mode:
                            $selected = ($ecatName == $event_category) ? "selected" : "";
            
                            echo "<option value=\"$ecatName\" $selected>$ecatName</option>";
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

                    <div class="col-md-6 col-12 mb-3">
                        <label for="genderCategory" class="form-label">Gender Category<span>*</span></label>
                        <select class="form-select form-control" id="genderCategoryyy" aria-label="">
                            <option selected value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Mix">Mix</option>
                            <option value="Kid">Kids</option>
                            <!--<option value="Training">Training</option>-->
                            <!--<option value="Training">Kids + Training</option>-->
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="genderSkillLevel" class="form-label">Gender Skill Level<span>*</span></label>
                        <select class="form-select form-control" id="genderSkillLevelll" aria-label="">
                            <option selected value="Beginner">Beginner</option>
                            <option value="Amateur">Amateur</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Intermediate +">Intermediate+</option>
                            <option value="Advance">Advance</option>
                            <option value="Mix">Mix</option>

                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventType" class="form-label">Event Type<span>*</span></label>
                        <select class="form-select form-control" id="eventTypeee" aria-label="">
                            <option selected value="Public">Public</option>
                            <option value="Invite">Invite Only</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventDate" class="form-label">Event Date<span>*</span></label>
                        <input type="date" class="form-control" id="eventDateee" placeholder="Event Date">
                    </div>

                    <!--<div class="col-md-6 col-12 mb-3">-->
                    <!--    <label for="eventTime" class="form-label">Event Time<span>*</span></label>-->
                    <!--    <input type="time" class="form-control" id="eventTimee" placeholder="Event Time">-->
                    <!--</div>-->
                     <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventTime" class="form-label">From Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="eventTime" name="event_time" required step="00:15">-->
        <select class="form-control" id="eventTimeee" name="event_time" required></select>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventTime" class="form-label">To Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="toTime" name="to_time" required step="1800">-->
                <select class="form-control" id="toTimeee" name="to_Time" required></select>

            </div>
            <div class="col-md-6 col-12 mb-3">
                        <label for="eventDate" class="form-label">Freeze Date<span>*</span></label>
                        <input type="date" class="form-control" id="freezeDateee" placeholder="Freeze Date">
                    </div>

            <div class="col-md-6 col-12 mb-3">
                <label for="eventTime" class="form-label">Freeze Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="freezeTime" name="freeze_time" required step="1800">-->
                                <select class="form-control" id="freezeTimeee" name="freeze_time" required></select>

            </div>

                    

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventDescription" class="form-label">Event Description<span>*</span></label>
                        <textarea class="form-control" id="eventDescriptionnn" rows="5" placeholder="Bring own bat, shoe, guards....."></textarea>
                    </div>
                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventMessage" class="form-label">Event Message<span>*</span></label>
                        <textarea class="form-control" id="eventMessageee" rows="5" placeholder="For Participants to put note....."></textarea>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventCost" class="form-label">Event Cost ($)<span>*</span></label>
                        <input type="text" class="form-control" id="eventCosttt" placeholder="Event Cost">
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                        <label for="eventDiscount" class="form-label">Event Court<span>*</span></label>
                        <input type="text" class="form-control" id="eventDiscounttt" placeholder="Event Court">
                    </div>
                    <div class="col-md-6 col-12 mb-3">
                        <label for="genderSkillLevel" class="form-label">Status<span>*</span></label>
                        <select class="form-select form-control" id="statuseventt" aria-label="">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventCost" class="form-label">Event Facility Cost ($)</label>
                        <input type="text" class="form-control" id="facilitycostt" placeholder="Event Facility Cost">
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                        <label for="eventDiscount" class="form-label">Event Accessories Cost</label>
                        <input type="text" class="form-control" id="accessoriesCostt" placeholder="Event Accessories Cost">
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                        <label for="eventDiscount" class="form-label">Event Snacks Cost</label>
                        <input type="text" class="form-control" id="snackscostt" placeholder="Event Snacks Cost">
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventCost" class="form-label">Total Event Cost ($)</label>
                        <input type="text" class="form-control" id="eventtotalCosttt" placeholder="Event Total Cost">
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventCost" class="form-label">Total Player Cost ($)</label>
                        <input type="text" class="form-control" id="eventtotalplayerCosttt" placeholder="Event Total Player Cost">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventCost" class="form-label">Profit Loss</label>
                        <input type="text" class="form-control" id="profitlosss" placeholder="Profit Loss">
                    </div>
                    <input type="hidden" id="event_sub_id">
                    <div class="col-auto m-auto">
                        <button type="button" class="btn btn-primary" id="save_btn_subs">Create Game</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function populateTimeDropdown(selectId) {
        let select = document.getElementById(selectId);
        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += 30) {
                let hour = h < 10 ? "0" + h : h;
                let minute = m < 10 ? "0" + m : m;
                let timeValue = `${hour}:${minute}`;
                let option = new Option(timeValue, timeValue);
                select.appendChild(option);
            }
        }
    }

    populateTimeDropdown("freezeTimeee");
    populateTimeDropdown("eventTimeee");
    populateTimeDropdown("toTimeee");

$(document).on('click', '.discoverGames_card_sub .edit_btn', function () {
    let id = $(this).attr('data-id');
        // console.log(id);
        let data = $('#data_'+id).val()
        let parseData = JSON.parse(data)
        console.log(parseData)
        console.log("Value to set:", parseData.EVENT_VENUE);

        $('.hostgameupdate_modal').addClass('open');
        $('#event_sub_id').val(id);
        $('#host-nameee').val(parseData.HOST_NAME);
        $('#eventCountryyy').val(parseData.EVENT_COUNTRY);
        $('#eventProvinceee').val(parseData.EVENT_PROVINCE);
        $('#eventCityyy').val(parseData.EVENT_CITY);
        $('#eventCurrencyyy').val(parseData.EVENT_CURRENCY);
$('#eventVenueee').val($.trim(parseData.EVENT_VENUE));
        $('#eventCategoryyy').val(parseData.EVENT_CATEGORY);
        $('#genderCategoryyy').val(parseData.GENDER_CATEGORY);
        $('#genderSkillLevelll').val(parseData.GENDER_SKILL_LEVEL);
        $('#eventTypeee').val(parseData.EVENT_TYPE);
setTimeout(() => {
    let eventTimeFormatted = parseData.EVENT_TIME.slice(0, 5); 
    console.log(eventTimeFormatted)
    $('#eventTimeee').val(eventTimeFormatted).change();
    $('#toTimeee').val(parseData.TO_TIME.slice(0, 5)).change();
    $('#freezeTimeee').val(parseData.CANCEL_TIME.slice(0, 5)).change();
}, 100); 
        $('#eventCosttt').val(parseData.EVENT_COST);
        $('#eventDiscounttt').val(parseData.EVENT_DISCOUNT);
        $('#eventDescriptionnn').val(parseData.EVENT_DESCRIPTION);
        $('#eventMessageee').val(parseData.EVENT_MESSAGE);
        $('#statuseventt').val(parseData.STATUS);
        $('#evnt_idd').val(parseData.ID);
        $('#facilitycostt').val(parseData.FACILITY_COST);
        $('#accessoriesCostt').val(parseData.ACCESSORIES_COST);
        $('#snackscostt').val(parseData.SNACKS_COST);
        $('#eventtotalCosttt').val(parseData.TOTAL_EVENT_COST);
        $('#eventtotalplayerCosttt').val(parseData.TOTAL_PLAYER_COST);
        $('#profitlosss').val(parseData.PROFIT_LOSS);
        
        let dayName = parseData.DAY ? parseData.DAY.toUpperCase().trim() : "";
    
    function getNextDayOfWeek(dayIndex) {
        let date = new Date();
        let currentDay = date.getDay(); // Sunday=0 ... Saturday=6
        let diff = (dayIndex + 7 - currentDay) % 7;
        if (diff === 0) diff = 7; // always next, not today
        date.setDate(date.getDate() + diff);
        return date.toISOString().split("T")[0]; // yyyy-mm-dd
    }

    if (dayName === "MONDAY") {
        $('#eventDateee').val(getNextDayOfWeek(1));  // 1 = Monday
        $('#freezeDateee').val(getNextDayOfWeek(1));
    } else if (dayName === "TUESDAY") {
        $('#eventDateee').val(getNextDayOfWeek(2));  // 2 = Tuesday
        $('#freezeDateee').val(getNextDayOfWeek(2));
    
} else if (dayName === "WEDNESDAY") {
        $('#eventDateee').val(getNextDayOfWeek(3));  // 2 = Tuesday
        $('#freezeDateee').val(getNextDayOfWeek(3));
    
    } else if (dayName === "THURSDAY") {
        $('#eventDateee').val(getNextDayOfWeek(4));  // 2 = Tuesday
        $('#freezeDateee').val(getNextDayOfWeek(4));
    
    } else if (dayName === "FRIDAY") {
        $('#eventDateee').val(getNextDayOfWeek(5));  // 2 = Tuesday
        $('#freezeDateee').val(getNextDayOfWeek(5));
    
    } else if (dayName === "SATURDAY") {
        $('#eventDateee').val(getNextDayOfWeek(6));  // 2 = Tuesday
        $('#freezeDateee').val(getNextDayOfWeek(5));
    
    } else if (dayName === "SUNDAY") {
        $('#eventDateee').val(getNextDayOfWeek(7));  // 2 = Tuesday
        $('#freezeDateee').val(getNextDayOfWeek(7));
    }
    });

</script>