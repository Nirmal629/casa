<?php

ob_start();
session_start();
error_reporting(1);
// print_r($_SESSION);
// exit;

if (isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: index.php"); // Default fallback
    }
    exit();
}
?>


<!-----Header------>
<?php include "includes/header.php"; ?>


<!-----new banner-------->
<section class="homebanner_sec bottomSide_gap">
    <div class="banner_image herobanner_slider">
        <div class="item">
            <div class="item_img">
                <img src="assets/images/herobanner2.jpg" class="img-fluid" alt="banner-image" />
            </div>
            <div class="cust_container">
                <div class="row bothSide_gap">
                    <!-- Left Blank Side -->
                    <div class="col-lg-6 col-12">
                        <div class="banner_content">
                            <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                <h6 class="Homebanner sub_heading">Uniting Passion for Sports:</h6>
                                <h1 class="Homebanner heading">Casa Badminton <span>Games</span></h1>
                                <p class="bannerdesc desc mb-1">Connect, play, and improve. Effortless sports management hub.</p>
                                <!--<p class="bannerdesc desc">From friendly matches to competitive tournaments, Casa is the perfect platform to enjoy a strong, supportive community that shares your passion.</p>-->
                            </div>
                        </div>
                    </div>
                    <!-- Right Side Form -->
                    <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                        <?php include "includes/Auth/login.php"; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="item">
            <div class="item_img">
                <img src="assets/images/herobanner3.jpg" class="img-fluid" alt="banner-image" />
            </div>
            <div class="cust_container">
                <div class="row bothSide_gap">
                    <!-- Left Blank Side -->
                    <div class="col-lg-6 col-12">
                        <div class="banner_content">
                            <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                <h6 class="Homebanner sub_heading">Uniting Passion for Sports:</h6>
                                <h1 class="Homebanner heading">Casa Badminton <span>Training</span></h1>
                                <p class="bannerdesc desc">Pro badminton training for all ages. Flexible sessions to fit your schedule.</p>
                            </div>
                        </div>
                    </div>
                    <!-- Right Side Form -->
                    <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                        <?php include "includes/Auth/login.php"; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="item">
            <div class="item_img">
                <img src="assets/images/woman-outdoors2.jpg" class="img-fluid" alt="banner-image" />
            </div>
            <div class="cust_container">
                <div class="row bothSide_gap">
                    <!-- Left Blank Side -->
                    <div class="col-lg-6 col-12">
                        <div class="banner_content">
                            <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                <h6 class="Homebanner sub_heading">Uniting Passion for Sports:</h6>
                                <h1 class="Homebanner heading">Casa Badminton <span>Store</span></h1>
                                <p class="bannerdesc desc">Shop elite shuttlecocks and accessories at the best prices.</p>
                                <!--<a href="https://casainfotech.com/staging/tournament/" target="_blank" class="learnmore_btn btn">Read More</a>-->
                            </div>
                        </div>
                    </div>
                    <!-- Right Side Form -->
                    <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                        <?php include "includes/Auth/login.php"; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!----tournament----->
