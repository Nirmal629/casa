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
    $gender_condition .= "AND cu.GENDER IN ('Male', 'Female', 'Kid') ";
} else {
    $gender_condition .= "AND cu.GENDER = '$gender' ";
}

if ($skill_level !== 'Mix') {
    $gender_condition .= "AND cu.VERIFIED_LEVEL = '$skill_level' ";
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
        echo "<div class='table-responsive'><table class='table table-sm table-bordered mt-2 text-center align-middle text-nowrap' style='font-size: 0.85rem;'>";
        echo "<thead class='table-light'><tr><th style='width: 30px;'>#</th><th class='text-start'>Name</th><th style='width: 30px;' title='Gender'>G</th><th style='width: 40px;' title='Level'>Lvl</th><th style='width: 35px;' title='Add/Invite Player'><i class='fa-solid fa-user-plus'></i></th><th style='width: 35px;' title='Confirm Player'><i class='fa-solid fa-check-double'></i></th></tr></thead>";
        echo "<tbody>";

        while ($player = mysqli_fetch_assoc($select_Player)) {
            $is_joined = $player['IS_JOINED'] == 1;
            $price = $is_joined ? $player['JOINED_PRICE'] : $fetch_event['EVENT_COST']; // Get price from ca_gamejoin if joined
    
            // Determine checkbox attributes
            $checked = $is_joined ? 'checked' : '';
            $disabled = $is_joined ? '' : 'disabled';
            
            $checked_con = $player['CONFIRMED'] == 'Y' ? 'checked' : '';
            $disabled_con = $player['CONFIRMED'] == 'Y' ? 'disabled' : '';

            $level_map = [
                'Beginner' => 'Beg',
                'Amateur' => 'Ama',
                'Intermediate' => 'Int',
                'Intermediate +' => 'Int+',
                'Pro' => 'Pro'
            ];
            $display_level = $level_map[$player['VERIFIED_LEVEL']] ?? $player['VERIFIED_LEVEL'];

            $gender_map = [
                'Male' => 'M',
                'Female' => 'F',
                'Kid' => 'K'
            ];
            $display_gender = $gender_map[$player['GENDER']] ?? $player['GENDER'];

            $name_parts = explode(' ', trim($player['NAME']));
            $display_name = $name_parts[0];
            if (count($name_parts) > 1) {
                $display_name .= ' ' . strtoupper(substr(end($name_parts), 0, 1)) . '.';
            }

            // Display player details
            echo " 
            <tr>
                <td>$i</td>
                <td class='text-start fw-bold'>$display_name</td>
                <td>$display_gender</td>
                <td><span class='badge bg-info'>$display_level</span></td>
                <td>
                    <label class='m-0 p-0 d-flex align-items-center justify-content-center' style='cursor: pointer;' title='Add Player'>
                        <input type='checkbox' class='invite-checkbox d-none' data-type='Public' 
                            data-user-id='".$player['ID']."' 
                            data-game-id='".$_POST['ID']."' 
                            data-host-id='".$_POST['HOST_ID']."' 
                            data-price-id='".$fetch_event['EVENT_COST']."' 
                            data-currency-id='".$fetch_event['EVENT_CURRENCY']."' 
                            $checked 
                            onchange='this.nextElementSibling.className = this.checked ? \"fa-solid fa-user-check fs-5 text-success\" : \"fa-solid fa-user-plus fs-5 text-secondary\";' />
                        <i class='" . ($checked ? "fa-solid fa-user-check fs-5 text-success" : "fa-solid fa-user-plus fs-5 text-secondary") . "' style='transition: 0.2s;'></i>
                    </label>
                </td>
                <td>
                    <label class='m-0 p-0 d-flex align-items-center justify-content-center' style='" . ($disabled_con ? "cursor: not-allowed; opacity: 0.5;" : "cursor: pointer;") . "' title='Confirm Player'>
                        <input type='checkbox' class='invite-checkboxx d-none' data-type='Public' 
                            data-user-id='".$player['ID']."' 
                            data-game-id='".$_POST['ID']."' 
                            data-host-id='".$_POST['HOST_ID']."' 
                            data-price-id='".$fetch_event['EVENT_COST']."' 
                            data-currency-id='".$fetch_event['EVENT_CURRENCY']."' 
                            $checked_con $disabled_con 
                            onchange='this.nextElementSibling.className = this.checked ? \"fa-solid fa-check-double fs-5 text-primary\" : \"fa-solid fa-check fs-5 text-secondary\";' />
                        <i class='" . ($checked_con ? "fa-solid fa-check-double fs-5 text-primary" : "fa-solid fa-check fs-5 text-secondary") . "' style='transition: 0.2s;'></i>
                    </label>
                </td>
            </tr>";
            $i++;
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p>No players found.</p>";
    }


?>