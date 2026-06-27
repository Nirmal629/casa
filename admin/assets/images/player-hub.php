
<!-----player-dashboard------->

<style>
    .tournamentCard {
        display: flex;
        background: #1f2937;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 15px;
        border: 1px solid #334155;
    }

    .tournamentCard .banner {
        width: 140px;
        flex-shrink: 0;
    }

    .tournamentCard .banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .tournamentCard .card-body {
        flex: 1;
        padding: 14px;
    }

    @media(max-width: 768px) {
        .tournamentCard {
            flex-direction: column;
        }

        .tournamentCard .banner {
            width: 100%;
            height: 140px;
        }
    }
</style>

<section class="playerhub_sec bothSide_gap">
    <div class="cust_container">
        <div class="wraper">
            <div class="header_box">
                <span><i class="fa-solid fa-house-chimney-user"></i> PLAYER HUB | Challenger IV</span>
                <span>PLAYER HUB Welcome back, Player !</span>
            </div>

            <div class="main-grid">

                <!-- LEFT -->
                <div class="panel">
                    <h3>The Casa Dossier</h3>
                    <div class="dossierCard">
                        <!-- Actions -->
                        <div class="actions">
                            <button>Show Review</button>
                            <button class="outline">Request Review</button>
                        </div>

                        <div class="profileDetails">
                            <!-- Profile -->
                            <div class="profile">
                                <img src="https://via.placeholder.com/90" alt="">
                            </div>

                            <!-- Info -->
                            <div class="info">
                                <p><b>Name:</b> John Doe</p>
                                <p><b>Gender:</b> Male</p>
                                <p><b>Level:</b> Advanced</p>
                                <p><b>Rating:</b> ⭐ 4.5</p>
                                <p><b>Top Partner:</b> Alex</p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="stats">
                            <div><span>120</span>Total Sessions</div>
                            <div><span>340</span>Total Games</div>
                            <div><span>5</span>No-Show</div>
                            <div><span>210</span>Recorded</div>
                        </div>

                        <!-- Win Ratio -->
                        <div class="win">
                            Win Ratio <b>62%</b>
                            <span class="up">▲</span>
                            <small>Based on recorded games</small>
                        </div>

                        <!-- Tournaments -->
                        <div class="block">
                            <h4>Tournaments</h4>
                            <p>Casa Open • Summer Smash</p>
                        </div>

                        <!-- Badges -->
                        <div class="block">
                            <h4>Skill Badges</h4>
                            <div class="badges">
                                <span class="badge smash">Smash Master</span>
                                <span class="badge net">Net Ninja</span>
                                <span class="badge def">Iron Defense</span>
                            </div>
                        </div>

                        <!-- Refer -->
                        <button class="refer">Refer Good Player</button>
                    </div>
                </div>

                <!-- CENTER -->
                <div class="panel center-panel">
                    <h3>All Clubs</h3>
                    <div class="scrollbox">

                        <div class="card">
                            <h4>Casa Club</h4>
                            <div class="content">
                                <div class="">
                                    <p class="label">Casa Badminton Club</p>
                                    <div class="info-grid">
                                        <div class="info"><span>Costing:</span> $20,</div>
                                        <div class="info"><span>Time:</span> 9:00 AM to 5:00 PM,</div>
                                    </div>
                                </div>
                                <div class="button_box">
                                    <a href="#" class="joinbtn">Request to join</a>
                                    <a href="player-dashboard.php" class="joinbtn">View Game</a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <h4>The Casamigos <span class="tag">New</span></h4>
                            <div class="content">
                                <div class="">
                                    <p class="label">Club joined tonight</p>
                                    <div class="info-grid">
                                        <div class="info"><span>Costing:</span> $20,</div>
                                        <div class="info"><span>Time:</span> 9:00 AM to 5:00 PM,</div>
                                    </div>
                                </div>
                                <div class="button_box">
                                    <a href="#" class="joinbtn">Request to join</a>
                                    <a href="player-dashboard.php" class="joinbtn">View Game</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3>Tournaments</h3>

                    <div class="scrollbox">

                        <?php
                        if (!empty($upcomingTournaments)) {
                            foreach ($upcomingTournaments as $row) {

                                // Date & Time
                                $date = date("d M Y", strtotime($row['EVENT_DATE']));
                                $time = date("h:i A", strtotime($row['EVENT_TIME']));

                                // Banner Image
                                $imgPath = !empty($row['IMGAE'])
                                    ? "admin/assets/images/tournaments_banner/" . $row['IMGAE']
                                    : "assets/images/default.jpg";

                                // Status
                                $statusClass = ($row['EVENT_CATEGORY'] == 'Open') ? 'tag open' : 'tag close';

                                // Quote
                                $words = explode(' ', strip_tags($row['EVENT_DESCRIPTION']));
                                $quote = implode(' ', array_slice($words, 0, 8)) . (count($words) > 8 ? '...' : '');
                        ?>

                                <div class="tournamentCard">

                                    <!-- LEFT: Banner -->
                                    <div class="banner">
                                        <img src="<?php echo $imgPath; ?>" alt="Tournament Banner">
                                    </div>

                                    <!-- RIGHT: Content -->
                                    <div class="card-body">

                                        <div class="card-header">
                                            <div class="headwrap">
                                                <div class="title">
                                                    <?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>
                                                </div>
                                                <span class="<?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($row['EVENT_CATEGORY']); ?>
                                                </span>
                                            </div>

                                            <div class="subtitle">
                                                <?php echo $row['GENDER_CATEGORY']; ?> (<?php echo $row['EVENT_TYPE']; ?>)
                                            </div>
                                        </div>

                                        <div class="quote">
                                            "<?php echo htmlspecialchars($quote); ?>"
                                        </div>

                                        <div class="content">
                                            <div class="info-grid">
                                                <div class="info"><span>Date:</span> <?php echo $date; ?></div>
                                                <div class="info"><span>Time:</span> <?php echo $time; ?></div>
                                                <div class="info"><span>Venue:</span> <?php echo htmlspecialchars($row['EVENT_VENUE']); ?></div>
                                                <div class="info">
                                                    <span>Teams Joined:</span>
                                                    <?php echo $row['joined_count']; ?> / <?php echo $row['MAX_TEAMS']; ?>
                                                </div>
                                            </div>

                                            <div class="actions">
                                                <a href="tournament-details.php?id=<?php echo $row['ID']; ?>" class="joinbtn">Join Now</a>
                                                <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                                            </div>
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


                </div>

                <!-- RIGHT -->
                <div class="panel">
                    <h3>Your Gear</h3>
                    <div class="scrollbox">
                        <div class="casaStore">
                            <!-- Item 1 -->
                            <div class="store-item">
                                <img src="assets/images/t-shirt.jpg" alt="image">
                                <div class="item-info">
                                    <h4>Casa T-Shirt</h4>
                                    <p class="price">CAD 10</p>
                                    <p class="order">Last order: <b>2 pcs – CAD 20</b></p>
                                    <span class="status delivered">Delivered</span>
                                </div>
                            </div>

                            <!-- Item 2 -->
                            <div class="store-item">
                                <img src="assets/images/t-shirt.jpg" alt="image">
                                <div class="item-info">
                                    <h4>EG1130</h4>
                                    <p class="price">CAD 50</p>
                                    <p class="order">Last order: <b>1 pc – CAD 50</b></p>
                                    <span class="status pending">Pending</span>
                                </div>
                            </div>

                            <!-- Item 3 (No last order) -->
                            <div class="store-item">
                                <img src="assets/images/t-shirt.jpg" alt="image">
                                <div class="item-info">
                                    <h4>Mavis 350 Blue</h4>
                                    <p class="price">CAD 19</p>
                                    <p class="no-order">
                                        Explore reliable and best rate products
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="https://casainfotech.com/product-listing.php" target="_blank" class="casastore_btn mt-4">Casa Store</a>
                </div>

            </div>

            <div class="review">
                <h2>✨ WE VALUE YOUR VOICE ✨</h2>
                <button>Please Write a Review</button>
            </div>
        </div>
    </div>
</section>


