<?php
session_start();
include('dbConnection.php');
if($_POST['type']=='filter')
{
    if (!empty($_POST['host'])) {
            $conditions[] = "HOST_ID='" . mysqli_real_escape_string($conn, $_POST['host']) . "'";
        }
        
        if (!empty($_POST['year'])) {
            $conditions[] = "YEAR(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['year']) . "'";
        }
        
        if (!empty($_POST['month'])) {
            $conditions[] = "MONTH(EVENT_DATE)='" . mysqli_real_escape_string($conn, $_POST['month']) . "'";
        }
        if (!empty($_POST['host'])) {
        $conditions[] = "HOST_ID='" . mysqli_real_escape_string($conn, $_POST['host']) . "'";
    }
        // $sql = "SELECT * FROM ca_events WHERE HOST_ID='".$_POST['HOST_ID']."' AND STATUS='Completed' AND YEAR(EVENT_DATE) = '".$_POST['year']."' AND MONTH(EVENT_DATE) = '".$_POST['month']."'"; // Adjust the query based on your conditions
        $sql = "SELECT * FROM ca_events WHERE STATUS='Completed'";
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY EVENT_DATE DESC, EVENT_TIME DESC";

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
            echo "<div class='empty-schedule-card text-center w-100 py-4 px-3 my-2' style='max-width: 440px; margin: 15px auto !important; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1.5px dashed #cbd5e1; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);'>
                <div class='empty-icon-wrap mx-auto mb-2 d-flex align-items-center justify-content-center' style='width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); color: #0284c7; box-shadow: 0 2px 8px rgba(2,132,199,0.15);'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='22' height='22' fill='currentColor' viewBox='0 0 16 16'>
                        <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/>
                        <path d='M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z'/>
                    </svg>
                </div>
                <h6 class='fw-bold mb-1' style='color: #1e293b; font-size: 0.95rem; letter-spacing: -0.2px;'>No Completed Events</h6>
                <p class='text-muted mb-2' style='font-size: 0.82rem; line-height: 1.45; max-width: 320px; margin: 0 auto;'>No completed games found for this selection.</p>
            </div>";
        }
}
else
{
    $currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)
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
            echo "<div class='empty-schedule-card text-center w-100 py-4 px-3 my-2' style='max-width: 440px; margin: 15px auto !important; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1.5px dashed #cbd5e1; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);'>
                <div class='empty-icon-wrap mx-auto mb-2 d-flex align-items-center justify-content-center' style='width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); color: #0284c7; box-shadow: 0 2px 8px rgba(2,132,199,0.15);'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='22' height='22' fill='currentColor' viewBox='0 0 16 16'>
                        <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/>
                        <path d='M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z'/>
                    </svg>
                </div>
                <h6 class='fw-bold mb-1' style='color: #1e293b; font-size: 0.95rem; letter-spacing: -0.2px;'>No Completed Events</h6>
                <p class='text-muted mb-2' style='font-size: 0.82rem; line-height: 1.45; max-width: 320px; margin: 0 auto;'>No completed games found for this selection.</p>
            </div>";
        }
}
?>