<section class="tournament_sec bothSide_gap">
    <div class="cust_container">
        <h6 class="sub_heading">Tournament</h6>
        <h2 class="heading">Upcoming Tournaments</h2>
        <div class="tournamentcard_slider">
            <?php
            include('dbConnection_PDO.php');

            try {
                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sql = "SELECT e.*, b.IMGAE,
                        (SELECT COUNT(ID) FROM to_teams WHERE TOURNAMENT_ID = e.ID) AS joined_count
                        FROM  to_tournaments e 
                        LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 
                        WHERE e.STATUS = 'Active' 
                        ORDER BY e.ID DESC";
                $stmt = $pdo->query($sql);
                $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($tournaments) > 0) {
                    foreach ($tournaments as $row) {
                        // Formatting
                        $date = date("M d, Y", strtotime($row['EVENT_DATE']));
                        $time = date("h:i A", strtotime($row['EVENT_TIME']));
                        $cost = number_format($row['EVENT_COST'], 2);

                        // Image Path Logic
                        $imgPath = !empty($row['IMGAE'])
                            ? "admin/assets/images/tournaments_banner/" . $row['IMGAE']
                            : "assets/images/default-tournament.jpg";

                        // --- LOGIC MOVED TO TOP ---
                        $isRegistrationOpen = true;
                        if (!empty($row['CANCEL_DATE'])) {
                            try {
                                $nowEst = new DateTime('now', new DateTimeZone('America/New_York'));
                                $cTime = !empty($row['CANCEL_TIME']) ? $row['CANCEL_TIME'] : '10:00:00';
                                $cancelEst = new DateTime($row['CANCEL_DATE'] . ' ' . $cTime, new DateTimeZone('America/New_York'));

                                if ($nowEst >= $cancelEst) {
                                    $isRegistrationOpen = false;
                                }
                            } catch (Exception $e) {
                                $isRegistrationOpen = true;
                            }
                        }
            ?>
                        <!-- Conditional Wrapper: <a> if open, <div> if closed -->
                        <?php if ($isRegistrationOpen): ?>
                            <a href="tournament-details.php?id=<?php echo $row['ID']; ?>" class="tournament_card">
                            <?php else: ?>
                                <div class="tournament_card" style="cursor: default;">
                                <?php endif; ?>

                                <!-- Image at the top -->
                                <div class="image">
                                    <img src="<?php echo $imgPath; ?>" class="img" alt="Banner" />
                                </div>

                                <div class="content">
                                    <!-- Cup Name -->
                                    <h4 class="name"><?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?></h4>

                                    <!-- Tagline -->
                                    <span class="tagline">
                                        <?php
                                        $plainDescription = strip_tags($row['EVENT_DESCRIPTION']);
                                        $words = explode(' ', $plainDescription);
                                        echo htmlspecialchars(implode(' ', array_slice($words, 0, 5)));
                                        if (count($words) > 5) echo '...';
                                        ?>
                                    </span>

                                    <!-- Categories Line -->
                                    <div class="meta-row category-line">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa-solid fa-user-group"></i></div>
                                            <div class="tournamentCardTxt">
                                                <span><?php echo $row['GENDER_CATEGORY']; ?> - <?php echo $row['EVENT_TYPE']; ?></span>
                                                <span>- <?php echo $row['EVENT_CATEGORY']; ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date & Time -->
                                    <div class="tournamentCardFlex" style="display: flex; align-items: center; gap: 8px; column-gap: 20px; margin-bottom: 6px;">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa fa-calendar-alt" style="color: #0056b3; width: 16px;"></i></div>
                                            <div class="tournamentCardTxt"><span><?php echo $date; ?></span></div>
                                        </div>
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa fa-clock" style="color: #0056b3; width: 16px;"></i></div>
                                            <div class="tournamentCardTxt"><span><?php echo $time; ?></span></div>
                                        </div>
                                    </div>

                                    <!-- Venue Line -->
                                    <div class="meta-row venue-line">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa fa-map-marker-alt"></i></div>
                                            <div class="tournamentCardTxt"><span><?php echo htmlspecialchars($row['EVENT_VENUE']); ?></span></div>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="price-tag tournamentCardFlex" style="display: flex; align-items: center; gap: 8px; column-gap: 20px; margin-bottom: 6px;">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa-solid fa-comment-dollar"></i></div>
                                            <div class="tournamentCardTxt">
                                                <strong> <?php echo number_format($row['EVENT_COST'], 2); ?></strong>
                                                <span style="font-size: 13px;">per player</span>
                                            </div>
                                        </div>
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa-solid fa-feather-pointed"></i></div>
                                            <div class="tournamentCardTxt"><strong>Birdie:</strong><span> Feather</span></div>
                                        </div>
                                    </div>

                                    <!-- Joined Status Badge -->
                                    <div class="joined-status">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa fa-check-circle"></i></div>
                                            <div class="tournamentCardTxt"><span><?php echo ($row['joined_count'] ?? 0); ?> teams joined</span></div>
                                        </div>
                                    </div>

                                    <!-- registration status -->
                                    <?php if ($isRegistrationOpen): ?>
                                        <div class="openBtn btn-info rounded text-white">
                                            <span>Registration open</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="openBtn rounded" style="background: #475569 !important; color: #cbd5e1 !important; cursor: not-allowed !important; opacity: 0.65; user-select: none;">
                                            <span>Registration Closed</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Conditional Closing Tag -->
                                <?php if ($isRegistrationOpen): ?>
                            </a><?php else: ?>
        </div><?php endif; ?>

<?php
                    }
                } else {
                    echo "<p class='text-center w-100'>No active tournaments found at the moment.</p>";
                }
            } catch (PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
            }
?>
    </div>
    </div>
</section>

