<?php
include 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['USER_ID'];
    $gameId = $_POST['ID'];
    $hostId = $_POST['HOST_ID'];
    $cost = $_POST['EVENT_COST'];
    $currency = $_POST['EVENT_CURRENCY'];
    $createdAt = date('Y-m-d H:i:s'); // Get the current timestamp

    // Insert the invitation data into the `ca_gamejoin` table
    $query = "UPDATE ca_gamejoin SET STATUS = 'Y' WHERE USER_ID = '$userId' AND GAME_ID='$gameId' AND TYPE='Invite'";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $sql = "SELECT * FROM ca_events WHERE STATUS='Active'"; // Adjust the query based on your conditions
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $outputHTML = "";
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
        
        $jsonStringy = json_encode($event);
        
        // You can also fetch the image and other dynamic content here.
        $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
        // echo "select * from ca_gamejoin where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$event['ID']."'";
        $selectJoin = mysqli_query($conn,"select * from ca_gamejoin where USER_ID='".$userId."' and GAME_ID='".$event_id."'");
        $countRows = mysqli_num_rows($selectJoin);
        $fetchRows = mysqli_fetch_assoc($selectJoin);
        
         if($event_type == "Public")
        {
            $action = "<span class='actionJC badge " . ($countRows > 0  ? 'bg-danger' : 'bg-success') . "' style='cursor:pointer;' data-id='$event_id'>
                        " . ($countRows > 0 ? 'Cancel' : 'Join') . "                </span>";
        }
        else
        {
            $action = "<span class='actionAC badge " . ($countRows > 0 && $fetchRows['STATUS'] == 'N' ? 'bg-success'  : 'bg-danger') . "' style='cursor:pointer;' data-id='$event_id'>
                        " . ($countRows > 0 && $fetchRows['STATUS'] == 'N' ? 'Accept' : 'Cancel') . "                </span>";
        }

        $outputHTML .= "
        <div class='discoverGames_card player_cards'>
            <div class='d-flex align-items-center justify-content-between'>
                <p class='desc'>$event_category Tournament</p>
                <div class='d-flex align-items-center justify-content-end gap-2'>
                    <span class='badge " . ($event_type == 'Public' ? 'bg-primary' : 'bg-success') . "'>
                    $event_type
                </span>
                    <input type='hidden' id='data_$event_id' value='$jsonStringy'/>
                    <input type='hidden' id='user_$event_id' value='".$userId."'/>
                </div>
            </div>
            <div class='d-flex align-items-center' style='gap: 15px; margin-bottom: 10px;'>
                <div class='d-flex align-items-center'>
                    <div class='profile_pic'><img src='$profile_pic' alt='profile' /></div>
                    <div class='profile_pic2'><img src='$profile_pic' alt='profile' /></div>
                </div>
                <div class='Slots_book' style='visibility:hidden'>Only 2 Slots</div>
            </div>
            <h4 class='date_time'>$event_date</h4>
            <p class='location'>
                <i class='fa-solid fa-location-dot'></i>
                $event_venue
            </p>
            <div class='d-flex align-items-center justify-content-between'>
                <div class='play_status'>
                    <img src='assets/images/Icons/SP5.png' class='img-fluid' alt='Game Icon' />
                    <span>$gender_skill_level</span>
                </div>
                <div class='d-flex align-items-center justify-content-between' style='gap: 10px;'>
                
                   $action
                
                    <span class='amount'>
                        <i class='fa-solid fa-dollar-sign mr-1'></i>
                        $event_currency $event_cost
                    </span>
                
                </div>
            </div>
        </div>";
            }
        } else {
            $outputHTML =  "<p>No events found.</p>";
        }
        echo json_encode(['status' => 'success', 'message' => 'Invitation sent successfully.','outputHTML'=>$outputHTML]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send invitation.','outputHTML'=>null]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
