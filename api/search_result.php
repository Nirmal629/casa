<?php
session_start();
include('dbConnection.php');
// print_r($_POST);
//   $search = isset($_POST['query']) ? mysqli_real_escape_string($conn, $_POST['query']) : '';

//     if($search!='')
//     {
//         $select_game = mysqli_query($conn,"select * from ca_events where ID='" . $_POST['ID'] . "'");
//         $fetch_game = mysqli_fetch_assoc($select_game);
//         $gender_condition = "";
//         if ($fetch_game['GENDER_CATEGORY'] == 'Mix') {
//             $gender_condition = "AND cu.GENDER IN ('Male', 'Female', 'Kid')";
//         } else {
//             $gender_condition = "AND cu.GENDER = '" . $fetch_game['GENDER_CATEGORY'] . "'";
//         }
//       $select_Player = mysqli_query($conn, "
//         SELECT cu.*, 
//                 cg.CONFIRMED AS CONFIRMED,
//               cg.PRICE AS JOINED_PRICE, 
//               IF(cg.USER_ID IS NOT NULL, 1, 0) AS IS_JOINED 
//         FROM ca_users cu
//         LEFT JOIN ca_gamejoin cg ON cu.ID = cg.USER_ID AND cg.GAME_ID = '" . $_POST['ID'] . "'
//         WHERE cu.USERTYPE='Player' 
//           AND cu.LOG_STATUS='Y' 
//           AND cu.DEL_STATUS='N' 
//           $gender_condition
//           AND (cu.NAME LIKE '%$search%' OR cu.WHATSAPP_NUMBER LIKE '%$search%')
//         ORDER BY IS_JOINED DESC
//     ");
//     }
//     else
//     {
//         $select_game = mysqli_query($conn,"select * from ca_events where ID='" . $_POST['ID'] . "'");
//         $fetch_game = mysqli_fetch_assoc($select_game);
//         $gender_condition = "";
//         if ($fetch_game['GENDER_CATEGORY'] == 'Mix') {
//             $gender_condition = "AND cu.GENDER IN ('Male', 'Female', 'Kid')";
//         } else {
//             $gender_condition = "AND cu.GENDER = '" . $fetch_game['GENDER_CATEGORY'] . "'";
//         }
//         $select_Player = mysqli_query($conn, "
//         SELECT cu.*, 
//             cg.CONFIRMED AS CONFIRMED,
//               cg.PRICE AS JOINED_PRICE, 
//               IF(cg.USER_ID IS NOT NULL, 1, 0) AS IS_JOINED 
//         FROM ca_users cu
//         LEFT JOIN ca_gamejoin cg ON cu.ID = cg.USER_ID AND cg.GAME_ID = '" . $_POST['ID'] . "'
//         WHERE cu.USERTYPE='Player' 
//           AND cu.LOG_STATUS='Y' 
//           AND cu.DEL_STATUS='N' 
//           $gender_condition
//         ORDER BY IS_JOINED DESC
//         ");
//     }
$search       = isset($_POST['query']) ? mysqli_real_escape_string($conn, $_POST['query']) : '';
$gameId       = isset($_POST['ID']) ? mysqli_real_escape_string($conn, $_POST['ID']) : '';
$hostId       = isset($_POST['HOST_ID']) ? mysqli_real_escape_string($conn, $_POST['HOST_ID']) : '';
$gender       = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : '';
$skill_level  = isset($_POST['skill_level']) ? mysqli_real_escape_string($conn, $_POST['skill_level']) : '';

// Build gender + skill filter
$gender_condition = "";
if ($gender === 'Mix') {
    $gender_condition = "AND cu.GENDER IN ('Male', 'Female', 'Kid') AND cu.VERIFIED_LEVEL = '$skill_level'";
} else {
    $gender_condition = "AND cu.GENDER = '$gender' AND cu.VERIFIED_LEVEL = '$skill_level'";
}

// Search condition
$search_condition = "";
if (!empty($search)) {
    $search_condition = "AND (cu.NAME LIKE '%$search%' OR cu.WHATSAPP_NUMBER LIKE '%$search%')";
}