<!-----The playground------->
<section class="playground_sec bothSide_gap py-5" style="background: #000;">
    <div class="cust_container">
        <div class="text-center mb-5 d-flex flex-column align-items-center">
            <h6 class="sub_heading white mb-2">About Us</h6>
            <h2 class="heading white fw-bold mb-4">The Playground</h2>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php
            $players = $pdo->query("SELECT COUNT(*) FROM ca_users WHERE USERTYPE = 'Player' AND LOG_STATUS = 'Y'")->fetchColumn();
            $clubs = $pdo->query("SELECT COUNT(*) FROM ca_users WHERE USERTYPE = 'Host' AND LOG_STATUS = 'Y'")->fetchColumn();
            $sessions = $pdo->query("SELECT COUNT(*) FROM ca_events WHERE STATUS = 'Completed'")->fetchColumn();

            $stats = [
                ['icon' => 'fa-users', 'count' => $players, 'label' => 'Total Players'],
                ['icon' => 'fa-building', 'count' => $clubs, 'label' => 'Total Clubs'],
                ['icon' => 'fa-play', 'count' => $sessions, 'label' => 'Total Sessions'],
                ['icon' => 'fa-fire', 'count' => $sessions * 12, 'label' => 'Total Matches']
            ];

            foreach ($stats as $stat): ?>
                <div class="feature-card p-4 text-center" style="flex: 1 1 200px; max-width: 250px; background: rgba(255, 255, 255, 0.05); border: 1px solid #ffffff22; border-radius: 12px; backdrop-filter: blur(10px);">
                    <div class="text-info mb-2" style="font-size: 2rem;"><i class="fa-solid <?= $stat['icon'] ?>"></i></div>
                    <h3 class="fw-bold text-white count mb-0" data-target="<?= (int)$stat['count'] ?>">0</h3>
                    <p class="text-white opacity-75 small mb-0"><?= $stat['label'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-------category_sec-------->
<section class="category_sec bothSide_gap py-5" style="background: #000;">
    <div class="cust_container">
        <h2 class="heading white text-center mb-5">Read Me</h2>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php
            $navs = [
                ['url' => 'about-us.php', 'icon' => 'fa-address-card', 'title' => 'Casa About Us'],
                ['url' => 'organiser.php', 'icon' => 'fa-briefcase', 'title' => 'Casa for Organiser'],
                ['url' => 'players.php', 'icon' => 'fa-users', 'title' => 'Casa for Players'],
                ['url' => 'casa-trainers.php', 'icon' => 'fa-user-tie', 'title' => 'Casa for Trainers'],
                ['url' => 'casa-clubs.php', 'icon' => 'fa-hotel', 'title' => 'Casa for Clubs']
            ];
            foreach ($navs as $nav): ?>
                <a href="<?= $nav['url'] ?>" class="text-decoration-none feature-card p-4 text-center" style="flex: 1 1 180px; max-width: 210px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; transition: 0.3s;">
                    <div class="text-info mb-3" style="font-size: 1.8rem;"><i class="fa-solid <?= $nav['icon'] ?>"></i></div>
                    <h6 class="text-white fw-bold mb-3"><?= $nav['title'] ?></h6>
                    <!--<span class="btn btn-outline-info btn-sm rounded-pill px-3" style="font-size: 0.7rem;">Learn More</span>-->
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-----Casa-post-banner------->
<div id="eventCard_post">
    <?php include "./poster.php"; ?>
</div>


<!------bookVenues_sec------->
<!-- <section class="bookVenues_sec bothSide_gap">
    <div class="cust_container">
        <div class="section_top">
            <h6 class="sub_heading">Lorem ipsum</h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="heading">Book Venues</h2>
                <a href="#" class="seeAll_btn btn">See all Venues</a>
            </div>
        </div>
        <div class="bookVenues_slider">
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
        </div>
    </div>
</section> -->

<!-----Popular Sports------->
<section class="popularSports_sec bothSide_gap">
    <div class="cust_container">
        <div class="section_top">
            <h6 class="sub_heading">Sports</h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="heading">Select The Sports</h2>
                <!-- <a href="#" class="seeAll_btn btn">See all</a> -->
            </div>
        </div>

        <!----Sports-Tab-start----->
        <ul class="popularSports_wrap">
            <li class="clickme">
                <a href="javascript:void();" data-tag="BadmintonTab" class="activelink">
                    <div class="popularSports_card">
                        <img src="assets/images/game/badminton_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Badminton</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="FootballTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/football_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Football</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="CricketTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/cricket_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Cricket</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="SwimmingTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/swimming_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Swimming</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="TennisTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/tennis_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Tennis</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="TableTennisTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/table_tennis_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Table Tennis</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
        </ul>

        <div style="clear: both;"></div>
        <div>
            <div class="list" id="BadmintonTab">
                <?php include "discover-games.php"; ?>
            </div>
            <div class="list hide" id="FootballTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="CricketTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="SwimmingTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="TennisTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="TableTennisTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
        </div>
        <!----Sports-Tab-End----->
    </div>
</section>

<!-----casa for Stadium------->
<!--<section id="casaStadium_sec" class="Storebanner_sec bothSide_gap mt-3" style="background-image: url(./assets/images/Casa_Stadium.jpg)">-->
<!--    <div class="cust_container">-->
<!--        <div class="wraper py-4">-->
<!--            <div class="d-flex align-items-center justify-content-center w-100">-->
<!--                <h6 class="sub_heading white">Casa for Stadium</h6>-->
<!--            </div>-->
<!--            <h2 class="heading white">Court Booking Management Software</h2>-->
<!--            <p class="desc white">Experience seamless and efficient court management with our all-in-one software solution, designed to simplify operations and enhance member satisfaction.</p>-->
<!--            <h6 class="miniheading text-center mt-2 mb-1">Key Features:</h6>-->
<!--            <p class="desc white mb-2">-->
<!--                <span class="pe-1">1).<b>Membership Management:</b> Easily handle player registrations, renewals, and profiles</span>-->
<!--                <span class="pe-1">2).<b>Daily & Advance Bookings:</b> Manage both same-day and future court reservations with ease.</span>-->
<!--                <span class="pe-1">3).<b>Inventory Management:</b> Track and control sports equipment and facility resources.</span>-->
<!--                <span class="pe-1">4).<b>Stringing Services:</b> Organize and monitor stringing requests efficiently.</span>-->
<!--                <span class="pe-1">5).<b>Online Player Booking:</b> Allow players to book courts anytime, anywhere.</span>-->
<!--                <span class="pe-1">6).<b>Billing & Payment History:</b> Simplify transactions and maintain detailed payment records.</span>-->
<!--            </p>-->
<!--            <p class="desc white">Streamline your club operations and elevate the player experience all through one powerful platform.</p>-->
<!--            <div class="d-flex align-items-center justify-content-center w-100 pt-4">-->
<!--                <a href="#" class="btn btn-info rounded text-white">Read More</a>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</section>-->

