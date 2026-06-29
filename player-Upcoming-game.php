<?php
// DB connection ($conn) is already provided by inner-header.php

$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)


$select_event_cat = mysqli_query($conn, "select * from ca_event_category where 1");
$host_id = isset($host_id) ? $host_id : (isset($_GET['host_id']) ? intval($_GET['host_id']) : ($_SESSION['mapped_host_id'] ?? 0));
?>
<!----player-Upcoming-game----->
<div class="mb-4">
    <form>
        <div class="row g-1">
            <input type="hidden" id="host" value="<?= htmlspecialchars($host_id) ?>" />
            <div class="col-auto">
                <select class="form-select form-select-sm" id="event_category" aria-label="Default select example" style="width: auto; min-width: 160px; font-size: 0.95rem;">
                    <option value="" selected>All</option>
                    <?php
                    while ($fetchEventCat = mysqli_fetch_assoc($select_event_cat)) {
                    ?>
                        <option value="<?= $fetchEventCat['NAME'] ?>"><?= $fetchEventCat['NAME'] ?></option>
                    <?php
                        
                    }
                    ?>
                </select>
            </div>
            <!--<div class="col-auto">-->
            <!--    <select class="form-select" id="year" aria-label="Default select example">-->
            <!--        <option value=''>Select the Year</option>-->
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
            <!--    <select class="form-select" id="month" aria-label="Default select example">-->
            <!--        <option value=''>Select the Month</option>-->
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
                <select class="form-select form-select-sm" id="year" aria-label="Default select example" style="width: 85px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
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
                <select class="form-select form-select-sm" id="month" aria-label="Default select example" style="width: 80px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                    <option value="">Month</option>
                    <?php
                    $months = [
                        1 => 'Jan',
                        2 => 'Feb',
                        3 => 'Mar',
                        4 => 'Apr',
                        5 => 'May',
                        6 => 'Jun',
                        7 => 'Jul',
                        8 => 'Aug',
                        9 => 'Sep',
                        10 => 'Oct',
                        11 => 'Nov',
                        12 => 'Dec'
                    ];
                    foreach ($months as $num => $name) {
                        $selected = ($num == $currentMonth) ? 'selected' : '';
                        echo "<option value=\"$num\" $selected>$name</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-auto ms-auto d-flex gap-1">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center" id="play_filter" title="Submit" style="width: 32px; height: 31px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.552 2.55 5.92-5.903z"/>
                    </svg>
                </button>
            
                <button type="button" class="btn btn-danger btn-sm d-flex align-items-center justify-content-center" id="play_reset" title="Reset" style="width: 32px; height: 31px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                        <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>
<div class="discoverGames_wraper plyerGame_wrapper">


    <?php

    // print_r($_SESSION);exit;
    $gender = $_SESSION['gender']; // Replace with logged-in user's gender
    $skill_level = $_SESSION['vlevel'] != '' ? $_SESSION['vlevel'] : $_SESSION['level']; // Replace with logged-in user's skill level
    // Define skill hierarchy
    $skill_levels = [
        'Beginner' => ["Beginner"],
        'Amateur' => ["Beginner", "Amateur"],
        'Intermediate' => ["Beginner", "Amateur", "Intermediate"],
        'Intermediate +' => ["Beginner", "Amateur", "Intermediate", "Intermediate +"],
        'Advance' => ["Beginner", "Amateur", "Intermediate", "Intermediate +", "Advance"]
    ];
    // echo "ok1".$skill_level;exit;

    $allowed_levels = implode("','", $skill_levels[$skill_level]);


    // Assuming you have a connection to the database ($conn)
    // $sql = "SELECT * FROM ca_events WHERE HOST_ID=21 AND STATUS='Active' AND (GENDER_CATEGORY = '$gender' OR GENDER_CATEGORY = 'Mix') AND GENDER_SKILL_LEVEL IN ('$allowed_levels') ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; 
    // $sql = "SELECT * FROM ca_events WHERE STATUS='Active' AND (GENDER_CATEGORY = '$gender' OR GENDER_CATEGORY = 'Mix') AND (GENDER_SKILL_LEVEL ='" . trim($skill_level) . "' OR GENDER_SKILL_LEVEL = 'Mix') AND EVENT_CATEGORY ='Badminton Game' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth' AND (EVENT_DATE >= CURDATE() OR (EVENT_DATE = CURDATE() AND TO_TIME >= CURTIME())) ORDER BY EVENT_DATE ASC, EVENT_TIME DESC";
    // --- compute current Toronto date/time in PHP ---
$torontoTz = new DateTimeZone('America/Toronto');
$nowToronto = new DateTime('now', $torontoTz);
$torontoDate = $nowToronto->format('Y-m-d'); // e.g. 2025-11-22
$torontoTime = $nowToronto->format('H:i:s'); // 24-hour, e.g. 14:30:00
$currentYear = $nowToronto->format('Y');
$currentMonth = $nowToronto->format('m');

// --- build SQL using PHP Toronto date/time for comparisons ---
$sql = "
    SELECT * FROM ca_events
    WHERE STATUS = 'Active'
      AND HOST_ID = '$host_id'
      AND (GENDER_CATEGORY = '$gender' OR GENDER_CATEGORY = 'Mix')
      AND (GENDER_SKILL_LEVEL = '" . trim($skill_level) . "' OR GENDER_SKILL_LEVEL = 'Mix')
      AND YEAR(EVENT_DATE) = '$currentYear'
      AND MONTH(EVENT_DATE) = '$currentMonth'
      AND (
            EVENT_DATE > '$torontoDate'
            OR (
                EVENT_DATE = '$torontoDate'
                AND COALESCE(TO_TIME, '00:00:00') >= '$torontoTime'
            )
      )
    ORDER BY EVENT_DATE ASC, EVENT_TIME DESC
";

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
            $event_canceldate = $event['CANCEL_DATE'] != '' ? date('D, d M Y', strtotime($event['CANCEL_DATE'])) : '';
            $event_cancelTime = date('h:i A', strtotime($event['CANCEL_TIME']));
            $event_toTime = $event['TO_TIME'];
            $event_court = $event['EVENT_DISCOUNT'];


            $jsonStringy = htmlspecialchars(
    json_encode($event, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
    ENT_QUOTES,
    'UTF-8'
);

            $words = explode(" ", $event_message); // Split message into words
            $wrapped_message = implode(" ", array_slice($words, 0, 7)); // Get first 5 words

            if (count($words) > 7) {
                $wrapped_message .= " ..."; // Append "..." if more words exist
            }

            $words_desc = explode(" ", $event_description); // Split message into words
            $wrapped_message_desc = implode(" ", array_slice($words_desc, 0, 7)); // Get first 5 words

            if (count($words_desc) > 7) {
                $wrapped_message_desc .= " ..."; // Append "..." if more words exist
            }

            // You can also fetch the image and other dynamic content here.
            $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
            // echo "select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'";
            $selectJoin = mysqli_query($conn, "select * from ca_gamejoin where USER_ID='" . $_SESSION['user_id'] . "' and GAME_ID='" . $event['ID'] . "'");
            $countRows = mysqli_num_rows($selectJoin);
            $fetchRows = mysqli_fetch_assoc($selectJoin);

            $bgClass = ''; // default
            if ($countRows > 0) {
                if ($fetchRows['CONFIRMED'] == 'Y') {
                    $bgClass = '#1987543b'; // Joined & Confirmed = green
                } else {
                    $bgClass = '#ffc10738'; // Joined but not Confirmed = orange
                }
            }

            $totalJoin = mysqli_query($conn, "select * from ca_gamejoin where GAME_ID='" . $event['ID'] . "'");
            $countTotalJoin = mysqli_num_rows($totalJoin);

            $totalConfirmed = mysqli_query($conn, "select * from ca_gamejoin where GAME_ID='" . $event['ID'] . "' and CONFIRMED='Y'");
            $countTotalConfirmed = mysqli_num_rows($totalConfirmed);

            if ($event_type == "Public") {
                $action = "<span class=' " . ($countRows > 0 ? 'actionC' : 'actionJC') . " badge " . ($countRows > 0  ? 'bg-danger' : 'bg-success') . "' style='width:100%; padding:10px 20px; cursor:pointer; font-size:80%;' data-id='$event_id'>
                        " . ($countRows > 0 ? 'Cancel' : 'Join') . "                </span>";
            } else {
                // $action = "<span class='" . ($countRows > 0 && $fetchRows['STATUS'] == 'N' ? 'actionAC' : 'actionC') . " badge " . ($countRows > 0 && $fetchRows['STATUS'] == 'N' ? 'bg-success'  : 'bg-danger') . "' style='cursor:pointer;' data-id='$event_id'>
                //             " . ($countRows > 0 && $fetchRows['STATUS'] == 'N' ? 'Accept' : 'Cancel') . "                </span>";
                if ($countRows > 0) {
                    if ($fetchRows['STATUS'] == 'N') {
                        $action = "<span class='actionAC badge bg-success' style='cursor:pointer;' data-id='$event_id'>Accept</span>";
                    } else {
                        $action = "<span class='actionC badge bg-danger' style='cursor:pointer;' data-id='$event_id'>Cancel</span>";
                    }
                } else {
                    $action = "<span class='badge bg-secondary' style='cursor:default;'>Not Invited</span>";
                }
            }
            
        $event_description_html = nl2br(htmlspecialchars($event_description, ENT_QUOTES, 'UTF-8'));


            echo "
        <div class='discoverGames_card player_cards' style='background-color:$bgClass'>
            <div class='d-flex flex-wrap align-items-center justify-content-between gap-2' style='margin-bottom: 6px; background-color: #0d6efda1; padding: 6px 10px; border-radius: 8px 8px 0px 0px;'>
                <p class='text-white desc fw-bold m-0' style='font-size: 85%;'>$event_category</p>
                <div class='accessories_wrap'>
                
                    <span style='padding: 2px 6px; font-size: 85%;' class='btn text-white " . ($event_type == 'Public' ? 'bg-info' : 'bg-success') . "'>
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
                    
                    <div class='d-flex align-items-start gap-2 mb-2 p-1' style='border: 1px dashed red; border-radius: 6px;'>
                        <i class='fa-solid fa-clock' style='font-size: 80%; color: red; line-height: normal;'></i>
                        <h4 class='upcoming date_time m-0' style='color: red;'><span>$event_date </span> <span> to </span> <span>" . date('h:i A', strtotime($event_toTime)) . "</span></h4>
                    </div>
                    <p class='location mb-1'>
                        <i class='fa-solid fa-location-dot'></i>
                        $event_venue
                    </p>
                    <p style='color:black; font-size: 80%; margin-bottom: 0px;'>Host: $host_name</p>
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
                     
                    <!---<div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                        <span>Game Info:</span>
                        <span class='badge btn-dark m-0' style='cursor: pointer;'
                              tabindex='0'
                              data-bs-toggle='popover'
                              data-bs-trigger='click'
                              data-bs-placement='top'
                              data-bs-html='true'
                              data-bs-content='$event_description_html'>
                            <i class='fa-solid fa-info'></i>
                        </span>
                    </div>--->
                    
                    <div class='view_btnn d-flex align-items-center justify-content-between gap-1' title='View' data-id='$event_id' style='cursor: pointer;'>
                        <span>All Players:</span>
                        <span class='badge bg-info'><i class='fa-regular fa-eye'></i></span>
                    </div>
                </div>
            </div>
            
            <p style='font-size:60%; margin-bottom: 5px;'>Freeze Date & Time: $event_canceldate $event_cancelTime</p>
            <p class='gamesms_text mb-1'>$wrapped_message</p>
            
            <!----<div class='Slots_book' style='visibility:hidden'>Only 2 Slots</div>--->

            <div class='d-flex align-items-center justify-content-start gap-1'>
                <div class='play_status flex-wrap'>
                    <img src='assets/images/Icons/SP5.png' class='img-fluid' alt='Game Icon' />
                    <span>Gender: $gender_category</span>
                    <span>Level: $gender_skill_level</span>
                    <span>Court: " . ($event_court == 0 ? 'NA' : $event_court) . " </span>
                    <span>$event_currency $event_cost</span>
                </div>
            </div>
            " . (
    !empty($event['JOIN_MESSAGE'])
    ? "<div class='d-flex align-items-center justify-content-start gap-1 mt-1'
        style='background:#d1e7dd; text-align:center; padding:2px; border-radius:6px;'>
        <span style='color:#0f5132; font-size:10px; font-weight:600'>"
        . htmlspecialchars($event['JOIN_MESSAGE']) .
        "</span>
      </div>"
    : ""
) . "
        <div class='w-full d-flex align-items-center justify-content-between gap-1 mt-1 pt-1' style='border-top:1px solid #bfbbbb;'>
           <div class='event-status-msg text-center fw-bold' style='font-size:80%;'>
    " . (
        $countRows > 0 
            ? ($fetchRows['CONFIRMED'] == 'Y' 
                ? "<div style='background:#d1e7dd; color:#0f5132; padding: 0px; border-radius:6px; text-align: left;'>
                        ✅ Your spot is confirmed, see you at the court!
                   </div>"
                : "<div style='background:#fff3cd; color:#664d03; padding: 0px; border-radius:6px; text-align: left;'>
                        ⏳ Your spot is not confirmed yet, awaiting host's confirmation.
                   </div>")
            : "<div style='background:#e2e3e5; color:#41464b; padding:8px 10px; border-radius:6px; text-align: left;'>
                        ❌ Not joined yet
               </div>"
    ) . "

</div>
<div class='d-flex align-items-center justify-content-center'>$action</div>
       </div></div>";
        }
    } else {
        echo "<p>No events found.</p>";
    }
    ?>


</div>

<!-----View-modal------->
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

<script>
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
</script>