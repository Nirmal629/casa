<?php
session_start();
include('dbConnection.php');

if (isset($_SESSION['user_id'])) {
    logPlayerActivity($conn, $_SESSION['user_id'], 'VIEW_PLAYERS', 'Viewed invited players for game ID ' . ($_POST['ID'] ?? 'unknown'));
}
// print_r($_POST);

if($_POST['EVENT_TYPE'] == 'Public')
{
//     $select_Player = mysqli_query($conn,"select * from ca_users where USERTYPE='Player' and LOG_STATUS='Y' and DEL_STATUS='N' and GENDER='".$_SESSION['gender']."'");
//     if (mysqli_num_rows($select_Player) > 0) {
//         while ($player = mysqli_fetch_assoc($select_Player)) {
//             $check_joined = mysqli_query($conn, "SELECT * FROM ca_gamejoin WHERE USER_ID='" . $player['ID'] . "' AND GAME_ID='" . $_POST['ID'] . "'");
//             $is_joined = mysqli_num_rows($check_joined) > 0; // True if the player is already joined
//             $fetch_Joined = mysqli_fetch_assoc($check_joined);
            
//             $select_Event = mysqli_query($conn,"select * from ca_events where ID='".$_POST['ID']."'");
//             $fetch_event = mysqli_fetch_assoc($select_Event);
    
//             // Determine if the checkbox should be checked and disabled
//             $checked = $is_joined ? 'checked' : '';
//             $disabled = $is_joined ? 'disabled' : '';
//             // Populate player details dynamically
//             echo "
//             <div class='Profiletable_wrap'>
//                 <div class='plyardetails'>
//                     <div style='display: flex; flex-direction: column; align-items: flex-start; gap: 5px;'>
// <h6 class='name'><strong>" . $player['NAME'] . " (" . $player['GENDER'] . ")</strong></h6>
// <p>{$player['WHATSAPP_NUMBER']}</p>
//                     </div>
//                     <div class='invite_btn'>
//                         <span>Add Player</span>
//                         <input type='checkbox' class='invite-checkbox' data-type='Public' data-user-id='".$player['ID']."' data-game-id='".$_POST['ID']."' data-host-id='".$_POST['HOST_ID']."' data-price-id='".$fetch_event['EVENT_COST']."' data-currency-id='".$fetch_event['EVENT_CURRENCY']."' $checked />
//                     </div>
//                 </div>
//             </div>";
//         }
//     } else {
//         echo "<p>No players found.</p>";
//     }

$select_game = mysqli_query($conn,"select * from ca_events where ID='" . $_POST['ID'] . "'");
$fetch_game = mysqli_fetch_assoc($select_game);
$skill_level = $fetch_game['GENDER_SKILL_LEVEL'];

$gender_condition = "";
if ($fetch_game['GENDER_CATEGORY'] == 'Mix') {
    $gender_condition .= "AND cu.GENDER IN ('Male', 'Female', 'Kid') ";
} else {
    $gender_condition .= "AND cu.GENDER = '" . $fetch_game['GENDER_CATEGORY'] . "' ";
}

if ($skill_level !== 'Mix') {
    $gender_condition .= "AND cu.VERIFIED_LEVEL = '$skill_level' ";
}
   $select_Player = mysqli_query($conn, "
    SELECT cu.*,
           cg.CONFIRMED AS CONFIRMED,
           cg.PRICE AS JOINED_PRICE, 
           IF(cg.USER_ID IS NOT NULL, 1, 0) AS IS_JOINED,
           IFNULL(gj.total_games, 0) AS TOTAL_GAMES
    FROM ca_users cu
    INNER JOIN ca_player_club_status pcs ON cu.ID = pcs.player_id AND pcs.host_id = '" . mysqli_real_escape_string($conn, $_POST['HOST_ID']) . "' AND pcs.status = 'accepted'
    LEFT JOIN ca_gamejoin cg ON cu.ID = cg.USER_ID AND cg.GAME_ID = '" . mysqli_real_escape_string($conn, $_POST['ID']) . "'
    LEFT JOIN (
        SELECT USER_ID, COUNT(*) AS total_games
        FROM ca_gamejoin
        GROUP BY USER_ID
    ) gj ON cu.ID = gj.USER_ID
    WHERE cu.USERTYPE='Player' 
      AND cu.LOG_STATUS='Y' 
      AND cu.DEL_STATUS='N' 
      $gender_condition
    ORDER BY IS_JOINED DESC,TOTAL_GAMES DESC
    ");
    
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
        echo "<input type='hidden' class='invite-checkbox' data-game-id='".$_POST['ID']."' data-host-id='".$_POST['HOST_ID']."' /><p>No players found.</p>";
    }

    
}
else
{
    $select_Player = mysqli_query($conn,"
        SELECT cu.* 
        FROM ca_users cu
        INNER JOIN ca_player_club_status pcs ON cu.ID = pcs.player_id 
            AND pcs.host_id = '" . mysqli_real_escape_string($conn, $_POST['HOST_ID']) . "' 
            AND pcs.status = 'accepted'
        WHERE cu.USERTYPE='Player' 
          AND cu.LOG_STATUS='Y' 
          AND cu.DEL_STATUS='N' 
          AND cu.GENDER='".mysqli_real_escape_string($conn, $_SESSION['gender'])."'
    ");
    if (mysqli_num_rows($select_Player) > 0) {
        $i = 1;
        echo "<div class='table-responsive'><table class='table table-sm table-bordered mt-2 text-center align-middle text-nowrap' style='font-size: 0.85rem;'>";
        echo "<thead class='table-light'><tr><th style='width: 30px;'>#</th><th class='text-start'>Name</th><th style='width: 30px;' title='Gender'>G</th><th style='width: 40px;' title='Level'>Lvl</th><th style='width: 35px;' title='Invite Player'><i class='fa-solid fa-user-plus'></i></th><th style='width: 35px;' title='Confirm Player'><i class='fa-solid fa-check-double'></i></th></tr></thead>";
        echo "<tbody>";
        while ($player = mysqli_fetch_assoc($select_Player)) {
            $check_joined = mysqli_query($conn, "SELECT * FROM ca_gamejoin WHERE USER_ID='" . $player['ID'] . "' AND GAME_ID='" . $_POST['ID'] . "'");
            $is_joined = mysqli_num_rows($check_joined) > 0; // True if the player is already joined
            $fetch_Joined = mysqli_fetch_assoc($check_joined);
            
            $select_Event = mysqli_query($conn,"select * from ca_events where ID='".$_POST['ID']."'");
            $fetch_event = mysqli_fetch_assoc($select_Event);
    
            // Determine if the checkbox should be checked and disabled
            $checked = $is_joined ? 'checked' : '';
            $disabled = $is_joined ? 'disabled' : '';
            $checked_con = isset($fetch_Joined['CONFIRMED']) && $fetch_Joined['CONFIRMED'] == 'Y' ? 'checked' : '';
            $disabled_con = isset($fetch_Joined['CONFIRMED']) && $fetch_Joined['CONFIRMED'] == 'Y' ? 'disabled' : '';

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

            // Populate player details dynamically
            echo "
            <tr>
                <td>$i</td>
                <td class='text-start fw-bold'>$display_name</td>
                <td>$display_gender</td>
                <td><span class='badge bg-info'>$display_level</span></td>
                <td>
                    <label class='m-0 p-0 d-flex align-items-center justify-content-center' style='cursor: pointer;' title='Invite Player'>
                        <input type='checkbox' class='invite-checkbox d-none' data-type='Invite' 
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
                        <input type='checkbox' class='invite-checkboxx d-none' data-type='Invite' 
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
        echo "<input type='hidden' class='invite-checkbox' data-game-id='".$_POST['ID']."' data-host-id='".$_POST['HOST_ID']."' /><p>No players found.</p>";
    }
}

?>