<!-----testimonials--------->
<!-- <section class="testimonials_sec bothSide_gap">
    <div class="cust_container">
        <div class="section_top">
            <h6 class="sub_heading">Testimonials</h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="heading">What our players say</h2>
                <a href="#" class="seeAll_btn btn">See all</a>
            </div>
        </div>

        <div class="testimonials_all testimonials_slider">
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Eos omnis sequi temporibus obcaecati dolorem saepe odio neque quasi velit magni officiis beatae libero unde, a fugiat consequuntur, numquam ut iste?</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Saepe recusandae est magni, earum tempore, animi molestias ullam veniam praesentium culpa ipsum eius asperiores. Esse eos quaerat temporibus, obcaecati debitis reiciendis.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Amet ratione quidem veritatis ullam repellat alias molestias, similique voluptates, sit ipsum ea repudiandae blanditiis incidunt aliquam error molestiae corporis et nemo?</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Soluta ullam error dolor non cupiditate totam officiis nam, molestiae aperiam expedita natus voluptatibus labore nulla tenetur temporibus illum quis odit. Sit.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat nisi recusandae eveniet, perspiciatis culpa exercitationem expedita, soluta deserunt qui non officia ipsum. Esse pariatur impedit tempora dolorum tenetur quam non.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Quod eligendi laborum maiores, in nulla tenetur tempore ducimus accusamus dicta id dignissimos neque quisquam aliquid cum ut voluptatibus incidunt nam deserunt?</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Ad laudantium, consectetur cum accusantium maiores delectus sequi magni reiciendis suscipit aperiam molestiae fugit recusandae similique labore eligendi ut culpa iure officia.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatibus necessitatibus nostrum quod, possimus aliquid dolores voluptas incidunt nesciunt provident, quis aperiam nulla perferendis sint unde quidem obcaecati? Facere, magnam iste.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
        </div>
    </div>
</section> -->


