<?php

function applyAutoConfirmAndMessage(mysqli $conn, int $gameId, bool $allowAutoConfirm = true)
{
        /* ===========================
       1. Fetch Event
    =========================== */
    $eventRes = mysqli_query($conn, "
        SELECT EVENT_CATEGORY, EVENT_VENUE, HOST_ID,EVENT_TIME,TO_TIME,AUTOMATION
        FROM ca_events
        WHERE ID = '$gameId'
        LIMIT 1
    ");
    
    if (!$eventRes || mysqli_num_rows($eventRes) === 0) return;

    $eventRow = mysqli_fetch_assoc($eventRes);
    if($eventRow['AUTOMATION'] == 'Y' )
    {
        $hostId = $eventRow['HOST_ID'];
        $event_time = $eventRow['EVENT_TIME'];
        $to_time = $eventRow['TO_TIME'];
        if (trim($eventRow['EVENT_CATEGORY']) !== 'Badminton Game') return;
    
        /* ===========================
           2. Fetch joined players (ORDERED)
        =========================== */
        $res = mysqli_query($conn, "
            SELECT ID
            FROM ca_gamejoin
            WHERE GAME_ID = '$gameId'
              AND STATUS = 'Y'
            ORDER BY CREATED_AT ASC
        ");
    
        $players = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $players[] = $r['ID'];
        }
    
        $joined = count($players);
        // if ($joined === 0) return;
    
        /* ===========================
           3. Load reference row
        =========================== */
        
        if ($joined < 4) {
            
            // echo "3";
                
                        $venueSql = "
                        SELECT COST 
                        FROM ca_venue 
                        WHERE NAME = '".$eventRow['EVENT_VENUE']."' AND HOST_ID='$hostId'
                        LIMIT 1
                    ";
                    $venueRes = mysqli_query($conn, $venueSql);
                    $venueRow = mysqli_fetch_assoc($venueRes);
                    
                    $court_rent_per_hour = (float)$venueRow['COST'];
                    
                    $birdieSql = "SELECT BRIDIE FROM ca_bride LIMIT 1";
                    $birdieRes = mysqli_query($conn, $birdieSql);
                    $birdieRow = mysqli_fetch_assoc($birdieRes);
                    
                    $birdie_price = (float)$birdieRow['BRIDIE'];
                    
                    $start = new DateTime($event_time);
                    $end   = new DateTime($to_time);
                    
                    $interval = $start->diff($end);
                    $hours = $interval->h + ($interval->i / 60);
                    
                    // safety
                    if ($hours <= 0) {
                        $hours = 1;
                    }
                    
                    $players = 4;
                    if($eventRow['EVENT_VENUE'] == 'Epic Badminton')
                    {
                        $birdies_used = $players * 2;
                    }
                    else
                    {
                        $birdies_used = $players;
                    }
                    
                    // Court cost
                    $facility_cost = ceil($court_rent_per_hour * $hours);
                    
                    // Birdie cost
                    $accessories_cost = ceil($birdie_price * $birdies_used);
                    
                    //club cost
                    $club_cost = 1 * 4;
                    
                    
                    // Total event cost
                    $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
                    
                    // Per player cost
                    $per_player_cost = ceil($total_event_cost / $players);
                    
                    
                    $message = '';
                    if($joined == 0)
                    {
                       $message ='0 court confirmed, 6 spots available for first court'; 
                    }
                    else if($joined == 1)
                    {
                       $message ='0 court confirmed, 5 spots available for first court'; 
                    }
                    else if($joined == 2)
                    {
                       $message ='0 court confirmed, 4 spots available for first court'; 
                    }
                     else if($joined == 3)
                    {
                       $message ='0 court confirmed, 3 spots available for first court'; 
                    }
                     else if($joined == 4)
                    {
                       $message ='0 court confirmed, 2 spots available for first court'; 
                    }
                    mysqli_query($conn, "
                        UPDATE ca_events
                        SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
                        WHERE ID = '$gameId'
                    ");
                    
                    $updateSql = "
                    UPDATE ca_events SET
                        EVENT_COST = '$per_player_cost',
                        FACILITY_COST = '$facility_cost',
                        ACCESSORIES_COST = '$accessories_cost',
                        TOTAL_EVENT_COST = '$total_event_cost',
                        CLUB_COST='$club_cost',
                        BIRDIE_USED='$birdies_used',
                        BIRDIE_COST= '$accessories_cost',
                        BIRDIE_PRICE='$birdie_price',
                        COURTS_CONFIRMED=0
                    WHERE ID = '$gameId'
                ";
                
                if ($allowAutoConfirm) {
                    mysqli_query($conn,"update ca_gamejoin set CONFIRMED='N' where GAME_ID='$gameId'");
                }
    
                
                mysqli_query($conn,"update ca_gamejoin set PRICE='$per_player_cost' where GAME_ID='$gameId'");
                
                mysqli_query($conn, $updateSql);
                        
                        $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
                        $fetch_data = mysqli_fetch_assoc($slect_data);
                        $total_player_cost = $fetch_data['PRICE'];
                        $profit_loss = ceil($total_player_cost - $total_event_cost);
                    
                        mysqli_query($conn, "
                            UPDATE ca_events SET
                                TOTAL_PLAYER_COST = '$total_player_cost',
                                PROFIT_LOSS = '$profit_loss'
                            WHERE ID = '$gameId'
                        ");
            }
            else
            {
                // echo "4";
                        $refRes = mysqli_query($conn, "
                            SELECT *
                            FROM casa_game_test_cases
                            WHERE players_joined = '$joined' AND venue_name!='Manual'
                            LIMIT 1
                        ");
                        if (!$refRes || mysqli_num_rows($refRes) === 0) return;
                    
                        $ref = mysqli_fetch_assoc($refRes);
                        $message = $ref['game_message'];
                        mysqli_query($conn, "
                            UPDATE ca_events
                            SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
                            WHERE ID = '$gameId'
                        ");
                        
                    
                        /* ===========================
                           4. Auto confirmation
                        =========================== */
                        if ($allowAutoConfirm) {
                            $autoConfirm = trim($ref['auto_confirm']);
                        
                            if ($autoConfirm === 'Confirm prior') {
                                mysqli_query($conn, "
                                    UPDATE ca_gamejoin
                                    SET CONFIRMED = 'Y'
                                    WHERE GAME_ID = '$gameId'
                                      AND STATUS = 'Y'
                                ");
                            }
                            elseif ($autoConfirm === 'Auto confirm') {
                                $lastPlayerId = end($players);
                                mysqli_query($conn, "
                                    UPDATE ca_gamejoin
                                    SET CONFIRMED = 'Y'
                                    WHERE ID = '$lastPlayerId'
                                ");
                            }
                            elseif ($autoConfirm === 'Wait') {
                                $lastPlayerId = end($players);
                                mysqli_query($conn, "
                                    UPDATE ca_gamejoin
                                    SET CONFIRMED = 'N'
                                    WHERE ID = '$lastPlayerId'
                                ");
                            }
                        }
                        // Wait → do nothing
                    
                        /* ===========================
                           5. Count confirmed players
                        =========================== */
                        
                        if($autoConfirm !=='Wait')
                        {
                            $confRes = mysqli_query($conn, "
                                SELECT COUNT(*) AS total
                                FROM ca_gamejoin
                                WHERE GAME_ID = '$gameId'
                                  AND CONFIRMED = 'Y'
                            ");
                            $confirmed_players = (int)mysqli_fetch_assoc($confRes)['total'];
                    
                
                            /* Venue (court rent per hour) */
                            $venueRes = mysqli_query($conn, "
                                SELECT COST FROM ca_venue
                                WHERE NAME = '".mysqli_real_escape_string($conn, $eventRow['EVENT_VENUE'])."' AND HOST_ID='$hostId'
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
                            $start = new DateTime($eventRow['EVENT_TIME']);
                            $end   = new DateTime($eventRow['TO_TIME']);
                            $interval = $start->diff($end);
                            $hours = $interval->h + ($interval->i / 60);
                            if ($hours <= 0) {
                                $hours = 1;
                            }
                        
                            /* Cost calculations */
                            echo $facility_cost    = $court_rent_per_hour * $hours * $ref['courts_confirmed'];
                            echo $accessories_cost = $birdie_price * $birdies_used;
                            echo $club_cost        = 1 * $confirmed_players;
                        
                            $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
                        
                            /* Per-player cost now dynamic */
                            echo $event_cost = ceil($total_event_cost / $confirmed_players);
                        
                            $update_players_price = mysqli_query($conn,"update ca_gamejoin set PRICE='$event_cost' where GAME_ID='$gameId' AND CONFIRMED = 'Y'");
                            // $total_player_cost = round($confirmed_players * $event_cost, 2);
                            $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
                            $fetch_data = mysqli_fetch_assoc($slect_data);
                            $total_player_cost = $fetch_data['PRICE'];
                            $profit_loss = ceil($total_player_cost - $total_event_cost);
                            echo "UPDATE ca_events SET
                                    EVENT_COST = '$event_cost',
                                    FACILITY_COST = '$facility_cost',
                                    ACCESSORIES_COST = '$accessories_cost',
                                    TOTAL_EVENT_COST = '$total_event_cost',
                                    TOTAL_PLAYER_COST = '$total_player_cost',
                                    CLUB_COST = '$club_cost',
                                    BIRDIE_USED = '$birdies_used',
                                    PROFIT_LOSS = '$profit_loss',
                                    BIRDIE_COST= '$accessories_cost',
                                    BIRDIE_PRICE='$birdie_price',
                                    COURTS_CONFIRMED='".$ref['courts_confirmed']."'
                                WHERE ID = '$gameId'";
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
                                    PROFIT_LOSS = '$profit_loss',
                                    BIRDIE_COST= '$accessories_cost',
                                    BIRDIE_PRICE='$birdie_price',
                                    COURTS_CONFIRMED='".$ref['courts_confirmed']."'
                                WHERE ID = '$gameId'
                            ");
                        }
            }
    }
    else
    {
        $res = mysqli_query($conn, "
            SELECT ID
            FROM ca_gamejoin
            WHERE GAME_ID = '$gameId'
              AND STATUS = 'Y'
            ORDER BY CREATED_AT ASC
        ");
    
        $players = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $players[] = $r['ID'];
        }
    
        $joined = count($players);
        $refRes = mysqli_query($conn, "
                            SELECT *
                            FROM casa_game_test_cases
                            WHERE players_joined = '$joined' AND venue_name='Manual'
                            LIMIT 1
                        ");
        $ref = mysqli_fetch_assoc($refRes);
        $message = $ref['game_message'];
                        
        mysqli_query($conn, "UPDATE ca_events SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
        WHERE ID = '$gameId'
    ");
    }
}
   

function applyAutoConfirmAndMessageReverse(mysqli $conn, int $gameId)
{
    $eventRes = mysqli_query($conn, "
        SELECT EVENT_CATEGORY
        FROM ca_events
        WHERE ID = '$gameId'
        LIMIT 1
    ");

    if (!$eventRes || mysqli_num_rows($eventRes) == 0) return;

    $event = mysqli_fetch_assoc($eventRes);

    if (trim($event['EVENT_CATEGORY']) !== 'Badminton Game') {
        return;
    }

    /* --------------------------------------------------
       2️⃣ Fetch joined players (ORDER MATTERS)
    -------------------------------------------------- */
    $res = mysqli_query($conn, "
        SELECT ID
        FROM ca_gamejoin
        WHERE GAME_ID = '$gameId'
          AND STATUS = 'Y'
        ORDER BY CREATED_AT ASC
    ");

    $players = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $players[] = $row['ID'];
    }

    $joined = count($players);

    /* --------------------------------------------------
       3️⃣ RESET ALL CONFIRMATIONS (IMPORTANT)
    -------------------------------------------------- */
    mysqli_query($conn, "
        UPDATE ca_gamejoin
        SET CONFIRMED = 'N'
        WHERE GAME_ID = '$gameId'
    ");

    /* --------------------------------------------------
       4️⃣ CONFIRM LOGIC (EXACT MATCH WITH YOUR SHEET)
    -------------------------------------------------- */
    $confirm_ids = [];

    if ($joined >= 4) {

        $block = $joined % 6;
        $courts = floor($joined / 4);

        // 4, 10, 16...
        if ($block == 4) {
            $start = ($courts - 1) * 4;
            $confirm_ids = array_slice($players, $start, 4);
        }

        // 5 or 6 → all confirmed
        elseif ($block == 5 || $block == 0) {
            $confirm_ids = $players;
        }
    }

    if (!empty($confirm_ids)) {
        mysqli_query($conn, "
            UPDATE ca_gamejoin
            SET CONFIRMED = 'Y'
            WHERE ID IN (" . implode(',', $confirm_ids) . ")
        ");
    }

    /* --------------------------------------------------
       5️⃣ MESSAGE LOGIC
    -------------------------------------------------- */
    $courts_confirmed = floor($joined / 4);
    $spots_available  = ($joined % 6 == 0) ? 6 : 6 - ($joined % 6);

    if ($courts_confirmed == 0) {
        $message = "0 Court Confirmed Yet, {$spots_available} Spots Available";
    } else {
        $message = "{$courts_confirmed} Court Confirmed, {$spots_available} Spots Available";
    }

    mysqli_query($conn, "
        UPDATE ca_events
        SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
        WHERE ID = '$gameId'
    ");
        
        //update event table attributes
        /* ================================
           COST RECALCULATION AFTER JOIN
           ================================ */
        
        /* 1️⃣ Get confirmed players count */
        $confirmedRes = mysqli_query($conn, "
            SELECT COUNT(*) AS total
            FROM ca_gamejoin
            WHERE GAME_ID = '$gameId'
              AND STATUS = 'Y'
              AND CONFIRMED = 'Y'
        ");
        $row = mysqli_fetch_assoc($confirmedRes);
         echo $confirmed_players = (int)$row['total'];
        
        if ($confirmed_players == 0) {
            $confirmed_players = 1; // safety
        }
        
        /* 2️⃣ Fetch current event cost data */
        $eventRes = mysqli_query($conn, "
            SELECT HOST_ID,EVENT_COST, TOTAL_EVENT_COST, EVENT_VENUE, EVENT_TIME, TO_TIME
            FROM ca_events
            WHERE ID = '$gameId'
        ");
        $event = mysqli_fetch_assoc($eventRes);
        
        $current_event_cost = (float)$event['EVENT_COST'];
        $current_total_event_cost = (float)$event['TOTAL_EVENT_COST'];
        $hostId = $event['HOST_ID'];
        $event_time = $event['EVENT_TIME'];
        $to_time = $event['TO_TIME'];
        $event_venue = $event['EVENT_VENUE'];
        
        /* ================================
          CASE 1: confirmed players ≤ 4
          ================================ */
        if ($confirmed_players <= 4) {
            
            $venueSql = "
            SELECT COST 
            FROM ca_venue 
            WHERE NAME = '".$event['EVENT_VENUE']."' AND HOST_ID='$hostId'
            LIMIT 1
        ";
        $venueRes = mysqli_query($conn, $venueSql);
        $venueRow = mysqli_fetch_assoc($venueRes);
        
        $court_rent_per_hour = (float)$venueRow['COST'];
        
        $birdieSql = "SELECT BRIDIE FROM ca_bride LIMIT 1";
        $birdieRes = mysqli_query($conn, $birdieSql);
        $birdieRow = mysqli_fetch_assoc($birdieRes);
        
        $birdie_price = (float)$birdieRow['BRIDIE'];
        
        $start = new DateTime($event_time);
        $end   = new DateTime($to_time);
        
        $interval = $start->diff($end);
        $hours = $interval->h + ($interval->i / 60);
        
        // safety
        if ($hours <= 0) {
            $hours = 1;
        }
        
        $players = 4;
        if($event_venue == 'Epic Badminton')
        {
        $birdies_used = $players * 2;
        }
        else
        {
            $birdies_used = $players;
        }
        
        // Court cost
        $facility_cost = $court_rent_per_hour * $hours;
        
        // Birdie cost
        $accessories_cost = $birdie_price * $birdies_used;
        
        //club cost
        $club_cost = 1 * 4;
        
        
        // Total event cost
        $total_event_cost = $facility_cost + $accessories_cost + $club_cost;
        
        // Per player cost
        $per_player_cost = ceil($total_event_cost / $players);
        
        $updateSql = "
        UPDATE ca_events SET
            EVENT_COST = '$per_player_cost',
            FACILITY_COST = '$facility_cost',
            ACCESSORIES_COST = '$accessories_cost',
            TOTAL_EVENT_COST = '$total_event_cost',
            CLUB_COST='$club_cost',
            BIRDIE_USED='$birdies_used'
        WHERE ID = '$gameId'
    ";
    
    mysqli_query($conn,"update ca_gamejoin set PRICE='$per_player_cost' where GAME_ID='$gameId'");
    
    mysqli_query($conn, $updateSql);
            
            $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
            $fetch_data = mysqli_fetch_assoc($slect_data);
            $total_player_cost = $fetch_data['PRICE'];
            $profit_loss = ceil($total_player_cost - $current_total_event_cost);
        
            mysqli_query($conn, "
                UPDATE ca_events SET
                    TOTAL_PLAYER_COST = '$total_player_cost',
                    PROFIT_LOSS = '$profit_loss'
                WHERE ID = '$gameId'
            ");
        }
        
        /* ================================
          CASE 2: confirmed players > 4
          ================================ */
        else {
        
            $venue = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COST FROM ca_venue
        WHERE NAME='{$event['EVENT_VENUE']}'
        AND HOST_ID='{$event['HOST_ID']}'
    "))['COST'];

    $birdie = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT BRIDIE FROM ca_bride
        WHERE HOST_ID='{$event['HOST_ID']}'
    "))['BRIDIE'];

    $hours = max(
        1,
        (strtotime($event['TO_TIME']) - strtotime($event['EVENT_TIME'])) / 3600
    );

    $birdies = ($event['EVENT_VENUE'] === 'Epic Badminton')
        ? $confirmed_players * 2
        : $confirmed_players;

    $facility = $venue * $hours;
    $access   = $birdie * $birdies;
    $club     = $confirmed_players;

    $total = $facility + $access + $club;
    $perPlayer = ceil($total / $confirmed_players);

    mysqli_query($conn,"
        UPDATE ca_gamejoin
        SET PRICE='$perPlayer'
        WHERE GAME_ID='$gameId' AND CONFIRMED='Y'
    ");

    $sum = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT SUM(PRICE) total
        FROM ca_gamejoin
        WHERE GAME_ID='$gameId' AND CONFIRMED='Y'
    "))['total'];
    
    echo $sum.'-'.$total;

    mysqli_query($conn,"
        UPDATE ca_events SET
            EVENT_COST='$perPlayer',
            FACILITY_COST='$facility',
            ACCESSORIES_COST='$access',
            TOTAL_EVENT_COST='$total',
            TOTAL_PLAYER_COST='$sum',
            CLUB_COST='$club',
            BIRDIE_USED='$birdies',
            PROFIT_LOSS='".($sum - $total)."'
        WHERE ID='$gameId'
    ");
        }
        
}

