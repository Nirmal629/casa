
<!-----player-dashboard------->
<section class="playerhub_sec ">
    <div class="">
        <div class="wraper">
            <div class="header_box">
                <span><i class="fa-solid fa-house-chimney-user"></i> PLAYER HUB | Challenger IV</span>
                <span>Welcome back, Player! Your gear is being prepared.</span>
            </div>

            <div class="main-grid">

                <!-- LEFT -->
                <div class="panel">
                    <h3>Player Stats</h3>
                    <div class="stat-grid mb-3">
                        <div class="stat">
                            <div class="circular-progress">
                                <svg>
                                    <circle class="circle-bg" cx="65" cy="65" r="45"></circle>
                                    <circle class="circle-progress" cx="65" cy="65" r="45"></circle>
                                </svg>
                                <div class="progress-text">350</div>
                            </div>
                            <div class="label">XP</div>
                        </div>
                        <div class="stat">
                            <div class="circular-progress">
                                <svg>
                                    <circle class="circle-bg" cx="65" cy="65" r="45"></circle>
                                    <circle class="circle-progress" cx="65" cy="65" r="45"></circle>
                                </svg>
                                <div class="progress-text">85%</div>
                            </div>
                            <div class="label">Win Rate</div>
                        </div>
                    </div>

                    <div class="toggle">
                        Matches Played
                        <span>350</span>
                    </div>
                    <div class="toggle">
                        Win Rate
                        <span>62%</span>
                    </div>

                    <h3 style="margin-top:25px">Preferences</h3>
                    <!-- <div class="toggle">
                        Dark Mode
                        <div class="switch active"></div>
                    </div> -->
                    <div class="toggle">
                        Preferred Role
                        <span>Healer</span>
                    </div>
                    <div class="toggle">
                        Preferred Role
                        <span>Healer</span>
                    </div>
                </div>

                <!-- CENTER -->
                <div class="panel center-panel">
                    <h3>All Clubs</h3>
                    <div class="scrollbox">
                        <div class="card">
                            <div class="content">
                                <h4>Casa Club</h4>
                                <p class="label">Casa Badminton Club</p>
                            </div>
                            <div class="button_box">
                                <a href="player-dashboard.php" class="joinbtn">View Game</a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="content">
                                <h4>The Casamigos <span class="tag">New</span></h4>
                                <p class="label">Club joined tonight</p>
                            </div>
                            <div class="button_box">
                                <a href="player-dashboard.php" class="joinbtn">View Game</a>
                            </div>
                        </div>
                    </div>
                    <h3>Tournaments</h3>
                    <!-- <div class="scrollbox">
                        <div class="card">
                            <div class="content">
                                <h4>Winter Cup 2025</h4>
                                <p class="label">Upcoming Tournament</p>
                            </div>
                            <div class="button_box">
                                <a href="tournament-details.php" class="joinbtn">Join Now</a>
                                <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="content">
                                <h4>Winter Cup 2026</h4>
                                <p class="label">Upcoming Tournament</p>
                            </div>
                            <div class="button_box">
                                <a href="tournament-details.php" class="joinbtn">Join Now</a>
                                <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="content">
                                <h4>Winter Cup 2026</h4>
                                <p class="label">Upcoming Tournament</p>
                            </div>
                            <div class="button_box">
                                <a href="tournament-details.php" class="joinbtn">Join Now</a>
                                <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="content">
                                <h4>Winter Cup 2026</h4>
                                <p class="label">Upcoming Tournament</p>
                            </div>
                            <div class="button_box">
                                <a href="tournament-details.php" class="joinbtn">Join Now</a>
                                <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                            </div>
                        </div>
                    </div> -->
                    <div class="scrollbox" style="max-height: 600px; overflow-y: auto;">
    <?php
    if (!empty($upcomingTournaments)) {
        foreach ($upcomingTournaments as $row) {
            // Formatting logic
            $date = date("M d, Y", strtotime($row['EVENT_DATE']));
            $time = date("h:i A", strtotime($row['EVENT_TIME']));
            $imgPath = !empty($row['IMGAE']) ? "admin/assets/images/event_banner/".$row['IMGAE'] : "assets/images/default.jpg";
            
            // Tagline (5 words)
            $words = explode(' ', strip_tags($row['EVENT_DESCRIPTION']));
            $tagline = implode(' ', array_slice($words, 0, 5)) . (count($words) > 5 ? '...' : '');
    ?>
            <!-- Compact Horizontal Card -->
            <div class="card" style="display: flex; flex-direction: row; align-items: center; gap: 15px; padding: 12px; margin-bottom: 12px; background: #2a3547; border: 1px solid #3d4b61; border-radius: 8px;">
                
                <!-- 1. Left Column: Banner Thumbnail -->
                <div style="flex-shrink: 0;">
                    <img src="<?php echo $imgPath; ?>" style="width: 100px; height: 70px; object-fit: cover; border-radius: 6px;" alt="Banner">
                </div>

                <!-- 2. Middle Column: All Text Info (Flex Grow takes available space) -->
                <div class="content" style="flex-grow: 1; padding: 0; display: flex; flex-direction: column; gap: 2px; overflow: hidden;">
                    <!-- Cup Name -->
                    <h4 style="margin: 0; font-size: 16px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>
                    </h4>
                    
                    <!-- Tagline -->
                    <p class="label" style="font-style: italic; color: #007bff; margin: 0; font-size: 12px;">
                        <?php echo htmlspecialchars($tagline); ?>
                    </p>

                    <!-- Category + Event Info -->
                    <div style="font-size: 11px; color: #ccc;">
                        <i class="fa fa-users"></i> <?php echo $row['GENDER_CATEGORY']; ?> (<?php echo $row['EVENT_TYPE']; ?>) || <?php echo $row['EVENT_CATEGORY']; ?>
                    </div>

                    <!-- Date || Time (Faded and Italic) -->
                    <div style="font-size: 11px; color: #777; font-style: italic;">
                        <i class="fa fa-calendar-day"></i> <?php echo $date; ?> || <i class="fa fa-clock"></i> <?php echo $time; ?>
                    </div>

                    <!-- Location -->
                    <div style="font-size: 11px; color: #bbb;">
                        <i class="fa fa-location-dot"></i> <?php echo htmlspecialchars($row['EVENT_VENUE']); ?>
                    </div>
                </div>

                <!-- 3. Right Column: Cost, Joined Count & Buttons -->
                <div style="flex-shrink: 0; text-align: right; border-left: 1px solid #3d4b61; padding-left: 15px; min-width: 130px;">
                    <!-- Cost -->
                    <p style="margin: 0; font-weight: 700; color: #fff; font-size: 15px;">
                        $<?php echo number_format($row['EVENT_COST'], 2); ?>
                    </p>
                    
                    <!-- Joined Count -->
                    <p style="margin: 0 0 8px 0; color: #28a745; font-size: 11px; font-weight: bold;">
                        <i class="fa fa-check-circle"></i> <?php echo $row['joined_count']; ?> Joined
                    </p>

                    <!-- Buttons -->
                    <div class="button_box" >
                        <a href="tournament-details.php?id=<?php echo $row['ID']; ?>" class="joinbtn" >Join Now</a>
                        <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                    </div>
                </div>
            </div>
    <?php
        }
    } else {
        echo "<p class='label'>No upcoming tournaments found.</p>";
    }
    ?>