<!-----Community Voices---->
<section class="testimonials_sec bothSide_gap" style="background: #0f172a;">
    <div class="cust_container px-4">
        <div class="text-center mb-5 d-flex flex-column align-items-center">
            <h6 class="sub_heading white mb-2">Community Voices</h6>
            <h2 class="heading white fw-bold mb-4">What Our Players Say</h2>
            <div style="width: 60px; height: 3px; background: #22d3ee; border-radius: 2px;"></div>
        </div>

        <div class="netflix-slider-wrapper" id="netflixSlider">
            <div class="netflix-track d-flex gap-4">
                <?php
                try {
                    // Updated Query per your request
                    $query = "SELECT r.MESSAGE, r.PLAYER_ROLE, r.RATING, u.NAME, u.PROFILE_IMAGE 
                              FROM ca_reviews r
                              JOIN ca_users u ON r.USER_ID = u.ID 
                              WHERE r.STATUS = 'Active' 
                              ORDER BY r.DATE_CREATED DESC LIMIT 20";

                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($reviews):
                        foreach ($reviews as $row):
                            // Handling the profile image path
                            $userImg = !empty($row['PROFILE_IMAGE']) ? "uploads/users/" . $row['PROFILE_IMAGE'] : "assets/images/default-avatar.png";
                ?>
                            <div class="testimonial-card">
                                <div class="text-warning mb-2 small">
                                    <?php
                                    // Dynamic Star Rating
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo ($i <= $row['RATING']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <p class="text-white opacity-75 fst-italic mb-3">
                                    "<?= htmlspecialchars($row['MESSAGE']) ?>"
                                </p>
                                <div class="d-flex align-items-center mt-auto">
                                    <div class="rounded-circle me-3 avatar-placeholder"
                                        style="background: url('<?= $userImg ?>') center/cover; width: 45px; height: 45px; border: 1px solid rgba(34, 211, 238, 0.5); flex-shrink: 0;">
                                    </div>
                                    <div style="overflow: hidden;">
                                        <h6 class="text-white fw-bold mb-0 small text-truncate"><?= htmlspecialchars($row['NAME']) ?></h6>
                                        <small class="text-info" style="font-size: 0.7rem;"><?= htmlspecialchars($row['PLAYER_ROLE']) ?></small>
                                    </div>
                                </div>
                            </div>
                <?php
                        endforeach;
                    else:
                        echo "<p class='text-white opacity-50 px-5'>No reviews found.</p>";
                    endif;
                } catch (PDOException $e) {
                    echo "<p class='text-danger'>Error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!----Store-------->
<!--<section class="Storebanner_sec bothSide_gap" style="background-image: url(./assets/images/bags-rack-store.jpeg)">-->
<!--    <div class="cust_container">-->
<!--        <div class="wraper">-->
<!--            <div class="d-flex align-items-center justify-content-center w-100">-->
<!--                <h6 class="sub_heading white">Store</h6>-->
<!--            </div>-->
<!--            <h2 class="heading white">Visit Our Store</h2>-->
<!--            <p class="desc white">We offer a thoughtfully selected range of shuttlecocks and accessories from the most trusted and renowned brands in the sport.</p>-->
<!--            <p class="desc white">Whether you're just starting out or a seasoned professional, our collection caters to badminton players of all levels.</p>-->
<!--            <p class="desc white">Our mission is to provide authentic, high-quality products at the most competitive prices</p>-->
<!--            <p class="desc white">Visit our store to explore our full range and learn more about our offerings.</p>-->
<!--            <div class="d-flex align-items-center justify-content-center w-100 pt-4">-->
<!--                <a href="product-listing.php" class="btn btn-info rounded text-white">Visit Store</a>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</section>-->

<!----contact-us------->
<?php include "./contact-us.php"; ?>


<!------footer------>
<?php include "includes/footer.php";

ob_end_flush();

?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slider = document.getElementById('netflixSlider');
        let isDown = false;
        let startX;
        let scrollLeft;

        // Check if slider exists to prevent errors
        if (!slider) {
            console.error("Slider element #netflixSlider not found!");
            return;
        }

        // Mouse Events
        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2.5;
            slider.scrollLeft = scrollLeft - walk;
        });

        // Touch Events (For Mobile)
        slider.addEventListener('touchstart', (e) => {
            isDown = true;
            startX = e.touches[0].pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        }, {
            passive: true
        });

        slider.addEventListener('touchend', () => {
            isDown = false;
        });

        slider.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            const x = e.touches[0].pageX - slider.offsetLeft;
            const walk = (x - startX) * 2;
            slider.scrollLeft = scrollLeft - walk;
        }, {
            passive: true
        });
    });
</script>