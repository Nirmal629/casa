<?php
session_start();
include('dbConnection.php');
   
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
        while ($player = mysqli_fetch_assoc($select_Player)) {
            $is_joined = $player['IS_JOINED'] == 1;
            if($is_joined)
            {
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
                    </div>
                    " : "") . "
                    <div>
                        <div class='invite_btn'>
                            <span>Add Player</span>
                            <input type='checkbox' class='invite-checkbox' data-type='Public' 
                                disabled
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
                                disabled
                                data-user-id='".$player['ID']."' 
                                data-game-id='".$_POST['ID']."' 
                                data-host-id='".$_POST['HOST_ID']."' 
                                data-price-id='".$fetch_event['EVENT_COST']."' 
                                data-currency-id='".$fetch_event['EVENT_CURRENCY']."' 
                                $checked_con $disabled/>
                        </div>
                        
                    </div>
                </div>
            </div>";
            $i++;
            }
        }
    } else {
        echo "<p>No players found.</p>";
    }


?>