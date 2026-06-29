<?php
include('dbConnection.php');
// print_r($_POST);


    $select_joined = mysqli_query($conn,"SELECT * FROM `ca_gamejoin` where GAME_ID='".$_POST['ID']."'");
    if (mysqli_num_rows($select_joined) > 0) {
            $i = 1;
            while ($joinedPlayer = mysqli_fetch_assoc($select_joined)) {
            $select_Player = mysqli_query($conn,"select * from ca_users where ID='".$joinedPlayer['USER_ID']."'");
            $fetch_Player = mysqli_fetch_assoc($select_Player);
                // Populate player details dynamically
                $created = date('D, d M Y, h:i A', strtotime($joinedPlayer['CREATED_AT']));
                
                 $confirmStatus = $joinedPlayer['CONFIRMED'] == 'Y' 
            ? "<strong style='color: white; background-color: green; padding: 2px 5px; border-radius: 4px; white-space: nowrap; font-size: 85%;'>Confirmed</strong>" 
            : "<strong style='color: white; background-color: rosybrown; padding: 2px 5px; border-radius: 4px; white-space: nowrap; font-size: 85%;'>Not Confirmed</strong>";
            
                $profile_pic = $fetch_Player['PROFILE_IMAGE']!=''?'../profile_img/'.$fetch_Player['PROFILE_IMAGE']:'../assets/images/profile.jpg';


                echo "
                <div class='Profiletable_wrap'>
                <strong>$i</strong>
                    <div class='hostProfile_small'>
                        <img src='$profile_pic' class='img-fluid' alt='Profile Picture'>
                    </div>
                    <div class='plyardetails'>
                        <h6 class='name mb-0'><strong>".$fetch_Player['NAME']."</strong> (".$fetch_Player['VERIFIED_LEVEL'].")</h6>
                        <div class='invite_btn' style='flex-wrap: wrap; width: 55%;'>
                            <span>Joined On:</span>
                            <strong style='text-align: end;'>$created</strong>
                            $confirmStatus
                        </div>
                    </div>
                </div>";
                $i++;
            }
        
    }
    else
    {
        echo "<p>No players found.</p>";
    }
   

?>