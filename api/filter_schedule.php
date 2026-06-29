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
        $is_today = (date('Y-m-d') == date('Y-m-d', strtotime($event['EVENT_DATE'])));
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
                                    🌐https://casainfotech.com/staging
                                    💪Stay fit and active!
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
            <div class='row px-2 mb-2 g-2 align-items-stretch'>
                <!-- Left Column: Date, Time, Location -->
                <div class='col-7 d-flex align-items-center'>
                    <div class='d-flex align-items-start gap-2 w-100'>
                        <!-- Calendar Box -->
                        <div class='p-1 rounded text-center flex-shrink-0' style='min-width: 40px; background-color: #fff1f0; border: 1px solid #ffccc7;'>
                            <div class='fw-bold' style='font-size: 0.95rem; line-height: 1; color: #cf1322;'>" . date('d', strtotime($event['EVENT_DATE'])) . "</div>
                            <div class='fw-semibold' style='font-size: 0.65rem; text-transform: uppercase; color: #cf1322;'>" . date('M', strtotime($event['EVENT_DATE'])) . "</div>
                        </div>
                        <!-- Details -->
                        <div class='d-flex flex-column justify-content-center w-100'>
                            <h6 class='mb-1 fw-bold text-dark' style='font-size: 0.75rem; line-height: 1.2;'>" . date('g:i A', strtotime($event['EVENT_TIME'])) . " - " . date('g:i A', strtotime($event['TO_TIME'])) . "</h6>
                            <p class='mb-0 text-truncate' style='font-size: 0.75rem; color: #555;'>
                                <i class='fa-solid fa-location-dot text-secondary me-1' style='font-size:0.7rem'></i>$event_venue
                            </p>
                            <p class='mb-0 text-muted' style='font-size: 0.65rem; line-height: 1.2; margin-top: 2px;'>
                                <i class='fa-solid fa-snowflake me-1' style='font-size:0.6rem'></i>Freeze: " . date('M d, g:i A', strtotime($event['CANCEL_DATE'] . ' ' . $event['CANCEL_TIME'])) . "
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Stats (Joined, Confirmed, Players) -->
                <div class='col-5 d-flex flex-column justify-content-between gap-1'>
                    <div class='d-flex gap-1 w-100'>
                         <div class='border rounded p-1 text-center flex-grow-1' style='background-color: #f8f9fa;'>
                             <div style='font-size: 0.6rem; letter-spacing: 0.3px;' class='text-muted text-uppercase fw-semibold'>Joined</div>
                             <div class='fw-bold text-dark' style='font-size: 0.9rem; line-height: 1;'>$countTotalJoin</div>
                         </div>
                         <div class='border rounded p-1 text-center flex-grow-1' style='background-color: #f8f9fa;'>
                             <div style='font-size: 0.6rem; letter-spacing: 0.3px;' class='text-muted text-uppercase fw-semibold'>Confirm</div>
                             <div class='fw-bold text-dark' style='font-size: 0.9rem; line-height: 1;'>$countTotalConfirmed</div>
                         </div>
                    </div>
                    <button class='btn btn-dark btn-sm w-100 joined_btn d-flex align-items-center justify-content-center gap-2 p-1 border-0 shadow-sm' style='font-size: 0.75rem; border-radius: 4px;' data-id='$event_id'>
                         Players <i class='fa-regular fa-eye'></i>
                    </button>
                </div>
            </div>
             <div class='px-2 mb-2'>
                <p class='gamesms_text m-0' style='font-size: 0.75rem; color: #6c757d; font-style: italic;'><i class='fa-solid fa-circle-info me-1' style='font-size: 0.7rem;'></i>$wrapped_message</p>
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
        $is_today = (date('Y-m-d') == date('Y-m-d', strtotime($event['EVENT_DATE'])));
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
                                    🌐https://casainfotech.com/staging
                                    💪Stay fit and active!
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
                        <h4 class='date_time mb-0 " . ($is_today ? "blink" : "") . "' style='color: #dc3545; font-size: 0.85rem; font-weight: 600;'>$event_date<span class='fw-normal text-muted mx-1'>|</span>" . date('h:i A', strtotime($event['TO_TIME'])) . "</h4>
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
}
        
?>