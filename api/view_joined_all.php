<?php
session_start();
include('dbConnection.php');

if (isset($_SESSION['user_id'])) {
    logPlayerActivity($conn, $_SESSION['user_id'], 'VIEW_PLAYERS', 'Viewed players for game ID ' . ($_POST['ID'] ?? 'unknown'));
}

   $select_Player = mysqli_query($conn, "
    SELECT cu.*,
           cg.CONFIRMED AS CONFIRMED,
           cg.PRICE AS JOINED_PRICE, 
           IF(cg.USER_ID IS NOT NULL, 1, 0) AS IS_JOINED 
    FROM ca_users cu
    LEFT JOIN ca_gamejoin cg ON cu.ID = cg.USER_ID AND cg.GAME_ID = '" . $_POST['ID'] . "'
    WHERE cu.USERTYPE='Player' 
      AND cu.LOG_STATUS='Y' 
      AND cu.DEL_STATUS='N' 
    ORDER BY IS_JOINED DESC
    ");
    
    if (mysqli_num_rows($select_Player) > 0) {
        // Fetch event details once
        if (!isset($fetch_event)) {
            $select_Event = mysqli_query($conn, "SELECT * FROM ca_events WHERE ID='" . $_POST['ID'] . "'");
            $fetch_event = mysqli_fetch_assoc($select_Event);
        }
        $i = 1;
        echo "<div class='table-responsive'><table class='table table-sm table-bordered mt-2 text-center align-middle text-nowrap' style='font-size: 0.85rem;'>";
        echo "<thead class='table-light'><tr><th style='width: 30px;'>#</th><th class='text-start'>Name</th><th style='width: 30px;' title='Gender'>G</th><th style='width: 40px;' title='Level'>Lvl</th></tr></thead>";
        echo "<tbody>";
        while ($player = mysqli_fetch_assoc($select_Player)) {
            $is_joined = $player['IS_JOINED'] == 1;
            if($is_joined)
            {
            $price = $is_joined ? $player['JOINED_PRICE'] : $fetch_event['EVENT_COST']; // Get price from ca_gamejoin if joined
    
            // Determine checkbox attributes
            $checked = $is_joined ? 'checked' : '';
            $disabled = $is_joined ? '' : 'disabled';
            
            $checked_con = $player['CONFIRMED'] == 'Y' ? 'checked' : '';
            $disabled_con = ''; // Allow host to unconfirm players
    
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
            </tr>";
            $i++;
            }
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p>No players found.</p>";
    }


?>