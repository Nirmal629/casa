<?php
session_start();
include('dbConnection.php');
// error_reporting(1);

if($_POST['type']=='filter')
{
    $currentYearr = date('Y');
    $currentMonthh = date('m');
    $currentDatee = date('Y-m-d');
        $gender = $_SESSION['gender']; // Replace with logged-in user's gender
        $skill_level = $_SESSION['vlevel']!=''?$_SESSION['vlevel']:$_SESSION['level']; // Replace with logged-in user's skill level
        
        // Define skill hierarchy
        $skill_levels = [
            'Beginner' => ["Beginner"],
            'Amateur' => ["Beginner", "Amateur"],
            'Intermediate' => ["Beginner", "Amateur", "Intermediate"],
            'Intermediate +' => ["Beginner", "Amateur", "Intermediate","Intermediate +"],
            'Advance' => ["Beginner", "Amateur", "Intermediate","Intermediate +", "Advance"]
        ];
                        // echo "ok1".$skill_level;exit;

        $allowed_levels = implode("','", $skill_levels[$skill_level]);
                        // echo "ok2";exit;
        $allowed_levels = implode("','", $skill_levels[$skill_level]);

        $conditions = [" AND STATUS='Active'"]; // Always include active status

        // Check and add conditions dynamically
        if (!empty($_POST['host'])) {
            $conditions[] = "HOST_ID='" . mysqli_real_escape_string($conn, $_POST['host']) . "'";
        }
        
        if (!empty($_POST['year'])) {
            $conditions[] = "YEAR(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['year']) . "'";
        }
        
        if (!empty($_POST['month'])) {
            $conditions[] = "MONTH(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['month']) . "'";
        }

        
        $sql = "SELECT * FROM ca_events";
        if (!empty($conditions)) {
            // $sql .= " WHERE (GENDER_CATEGORY='$gender' OR GENDER_CATEGORY='Mix') AND GENDER_SKILL_LEVEL IN ('$allowed_levels')" . implode(" AND ", $conditions);
            // $sql .= " WHERE (GENDER_CATEGORY='$gender' OR GENDER_CATEGORY='Mix') AND GENDER_SKILL_LEVEL = '$skill_level'" . implode(" AND ", $conditions);
            $sql .= " WHERE (GENDER_CATEGORY='$gender' OR GENDER_CATEGORY='Mix') AND (GENDER_SKILL_LEVEL = '$skill_level' OR GENDER_SKILL_LEVEL = 'Mix') AND EVENT_CATEGORY ='".$_POST['event_category']."'" . implode(" AND ", $conditions);
            
            // If selected month is the current month → exclude past games
            if (!empty($_POST['year']) && !empty($_POST['month'])) {
                $selectedYear = (int) $_POST['year'];
                $selectedMonth = (int) $_POST['month'];
        
                if ($selectedYear == $currentYearr && $selectedMonth == $currentMonthh) {
                    $sql .= " AND EVENT_DATE >= '$currentDatee'";
                }
            }


        }
        $sql .= " ORDER BY EVENT_DATE ASC, EVENT_TIME DESC";
        // echo $sql;exit;    
        // $sql = "SELECT * FROM ca_events WHERE HOST_ID='".$_POST['host']."' AND STATUS='Active' AND YEAR(EVENT_DATE) = '".$_POST['year']."' AND MONTH(EVENT_DATE) = '".$_POST['month']."'  AND GENDER_CATEGORY='$gender' AND GENDER_SKILL_LEVEL IN ('$allowed_levels') ORDER BY EVENT_DATE"; // Adjust the query based on your conditions
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
                        $event_court = $event['EVENT_DISCOUNT'];


        $event_cancelTime = date('h:i A', strtotime($event['CANCEL_TIME']));
        $event_toTime = $event['TO_TIME'];
        
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
        $selectJoin = mysqli_query($conn,"select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'");
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
        
        $totalJoin = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."'");
        $countTotalJoin = mysqli_num_rows($totalJoin);
        
        $totalConfirmed = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."' and CONFIRMED='Y'");
        $countTotalConfirmed = mysqli_num_rows($totalConfirmed);
        
        if($event_type == "Public")
        {
            $action = "<span class=' " . ($countRows > 0 ? 'actionC' : 'actionJC') . " badge " . ($countRows > 0  ? 'bg-danger' : 'bg-success') . "' style='width:100%; padding:10px 20px; cursor:pointer; font-size:80%;' data-id='$event_id'>
                        " . ($countRows > 0 ? 'Cancel' : 'Join') . "                </span>";
        }
        else
        {
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
        <div class='w-full d-flex align-items-center justify-content-between gap-1 mt-2 pt-1' style='border-top:1px solid #bfbbbb;'>
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
}
else
{
    $gender = $_SESSION['gender']; // Replace with logged-in user's gender
        $skill_level = $_SESSION['vlevel']!=''?$_SESSION['vlevel']:$_SESSION['level']; // Replace with logged-in user's skill level
        
        // Define skill hierarchy
        $skill_levels = [
            'Beginner' => ["Beginner"],
            'Amateur' => ["Beginner", "Amateur"],
            'Intermediate +' => ["Beginner", "Amateur", "Intermediate","Intermediate +"],
            'Advance' => ["Beginner", "Amateur", "Intermediate","Intermediate +", "Advance"]
        ];
        $allowed_levels = implode("','", $skill_levels[$skill_level]);
        $currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)


        // Assuming you have a connection to the database ($conn)
        // $sql = "SELECT * FROM ca_events WHERE HOST_ID=21 AND STATUS='Active' AND (GENDER_CATEGORY='$gender' OR GENDER_CATEGORY='Mix') AND GENDER_SKILL_LEVEL IN ('$allowed_levels') ORDER BY EVENT_DATE DESC, EVENT_TIME DESC"; 
        $sql = "SELECT * FROM ca_events WHERE STATUS='Active' AND (GENDER_CATEGORY='$gender' OR GENDER_CATEGORY='Mix') AND (GENDER_SKILL_LEVEL ='" . trim($skill_level) . "' OR GENDER_SKILL_LEVEL = 'Mix') AND EVENT_CATEGORY ='Badminton Game' AND YEAR(EVENT_DATE) = '$currentYear' AND MONTH(EVENT_DATE) = '$currentMonth' AND EVENT_DATE >= CURDATE() ORDER BY EVENT_DATE ASC, EVENT_TIME DESC";
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
                        $event_court = $event['EVENT_DISCOUNT'];


        $event_cancelTime = date('h:i A', strtotime($event['CANCEL_TIME']));
        $event_toTime = $event['TO_TIME'];
        
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
        
        // You can also fetch the image and other dynamic content here.
        $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
        // echo "select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'";
        $selectJoin = mysqli_query($conn,"select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'");
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
        
        $totalJoin = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."'");
        $countTotalJoin = mysqli_num_rows($totalJoin);
        
        $totalConfirmed = mysqli_query($conn,"select * from ca_gamejoin where GAME_ID='".$event['ID']."' and CONFIRMED='Y'");
        $countTotalConfirmed = mysqli_num_rows($totalConfirmed);
        
        if($event_type == "Public")
        {
            $action = "<span class=' " . ($countRows > 0 ? 'actionC' : 'actionJC') . " badge " . ($countRows > 0  ? 'bg-danger' : 'bg-success') . "' style='width:100%; padding:10px 20px; cursor:pointer; font-size:80%;' data-id='$event_id'>
                        " . ($countRows > 0 ? 'Cancel' : 'Join') . "                </span>";
        }
        else
        {
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
        <div class='w-full d-flex align-items-center justify-content-between gap-1 mt-2 pt-1' style='border-top:1px solid #bfbbbb;'>
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
}

?>