</div>

                    <!-- <h3>Achievements</h3>
                    <div class="achievement_box">
                        <i class="fa-solid fa-trophy"></i>
                        <span class="text">Uoined a Club Tournament Rookie</span>
                        <i class="fa-solid fa-trophy"></i>
                    </div> -->
                    <!-- <div class="badges">
                        <div class="badge">Joined a Club</div>
                        <div class="badge">Tournament Rookie</div>
                    </div> -->
                </div>

                <!-- RIGHT -->
                <div class="panel">
                    <h3>Your Gear</h3>
                    <img src="assets/images/t-shirt.jpg" class="gear-img">
                    <p class="label">Item: "Origin" T-Shirt</p>
                    <p class="label">Size: L</p>
                    <p class="label">Status: In Transit</p>

                    <a href="https://casainfotech.com/staging/product-listing.php" target="_blank" class="casastore_btn mt-5">Casa Store</a>
                </div>

            </div>

            <div class="review">
                <h2>✨ WE VALUE YOUR VOICE ✨</h2>
                <button>Please Write a Review</button>
            </div>
        </div>
    </div>
</section>


<script>
    document.querySelectorAll('.switch').forEach(sw => {
        sw.addEventListener('click', () => sw.classList.toggle('active'));
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        document.querySelectorAll(".circular-progress").forEach(progress => {

            const value = parseInt(progress.dataset.value);
            const max = parseInt(progress.dataset.max);
            const circle = progress.querySelector(".circle-progress");
            const text = progress.querySelector(".progress-text");

            const radius = 45;
            const circumference = 2 * Math.PI * radius;

            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = circumference;

            let current = 0;
            const targetPercent = Math.round((value / max) * 500);

            function animate() {
                if (current <= targetPercent) {
                    const offset = circumference - (current / 100) * circumference;
                    circle.style.strokeDashoffset = offset;

                    // Display value
                    if (max === 100) {
                        text.innerText = current + "%";
                    } else {
                        text.innerText = Math.round((current / 100) * max);
                    }

                    current++;
                    requestAnimationFrame(animate);
                }
            }

            animate();
        });

    });
</script>