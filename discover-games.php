<div class="discoverGames_sec bottomSide_gap">
    <div class="discoverGames_slider" data-aos="fade-up" data-aos-duration="2000">
        <?php
        include('dbConnection.php');
        $select_games = mysqli_query($conn,"select * from ca_events where EVENT_DATE > NOW() AND STATUS='Active' order by ID DESC");
        $count_games = mysqli_num_rows($select_games);
        if($count_games>0)
        {
            while($fetch_games = mysqli_fetch_assoc($select_games))
            {
                $event_id = $fetch_games['ID'];
        $host_name = $fetch_games['HOST_NAME'];
        $event_date = date('D, d M Y, h:i A', strtotime($fetch_games['EVENT_DATE'] . ' ' . $fetch_games['EVENT_TIME']));
        $event_venue = $fetch_games['EVENT_VENUE'];
        $event_cost = $fetch_games['EVENT_COST'];
        $event_currency = $fetch_games['EVENT_CURRENCY'];
        $event_category = $fetch_games['EVENT_CATEGORY'];
        $gender_category = $fetch_games['GENDER_CATEGORY'];
        $gender_skill_level = $fetch_games['GENDER_SKILL_LEVEL'];
        $event_description = html_entity_decode($fetch_games['EVENT_DESCRIPTION'], ENT_QUOTES, 'UTF-8');
        $event_message = html_entity_decode($fetch_games['EVENT_MESSAGE'], ENT_QUOTES, 'UTF-8');
        $event_type = $fetch_games['EVENT_TYPE'];
        
        $jsonStringy = json_encode($event, JSON_HEX_APOS | JSON_HEX_QUOT);
        
        $words = explode(" ", $event_message); // Split message into words
        $wrapped_message = implode(" ", array_slice($words, 0, 7)); // Get first 5 words
        
        if (count($words) > 7) {
            $wrapped_message .= " ..."; // Append "..." if more words exist
        }

        
        // You can also fetch the image and other dynamic content here.
        $profile_pic = "assets/images/profile.jpg"; // Example for profile image, use dynamic based on host data
        ?>
        
            <div class="discoverGames_card">                     
                <div class='d-flex align-items-center justify-content-between'>
                    <p class='desc'><?= $event_category . ' - ' . $gender_category ?></p>
                </div>
            
                <h4 class='date_time'>
                    <?= $event_date . ' - ' . date('h:i A', strtotime($event['TO_TIME'])) ?>
                </h4>
            
                <p class='location'>
                    <i class='fa-solid fa-location-dot'></i>
                    <?= $event_venue ?>
                </p>
            
                <div class='d-flex align-items-center' style='gap: 15px; margin-bottom: 10px;'>
                    <div class='d-flex align-items-center'>
                        <p><?= $wrapped_message ?></p>
                    </div>
                    <div class='Slots_book' style='visibility:hidden'>Only 2 Slots</div>
                </div>
            
                <div class='d-flex align-items-center justify-content-between'>
                    <div class='play_status'>
                        <img src='assets/images/Icons/SP5.png' class='img-fluid' alt='Game Icon' />
                        <span><?= $gender_skill_level ?></span>
                    </div>
            
                    <div class='d-flex align-items-center justify-content-between' style='gap: 10px;'>
                        <span class='badge <?= ($event_type == 'Public' ? 'bg-primary' : 'bg-success') ?>'>
                            <?= $event_type ?>
                        </span>
            
                        <span class='amount'>
                            <i class='fa-solid fa-dollar-sign mr-1'></i>
                            <?= $event_currency . ' ' . $event_cost ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php
            }
        }
        else
        {
            ?>
            <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            <?php
            
        }
        ?>
        <!--<div class="discoverGames_card">-->
        <!--    <a href="#">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">tournament</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book"><b>2</b>/5 Going</div>-->
        <!--        </div>-->
                <!-- <p class="desc">Yash | 3283 Karma</p> -->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="play_status">-->
        <!--            <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--            <span>Beginner - Professional</span>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="#">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">Singles regular</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book"><b>2</b>/4 Going</div>-->
        <!--        </div>-->
                <!-- <p class="desc">Yash | 3283 Karma</p> -->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="play_status">-->
        <!--            <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--            <span>Beginner - Professional</span>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="#">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">Singles regular</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign mr-1"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book"><b>12</b>/16 Going</div>-->
        <!--        </div>-->
                <!-- <p class="desc">Yash | 3283 Karma</p> -->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="play_status">-->
        <!--            <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--            <span>Beginner - Professional</span>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
        <!--<div class="discoverGames_card">-->
        <!--    <a href="#">-->
        <!--        <div class="d-flex align-items-center justify-content-between">-->
        <!--            <p class="desc">tournament</p>-->
        <!--            <span class="amount"><i class="fa-solid fa-dollar-sign"></i>INR 90</span>-->
        <!--        </div>-->
        <!--        <div class="d-flex align-items-center" style="gap: 15px; margin-bottom: 10px;">-->
        <!--            <div class="d-flex align-items-center">-->
        <!--                <div class="profile_pic"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--                <div class="profile_pic2"><img src="assets/images/profile.jpg" alt="profile" /></div>-->
        <!--            </div>-->
        <!--            <div class="Slots_book">Only 2 Slots</div>-->
        <!--        </div>-->
                <!-- <p class="desc">Yash | 3283 Karma</p> -->
        <!--        <h4 class="date_time">Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</h4>-->
        <!--        <p class="location">-->
        <!--            <i class="fa-solid fa-location-dot"></i>-->
        <!--            Casa Badminton Club-->
        <!--        </p>-->
        <!--        <div class="play_status">-->
        <!--            <img src="assets/images/Icons/SP5.png" class="img-fluid" alt="Badminton" />-->
        <!--            <span>Beginner - Professional</span>-->
        <!--        </div>-->
        <!--    </a>-->
        <!--</div>-->
    </div>
</div>