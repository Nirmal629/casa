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
    $query = "INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID,PRICE,CURRENCY,TYPE,STATUS, CREATED_AT) VALUES ('$userId', '$gameId', '$hostId','$cost', '$currency','Public','Y','$createdAt')";
    $result = mysqli_query($conn, $query);

    if ($result) {
         $eventSql = "
            SELECT EVENT_CATEGORY,EVENT_VENUE
            FROM ca_events
            WHERE ID = '$gameId'
            LIMIT 1
        ";
        $eventRes = mysqli_query($conn, $eventSql);
        $eventRow = mysqli_fetch_assoc($eventRes);
        
        if (trim($eventRow['EVENT_CATEGORY']) === 'Badminton Game') {
        
        $game_id = $gameId;

        /* 1️⃣ Fetch joined players in join order */
        $sql = "
            SELECT ID
            FROM ca_gamejoin
            WHERE GAME_ID = '$game_id'
              AND STATUS = 'Y'
            ORDER BY CREATED_AT ASC
        ";
        $res = mysqli_query($conn, $sql);
        
        $players = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $players[] = $row['ID'];
        }
        
        $joined = count($players);
        
        /* 2️⃣ Courts confirmed */
        $courts_confirmed = floor($joined / 4);
        
        /* 3️⃣ Spots available (Excel logic) */
        $block_position = $joined % 6;
        $spots_available = ($block_position == 0) ? 6 : 6 - $block_position;
        
        /* 4️⃣ Defaults */
        $confirm_ids = [];
        $action = "No Action";
        
        /* 5️⃣ Confirmation logic (dynamic & correct) */
        if ($block_position == 4) {
            // Confirm 4 players for a new court
            $start_index = ($courts_confirmed - 1) * 4;
            $confirm_ids = array_slice($players, $start_index, 4);
            $action = "Send Confirmation Msg";
        }
        elseif ($block_position == 5 || $block_position == 0) {
            // Auto confirm latest player
            $confirm_ids = [ end($players) ];
            $action = "Send Confirmation Msg";
        }
        
        /* 6️⃣ Update confirmed players safely */
        if (!empty($confirm_ids)) {
            $ids = implode(',', $confirm_ids);
            mysqli_query($conn, "
                UPDATE ca_gamejoin
                SET CONFIRMED = 'Y'
                WHERE ID IN ($ids)
                  AND CONFIRMED = 'N'
            ");
        }
        
        /* 7️⃣ Build message (EXACT text you want to show) */
        if ($courts_confirmed == 0) {
            $message = "0 Court Confirmed Yet, {$spots_available} Spots Available";
        } else {
            $message = "{$courts_confirmed} Court Confirmed, {$spots_available} Spots Available";
        }
        
        /* 8️⃣ SAVE MESSAGE IN ca_events */
        mysqli_query($conn, "
            UPDATE ca_events
            SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
            WHERE ID = '$game_id'
        ");
        
        //update event table attributes
        /* ================================
           COST RECALCULATION AFTER JOIN
           ================================ */
        
        /* 1️⃣ Get confirmed players count */
        $confirmedRes = mysqli_query($conn, "
            SELECT COUNT(*) AS total
            FROM ca_gamejoin
            WHERE GAME_ID = '$game_id'
              AND STATUS = 'Y'
              AND CONFIRMED = 'Y'
        ");
        $row = mysqli_fetch_assoc($confirmedRes);
        $confirmed_players = (int)$row['total'];
        
        if ($confirmed_players == 0) {
            $confirmed_players = 1; // safety
        }
        
        /* 2️⃣ Fetch current event cost data */
        $eventRes = mysqli_query($conn, "
            SELECT EVENT_COST, TOTAL_EVENT_COST, EVENT_VENUE, EVENT_TIME, TO_TIME
            FROM ca_events
            WHERE ID = '$game_id'
        ");
        $event = mysqli_fetch_assoc($eventRes);
        
        $current_event_cost = (float)$event['EVENT_COST'];
        $current_total_event_cost = (float)$event['TOTAL_EVENT_COST'];
        
        /* ================================
          CASE 1: confirmed players ≤ 4
          ================================ */
        if ($confirmed_players <= 4) {
            
            $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$game_id."' AND CONFIRMED='Y'");
            $fetch_data = mysqli_fetch_assoc($slect_data);
            $total_player_cost = $fetch_data['PRICE'];
            $profit_loss = round($total_player_cost - $current_total_event_cost, 2);
        
            mysqli_query($conn, "
                UPDATE ca_events SET
                    TOTAL_PLAYER_COST = '$total_player_cost',
                    PROFIT_LOSS = '$profit_loss'
                WHERE ID = '$game_id'
            ");
        }
        
        /* ================================
          CASE 2: confirmed players > 4
          ================================ */
        else {
        
            /* Venue (court rent per hour) */
            $venueRes = mysqli_query($conn, "
                SELECT COST FROM ca_venue
                WHERE NAME = '".mysqli_real_escape_string($conn, $event['EVENT_VENUE'])."' AND HOST_ID='$hostId'
                LIMIT 1
            ");
            $venueRow = mysqli_fetch_assoc($venueRes);
            $court_rent_per_hour = $venueRow['COST'];
        
            /* Birdie price (dynamic) */
            $birdieRes = mysqli_query($conn, "SELECT BRIDIE FROM ca_bride WHERE HOST_ID='$hostId' LIMIT 1");
            $birdieRow = mysqli_fetch_assoc($birdieRes);
            $birdie_price = $birdieRow['BRIDIE'];
        
            /* Birdies used = confirmed players */
            if (trim($eventRow['EVENT_VENUE']) === 'Epic Badminton') {
            $birdies_used = $confirmed_players * 2;
            }
            else
            {
                $birdies_used = $confirmed_players;
            }
        
            /* Time calculation */
            $start = new DateTime($event['EVENT_TIME']);
            $end   = new DateTime($event['TO_TIME']);
            $interval = $start->diff($end);
            $hours = $interval->h + ($interval->i / 60);
            if ($hours <= 0) {
                $hours = 1;
            }
        
            /* Cost calculations */
            $facility_cost    = $court_rent_per_hour * $hours;
            $accessories_cost = $birdie_price * $birdies_used;
            $club_cost        = 1 * $confirmed_players;
        
            $total_event_cost = round($facility_cost + $accessories_cost + $club_cost,2);
        
            /* Per-player cost now dynamic */
            $event_cost = round($total_event_cost / $confirmed_players, 2);
        
            $update_players_price = mysqli_query($conn,"update ca_gamejoin set PRICE='$event_cost' where GAME_ID='$game_id' AND CONFIRMED = 'Y'");
            // $total_player_cost = round($confirmed_players * $event_cost, 2);
            $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$game_id."' AND CONFIRMED='Y'");
            $fetch_data = mysqli_fetch_assoc($slect_data);
            $total_player_cost = $fetch_data['PRICE'];
            $profit_loss = round($total_player_cost - $total_event_cost, 2);
        
            /* Update ALL fields */
            mysqli_query($conn, "
                UPDATE ca_events SET
                    EVENT_COST = '$event_cost',
                    FACILITY_COST = '$facility_cost',
                    ACCESSORIES_COST = '$accessories_cost',
                    TOTAL_EVENT_COST = '$total_event_cost',
                    TOTAL_PLAYER_COST = '$total_player_cost',
                    CLUB_COST = '$club_cost',
                    BIRDIE_USED = '$birdies_used',
                    PROFIT_LOSS = '$profit_loss'
                WHERE ID = '$game_id'
            ");
        }
        }


        echo json_encode(['status' => 'success', 'message' => 'Event Joined successfully.','outputHTML'=>""]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to Join.','outputHTML'=>null]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
