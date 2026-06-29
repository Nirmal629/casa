<?php
session_start();
include 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['USER_ID'];
    $gameId = $_POST['ID'];
    $hostId = $_POST['HOST_ID'];
    $cost = $_POST['EVENT_COST'];
    $currency = $_POST['EVENT_CURRENCY'];
    $createdAt = date('Y-m-d H:i:s'); // Get the current timestamp

    // Insert the invitation data into the `ca_gamejoin` table
    $query = "INSERT INTO ca_gamejoin_default (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE,STATUS, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$cost', '$currency','Public','Y','$createdAt')";
    $result = mysqli_query($conn, $query);

    if ($result) {
        logPlayerActivity($conn, $userId, 'JOIN_GAME', 'Joined subscription game ID ' . $gameId);

        $eventsByDay = [
            'SUNDAY'    => [],
            'MONDAY'    => [],
            'TUESDAY'   => [],
            'WEDNESDAY' => [],
            'THURSDAY'  => [],
            'FRIDAY'    => [],
            'SATURDAY'  => []
        ];
        
        $dayColors = [
            'SUNDAY'    => 'bg-danger text-white p-1',
            'MONDAY'    => 'bg-primary text-white p-1',
            'TUESDAY'   => 'bg-success text-white p-1',
            'WEDNESDAY' => 'bg-warning text-dark p-1',
            'THURSDAY'  => 'bg-info text-dark p-1',
            'FRIDAY'    => 'bg-secondary text-white p-1',
            'SATURDAY'  => 'bg-dark text-white p-1'
        ];
        
        $sql = "SELECT * FROM ca_events_default WHERE (GENDER_SKILL_LEVEL='".$_SESSION['vlevel']."' OR GENDER_SKILL_LEVEL = 'Mix') AND (GENDER_CATEGORY='".$_SESSION['gender']."' OR GENDER_CATEGORY = 'Mix') AND EVENT_CATEGORY !='Snacks And Kerala Knook'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while ($event = mysqli_fetch_assoc($result)) {
                $day = strtoupper($event['DAY']);
                if (isset($eventsByDay[$day])) {
                    $eventsByDay[$day][] = $event;
                }
            }
    }
    

echo '<section class="outputHtml">';
echo '<div class="custom_card">';
echo '<h6 class="card_heading">Scheduled Games (Grouped by Weekday)</h6>';
        if (!empty($eventsByDay)) {
            foreach ($eventsByDay as $day => $day_events) {
                if (count($day_events) === 0) continue;

                echo "<h5 class='text-uppercase mt-4 fw-bold border-bottom pb-2'>$day</h5>";
                echo "<div class='discoverGames_wraper hostWrapper'>"; // Ensure flex wrap

                foreach ($day_events as $event) {
                    $event_id = $event['ID'];
                    $host_name = $event['HOST_NAME'];
                    $event_date = date('D, d M Y, h:i A', strtotime($event['EVENT_DATE'] . ' ' . $event['EVENT_TIME']));
                    $event_venue = $event['EVENT_VENUE'];
                    $event_day = $event['DAY'];
                    $event_cost = $event['EVENT_COST'];
                    $event_court = $event['EVENT_DISCOUNT'];
                    $event_currency = $event['EVENT_CURRENCY'];
                    $event_category = $event['EVENT_CATEGORY'];
                    $gender_category = $event['GENDER_CATEGORY'];
                    $gender_skill_level = $event['GENDER_SKILL_LEVEL'];
                    $event_description = html_entity_decode($event['EVENT_DESCRIPTION'], ENT_QUOTES, 'UTF-8');
                    $event_message = html_entity_decode($event['EVENT_MESSAGE'], ENT_QUOTES, 'UTF-8');
                    $event_type = $event['EVENT_TYPE'];

                    $jsonStringy = htmlspecialchars(json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $words_desc = explode(" ", $event_description);
                    $wrapped_message_desc = implode(" ", array_slice($words_desc, 0, 7));
                    if (count($words_desc) > 7) {
                        $wrapped_message_desc .= " ...";
                    }

                    $selectJoin = mysqli_query($conn, "SELECT * FROM ca_gamejoin_default WHERE USER_ID='{$_SESSION['user_id']}' AND GAME_ID='{$event['ID']}'");
                    $countRows = mysqli_num_rows($selectJoin);
                    
                    $dayColorClass = isset($dayColors[$event_day]) ? $dayColors[$event_day] : '';

                    echo "
                    <div class='discoverGames_card_sub'>
                        <div class='d-flex align-items-center justify-content-between'>
                            <p class='desc'>$event_category - $gender_category</p>
                            <div class='d-flex align-items-center justify-content-end gap-2'>
                                <span class='badge bg-info text-dark rounded-circle fw-bold' title='$event_description'>i</span>
<button 
    class='btn " . ($countRows > 0 ? "btn-danger" : "btn-primary") . " btn-sm join_btn' 
    data-id='$event_id' 
    data-joined='" . ($countRows > 0 ? "1" : "0") . "'>
    " . ($countRows > 0 ? "Cancel" : "Join") . "
</button>                               <input type='hidden' id='data_$event_id' value='$jsonStringy'/>
                                <input type='hidden' id='user_$event_id' value='{$_SESSION['user_id']}'/>
                            </div>
                        </div>
                        <h4 style='padding: 5px;' class='date_time $dayColorClass'>$event_day - " . date('h:i A', strtotime($event['EVENT_TIME'])) . " - " . date('h:i A', strtotime($event['TO_TIME'])) . "</h4>
                        <p class='location'><i class='fa-solid fa-location-dot'></i> $event_venue</p>
                        <div class='d-flex align-items-center gap-3 mb-2'>
                            <p style='font-size: 12px;'>$event_message</p>
                        </div>
                        <div class='d-flex align-items-center justify-content-between'>
                            <div class='play_status'>
                                <span>Level: $gender_skill_level</span>
                            </div>
                            <div class='d-flex align-items-center gap-2'>
                                <span class='badge " . ($event_type == 'Public' ? 'bg-primary' : 'bg-success') . "'>$event_type</span>
                                <span class='amount'>$event_currency $event_cost</span>
                            </div>
                        </div>
                    </div>";
                }

                echo "</div>"; // close discoverGames_wraper for the day group
            }
        } else {
            echo "<p>No events found for your skill level.</p>";
        }
        
    echo "</div>";
echo "</section>";
        
        
    } else {
        echo "Failed To Join";
    }
} else {
    echo "Try After Sometime";
}
?>