// Final query
$query = "
    SELECT cu.*, 
           cg.CONFIRMED AS CONFIRMED,
           cg.PRICE AS JOINED_PRICE,
           IF(cg.USER_ID IS NOT NULL, 1, 0) AS IS_JOINED,
           IFNULL(gj.total_games, 0) AS TOTAL_GAMES
    FROM ca_users cu
    LEFT JOIN ca_gamejoin cg ON cu.ID = cg.USER_ID AND cg.GAME_ID = '$gameId'
    LEFT JOIN (
        SELECT USER_ID, COUNT(*) AS total_games
        FROM ca_gamejoin
        GROUP BY USER_ID
    ) gj ON cu.ID = gj.USER_ID
    WHERE cu.USERTYPE = 'Player'
      AND cu.LOG_STATUS = 'Y'
      AND cu.DEL_STATUS = 'N'
      $gender_condition
      $search_condition
    ORDER BY IS_JOINED DESC,TOTAL_GAMES DESC
";

$select_Player = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($select_Player) > 0) {
        // Fetch event details once
        if (!isset($fetch_event)) {
            $select_Event = mysqli_query($conn, "SELECT * FROM ca_events WHERE ID='" . $_POST['ID'] . "'");
            $fetch_event = mysqli_fetch_assoc($select_Event);
        }
    $i = 1;
        while ($player = mysqli_fetch_assoc($select_Player)) {
            $is_joined = $player['IS_JOINED'] == 1;
            $price = $is_joined ? $player['JOINED_PRICE'] : $fetch_event['EVENT_COST']; // Get price from ca_gamejoin if joined
    
            // Determine checkbox attributes
            $checked = $is_joined ? 'checked' : '';
            $disabled = $is_joined ? '' : 'disabled';
            
            $checked_con = $player['CONFIRMED'] == 'Y' ? 'checked' : '';
            $disabled_con = $player['CONFIRMED'] == 'Y' ? 'disabled' : '';

    
            // Display player details
            echo "
            <div class='Profiletable_wrap'>
                <div class='plyardetails'>
                    <div class='d-flex justify-content-start gap-2 w-50'>
                        <div class='slno'>$i</div>
                        <div class='d-flex align-items-center justify-content-start flex-wrap gap-2'>
                            <h6 class='name mb-0'><strong>" . $player['NAME'] . " (" . $player['GENDER'] . ")</strong></h6>
                            <p class='text-danger'>{$player['WHATSAPP_NUMBER']}</p>
                            <p class='bg-info text-white rounded px-1'>".$player['VERIFIED_LEVEL']."</p>
                        </div>
                    </div>
                    " . ($is_joined ? "
                    <div class='price_input' style='display: flex; justify-content: end; gap: 5px;'>
                        <input type='text' class='form-control player-price' id='price_".$player['ID']."' value='".$price."' style='max-width:80px; min-width: 55px; padding: 4px 5px;'/>
                        <button class='btn badge btn-success save-price' data-user-id='".$player['ID']."' data-game-id='".$_POST['ID']."'>Save</button>
                    </div>
                    " : "") . "
                    <div>
                        <div class='invite_btn'>
                            <span>Add Player</span>
                            <input type='checkbox' class='invite-checkbox' data-type='Public' 
                                data-user-id='".$player['ID']."' 
                                data-game-id='".$_POST['ID']."' 
                                data-host-id='".$_POST['HOST_ID']."' 
                                data-price-id='".$fetch_event['EVENT_COST']."' 
                                data-currency-id='".$fetch_event['EVENT_CURRENCY']."' 
                                $checked />
                        </div>
                        <div class='invite_btnnn'>
                            <span>Confirm</span>
                            <input type='checkbox' class='invite-checkboxx' data-type='Public' 
                                data-user-id='".$player['ID']."' 
                                data-game-id='".$_POST['ID']."' 
                                data-host-id='".$_POST['HOST_ID']."' 
                                data-price-id='".$fetch_event['EVENT_COST']."' 
                                data-currency-id='".$fetch_event['EVENT_CURRENCY']."' 
                                $checked_con/>
                        </div>
                        
                    </div>
                </div>
            </div>";
            $i++;
        }
    } else {
        echo "<p>No players found.</p>";
    }


?>