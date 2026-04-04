<?php
include 'dbConnection.php';
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$gameId = $data['eventID'];
// print_r($data);
// echo "UPDATE ca_events 
//         SET AUTOMATION='".$data['AUTOMATION']."'
//         WHERE ID = '$gameId'";exit;
mysqli_query($conn,"UPDATE ca_events 
        SET AUTOMATION='".$data['AUTOMATION']."'
        WHERE ID = '$gameId'");

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
    // print_r($eventRow);
    if($data['AUTOMATION'] == 'Y' )
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
        if ($joined === 0) return;
    
        /* ===========================
           3. Load reference row
        =========================== */
        
        if($joined < 4 )
        {
            mysqli_query($conn, "
                                        UPDATE ca_gamejoin
                                        SET CONFIRMED = 'Y'
                                        WHERE GAME_ID = '$gameId'
                                          AND STATUS = 'Y'
                                    ");
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
                                    //  $facility_cost    = $court_rent_per_hour * $hours * $ref['courts_confirmed'];
                                     $facility_cost    = $court_rent_per_hour * $hours * 1;
                                     $accessories_cost = $birdie_price * $birdies_used;
                                     $club_cost        = 1 * $confirmed_players;
                                
                                    $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
                                
                                    /* Per-player cost now dynamic */
                                     $event_cost = ceil($total_event_cost / $confirmed_players);
                                
                                    $update_players_price = mysqli_query($conn,"update ca_gamejoin set PRICE='$event_cost' where GAME_ID='$gameId' AND CONFIRMED = 'Y'");
                                    // $total_player_cost = round($confirmed_players * $event_cost, 2);
                                    $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
                                    $fetch_data = mysqli_fetch_assoc($slect_data);
                                    $total_player_cost = $fetch_data['PRICE'];
                                    $profit_loss = ceil($total_player_cost - $total_event_cost);
                                
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
                                            BIRDIE_PRICE='$birdie_price'
                                        WHERE ID = '$gameId'
                                    ");
        }
        else
        {
            $refResCase = mysqli_query($conn, "
                            SELECT *
                            FROM casa_game_test_cases
                            WHERE players_joined = '$joined' AND venue_name!='Manual'
                            LIMIT 1
                        ");
                if (!$refResCase || mysqli_num_rows($refResCase) === 0) return;
                            
                $refCaseRes = mysqli_fetch_assoc($refResCase);
                
                $autoConfirm = $refCaseRes['auto_confirm'];
                
        
                
                if ($autoConfirm === 'Auto confirm' || $autoConfirm === 'Confirm prior') {
                    // echo "auto";
                    $message = $refCaseRes['game_message'];
                    mysqli_query($conn, "
                        UPDATE ca_events
                        SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
                        WHERE ID = '$gameId'
                    ");
                    mysqli_query($conn, "
                                        UPDATE ca_gamejoin
                                        SET CONFIRMED = 'Y'
                                        WHERE GAME_ID = '$gameId'
                                          AND STATUS = 'Y'
                                    ");
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
                                     $facility_cost    = $court_rent_per_hour * $hours * $refCaseRes['courts_confirmed'];
                                     $accessories_cost = $birdie_price * $birdies_used;
                                     $club_cost        = 1 * $confirmed_players;
                                
                                    $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
                                
                                    /* Per-player cost now dynamic */
                                     $event_cost = ceil($total_event_cost / $confirmed_players);
                                
                                    $update_players_price = mysqli_query($conn,"update ca_gamejoin set PRICE='$event_cost' where GAME_ID='$gameId' AND CONFIRMED = 'Y'");
                                    // $total_player_cost = round($confirmed_players * $event_cost, 2);
                                    $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
                                    $fetch_data = mysqli_fetch_assoc($slect_data);
                                    $total_player_cost = $fetch_data['PRICE'];
                                    $profit_loss = ceil($total_player_cost - $total_event_cost);
                                
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
                                            COURTS_CONFIRMED='".$refCaseRes['courts_confirmed']."'
                                        WHERE ID = '$gameId'
                                    ");
        }
                else
                {
                    // echo "wait";
                    $message = $refCaseRes['game_message'];
                    mysqli_query($conn, "
                        UPDATE ca_events
                        SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
                        WHERE ID = '$gameId'
                    ");
                    
                    $prevRefRes = mysqli_query($conn, "
                        SELECT *
                        FROM casa_game_test_cases
                        WHERE players_joined < '$joined'
                          AND auto_confirm IN ('Auto confirm', 'Confirm prior')
                          AND venue_name != 'Manual'
                        ORDER BY players_joined DESC
                        LIMIT 1
                    ");
            
                    if ($prevRefRes && mysqli_num_rows($prevRefRes) > 0) {
                        $ref = mysqli_fetch_assoc($prevRefRes);
                        $autoConfirm = trim($ref['auto_confirm']);
                    }
                    // echo $autoConfirm;
                    // print_r($ref);
                    // exit;
                    
                        if ($autoConfirm === 'Auto confirm' || $autoConfirm === 'Confirm prior') {
                            // echo "
                            //                 SELECT ID
                            //                 FROM ca_gamejoin
                            //                 WHERE GAME_ID = '$gameId'
                            //                   AND STATUS = 'Y'
                            //                 ORDER BY ID DESC LIMIT 1
                            //             ";
                                    $resLastJoined = mysqli_query($conn, "
                                            SELECT *
                                            FROM ca_gamejoin
                                            WHERE GAME_ID = '$gameId'
                                              AND STATUS = 'Y'
                                            ORDER BY ID DESC LIMIT 1
                                        ");
                                    $fetchLastJoined = mysqli_fetch_assoc($resLastJoined);
                        // echo "
                        //                     UPDATE ca_gamejoin
                        //                     SET CONFIRMED = 'Y'
                        //                     WHERE GAME_ID = '$gameId' AND USER_ID!='".$fetchLastJoined['USER_ID']."'
                        //                       AND STATUS = 'Y'
                        //                 ";
                        mysqli_query($conn, "
                                            UPDATE ca_gamejoin
                                            SET CONFIRMED = 'Y'
                                            WHERE GAME_ID = '$gameId' AND USER_ID!='".$fetchLastJoined['USER_ID']."'
                                              AND STATUS = 'Y'
                                        ");
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
                                        // echo $ref['courts_confirmed']."reffff";
                                            /* Cost calculations */
                                             $facility_cost    = $court_rent_per_hour * $hours * $refCaseRes['courts_confirmed'];
                                             $accessories_cost = $birdie_price * $birdies_used;
                                             $club_cost        = 1 * $confirmed_players;
                                        
                                            $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
                                        
                                            /* Per-player cost now dynamic */
                                             $event_cost = ceil($total_event_cost / $confirmed_players);
                                        
                                            $update_players_price = mysqli_query($conn,"update ca_gamejoin set PRICE='$event_cost' where GAME_ID='$gameId' AND CONFIRMED = 'Y'");
                                            // $total_player_cost = round($confirmed_players * $event_cost, 2);
                                            $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
                                            $fetch_data = mysqli_fetch_assoc($slect_data);
                                            $total_player_cost = $fetch_data['PRICE'];
                                            $profit_loss = ceil($total_player_cost - $total_event_cost);
                                        
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
                                                    COURTS_CONFIRMED='".$refCaseRes['courts_confirmed']."'
                                                WHERE ID = '$gameId'
                                            ");
                        }
            
                    }
        }
         
            
    }
    else
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
        if ($joined === 0) return;
    
        /* ===========================
           3. Load reference row
        =========================== */
        
        if($joined < 4 )
        {
            mysqli_query($conn, "
                                        UPDATE ca_gamejoin
                                        SET CONFIRMED = 'Y'
                                        WHERE GAME_ID = '$gameId'
                                          AND STATUS = 'Y'
                                    ");
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
                                    //  $facility_cost    = $court_rent_per_hour * $hours * $ref['courts_confirmed'];
                                     $facility_cost    = $court_rent_per_hour * $hours * 1;
                                     $accessories_cost = $birdie_price * $birdies_used;
                                     $club_cost        = 1 * $confirmed_players;
                                
                                    $total_event_cost = ceil($facility_cost + $accessories_cost + $club_cost);
                                
                                    /* Per-player cost now dynamic */
                                     $event_cost = ceil($total_event_cost / $confirmed_players);
                                
                                    $update_players_price = mysqli_query($conn,"update ca_gamejoin set PRICE='$event_cost' where GAME_ID='$gameId' AND CONFIRMED = 'Y'");
                                    // $total_player_cost = round($confirmed_players * $event_cost, 2);
                                    $slect_data = mysqli_query($conn,"select sum(PRICE) as PRICE from ca_gamejoin where GAME_ID='".$gameId."' AND CONFIRMED='Y'");
                                    $fetch_data = mysqli_fetch_assoc($slect_data);
                                    $total_player_cost = $fetch_data['PRICE'];
                                    $profit_loss = ceil($total_player_cost - $total_event_cost);
                                
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
                                            COURTS_CONFIRMED=1
                                        WHERE ID = '$gameId'
                                    ");
        }
    }
    


?>