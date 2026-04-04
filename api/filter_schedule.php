<?php
session_start();
include('dbConnection.php');
if($_POST['type']=='filter')
{
        if (!empty($_POST['year'])) {
        $conditions[] = "YEAR(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['year']) . "'";
        }
        
        if (!empty($_POST['month'])) {
            $conditions[] = "MONTH(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['month']) . "'";
        }
        
        $sql = "SELECT * FROM ca_events WHERE HOST_ID='".$_SESSION['user_id']."' and STATUS='Active'";
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY EVENT_DATE DESC, EVENT_TIME DESC";
        // $sql = "SELECT * FROM ca_events WHERE HOST_ID='".$_SESSION['user_id']."' and STATUS='Active' AND YEAR(EVENT_DATE) = '".$_POST['year']."' AND MONTH(EVENT_DATE) = '".$_POST['month']."' ORDER BY EVENT_DATE"; // Adjust the query based on your conditions
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
        $event_description = $event['EVENT_DESCRIPTION'];
        $event_message = $event['EVENT_MESSAGE'];
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
        
        $words_desc = explode(" ", $event_description); // Split message into words
        $wrapped_message_desc = implode(" ", array_slice($words_desc, 0, 7)); // Get first 5 words
        
        if (count($words_desc) > 7) {
            $wrapped_message_desc .= " ..."; // Append "..." if more words exist
        }
        
                $event_description_html = nl2br(htmlspecialchars($event_description, ENT_QUOTES, 'UTF-8'));
                
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
                                    
                                    // Final formatted message
                                    $eventData = <<<EOT
                                    Host:                $host_name
                                    Game:                $event_category
                                    Category:            $gender_category
                                    Level:               $gender_skill_level
                                    Day:                 $dayName
                                    Date:                $dateFormatted
                                    Time:                $timeFormatted
                                    Venue:               $event_venue
                                    ___________
                                    $playerList
                                    ___________
                                    
                                    Players are requested to join the game using the portal.
                                    Reach out to admin for any further queries/suggestions.
                                    
                                    Warm regards,
                                    🏸Casa Badminton Club
                                    📞+1 437 981 0512
                                    🌐https://casainfotech.com
                                    💪Stay fit and active!
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
                        <h4 class='date_time mb-0 " . ($is_today ? "blink" : "") . "' style='color: red;'><span>$event_date</span><span> to </span><span>" . date('h:i A', strtotime($event['TO_TIME'])) . "</span></h4>
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
}
else
{
    $currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
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
        $event_description = $event['EVENT_DESCRIPTION'];
        $event_message = $event['EVENT_MESSAGE'];
        $event_type = $event['EVENT_TYPE'];
        $event_canceldate = $event['CANCEL_DATE']!='' ? date('D, d M Y', strtotime($event['CANCEL_DATE'])) : '';
        $event_cancelTime = date('h:i A', strtotime($event['CANCEL_TIME']));
        
        $jsonStringy = json_encode($event, JSON_HEX_APOS | JSON_HEX_QUOT);
        
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

        $event_description_html = nl2br(htmlspecialchars($event_description, ENT_QUOTES, 'UTF-8'));
        
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
                                    
                                    // Final formatted message
                                    $eventData = <<<EOT
                                    Host:                $host_name
                                    Game:                $event_category
                                    Category:            $gender_category
                                    Level:               $gender_skill_level
                                    Day:                 $dayName
                                    Date:                $dateFormatted
                                    Time:                $timeFormatted
                                    Venue:               $event_venue
                                    ___________
                                    $playerList
                                    ___________
                                    
                                    Players are requested to join the game using the portal.
                                    Reach out to admin for any further queries/suggestions.
                                    
                                    Warm regards,
                                    🏸Casa Badminton Club
                                    📞+1 437 981 0512
                                    🌐https://casainfotech.com
                                    💪Stay fit and active!
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
                        <h4 class='date_time mb-0 " . ($is_today ? "blink" : "") . "' style='color: red;'><span>$event_date</span><span> to </span><span>" . date('h:i A', strtotime($event['TO_TIME'])) . "</span></h4>
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
}
        
?>