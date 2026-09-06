<?php

ob_start();
session_start();
error_reporting(1);

// Logged-in users never see the marketing home page – send them to their hub.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['usertype']) && ($_SESSION['usertype'] === 'Host' || $_SESSION['usertype'] === 'Trainer')) {
        header("Location: host-dashboard.php");
    } else {
        header("Location: player-hub.php");
    }
    exit();
}

// Shared PDO handle used by the tournament, stats and testimonial sections.
// (The mysqli $conn is provided by includes/header.php below.)
$pdo = null;
try {
    include 'dbConnection_PDO.php';
} catch (PDOException $e) {
    $pdo = null;
}
?>

<!-- Header -->
<?php include "includes/header.php"; ?>


<!-- Hero banner -->
<section class="homebanner_sec bottomSide_gap">
    <div class="banner_image herobanner_slider">
        <?php
        try {
            $bannerResult = $conn->query("SELECT * FROM ca_herobanners WHERE status = 1");
        } catch (mysqli_sql_exception $e) {
            $bannerResult = false;
        }

        while ($bannerResult && ($row = $bannerResult->fetch_assoc())) {
        ?>
            <div class="item">
                <div class="item_img">
                    <img src="<?php echo $row['image']; ?>" class="img-fluid" alt="banner-image">
                </div>

                <div class="cust_container">
                    <div class="row bothSide_gap">

                        <div class="col-lg-6 col-12">
                            <div class="banner_content">
                                <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                    <h6 class="Homebanner sub_heading"><?php echo $row['sub_heading']; ?></h6>

                                    <h1 class="Homebanner heading">
                                        <?php echo $row['heading']; ?>
                                        <span><?php echo $row['highlight_text']; ?></span>
                                    </h1>

                                    <p class="bannerdesc desc mb-1"><?php echo $row['description1']; ?></p>

                                    <?php if (!empty($row['description2'])) { ?>
                                        <p class="bannerdesc desc"><?php echo $row['description2']; ?></p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                            <?php include "includes/Auth/login.php"; ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php } ?>

    </div>
</section>


<!-- Upcoming tournaments -->
<section class="tournament_sec bottomSide_gap" id="casaTournament_sec">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h6 class="sub_heading">Tournament</h6>
            <h2 class="heading">Upcoming Tournaments</h2>
        </div>

        <div class="tournamentcard_slider">
            <?php
            try {
                if (!$pdo) {
                    throw new PDOException('Database connection unavailable.');
                }

                $sql = "SELECT e.*, b.IMGAE,
                        (SELECT COUNT(ID) FROM to_teams WHERE TOURNAMENT_ID = e.ID) AS joined_count
                        FROM  to_tournaments e
                        LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID
                        WHERE e.STATUS = 'Active'
                        ORDER BY e.ID DESC";
                $tournaments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                if ($tournaments) {
                    foreach ($tournaments as $row) {
                        $date = date("M d, Y", strtotime($row['EVENT_DATE']));
                        $time = date("h:i A", strtotime($row['EVENT_TIME']));

                        $imgPath = !empty($row['IMGAE'])
                            ? "admin/assets/images/tournaments_banner/" . $row['IMGAE']
                            : "assets/images/default-tournament.jpg";

                        // Registration closes at CANCEL_DATE/CANCEL_TIME (America/New_York).
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
                        <!-- Conditional wrapper: <a> if open, <div> if closed -->
                        <?php if ($isRegistrationOpen): ?>
                            <a href="tournament-details.php?id=<?php echo $row['ID']; ?>" class="tournament_card">
                            <?php else: ?>
                                <div class="tournament_card" style="cursor: default;">
                                <?php endif; ?>

                                <div class="image">
                                    <img src="<?php echo $imgPath; ?>" class="img" alt="Banner" />
                                </div>

                                <div class="content">
                                    <h4 class="name"><?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?></h4>

                                    <span class="tagline">
                                        <?php
                                        $plainDescription = strip_tags($row['EVENT_DESCRIPTION']);
                                        $words = explode(' ', $plainDescription);
                                        echo htmlspecialchars(implode(' ', array_slice($words, 0, 5)));
                                        if (count($words) > 5) echo '...';
                                        ?>
                                    </span>

                                    <div class="meta-row category-line">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa-solid fa-user-group"></i></div>
                                            <div class="tournamentCardTxt">
                                                <span><?php echo $row['GENDER_CATEGORY']; ?> - <?php echo $row['EVENT_TYPE']; ?></span>
                                                <span>- <?php echo $row['EVENT_CATEGORY']; ?></span>
                                            </div>
                                        </div>
                                    </div>

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

                                    <div class="meta-row venue-line">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa fa-map-marker-alt"></i></div>
                                            <div class="tournamentCardTxt"><span><?php echo htmlspecialchars($row['EVENT_VENUE']); ?></span></div>
                                        </div>
                                    </div>

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

                                    <div class="joined-status">
                                        <div class="tournamentCardCol">
                                            <div class="tournamentCardIcon"><i class="fa fa-check-circle"></i></div>
                                            <div class="tournamentCardTxt"><span><?php echo ($row['joined_count'] ?? 0); ?> teams joined</span></div>
                                        </div>
                                    </div>

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

                                <?php if ($isRegistrationOpen): ?>
                            </a><?php else: ?>
        </div><?php endif; ?>

<?php
                    }
                } else {
                    echo "<p class='text-center w-100'>No active tournaments found at the moment.</p>";
                }
            } catch (PDOException $e) {
                echo "<p class='text-center w-100'>Unable to load tournaments right now.</p>";
            }
            ?>
        </div>
    </div>
</section>


<!-- Casa store -->
<section class="homeStore_sec bothSide_gap" id="homesotreid" style="background: #0f172a;">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h6 class="sub_heading">Store</h6>
            <h2 class="heading white">Casa Store</h2>
        </div>

        <div class="sttoreproduct_slider">
            <?php
            try {
                $productResult = $conn->query("SELECT ID, PRODUCT_NAME, PRICE, IMAGE, TNAME FROM ca_products ORDER BY ID DESC LIMIT 12");
            } catch (mysqli_sql_exception $e) {
                $productResult = false;
            }

            if ($productResult && $productResult->num_rows > 0) {
                while ($product = $productResult->fetch_assoc()) {
                    $productName = htmlspecialchars($product['PRODUCT_NAME'] ?? '', ENT_QUOTES, 'UTF-8');
                    $productDesc = htmlspecialchars(!empty($product['TNAME']) ? $product['TNAME'] : 'Free Delivery', ENT_QUOTES, 'UTF-8');
                    $productPrice = number_format((float) ($product['PRICE'] ?? 0), 2);
                    $productImage = !empty($product['IMAGE']) ? 'admin/' . ltrim($product['IMAGE'], '/') : 'assets/images/product/badminton1.jpg';
            ?>
                    <a href="product-listing.php" class="storeproduct_card">
                        <div class="image_wrap">
                            <img src="<?php echo htmlspecialchars($productImage, ENT_QUOTES, 'UTF-8'); ?>" class="img" alt="<?php echo $productName; ?>">
                        </div>
                        <div class="content">
                            <h4 class="name"><?php echo $productName; ?></h4>
                            <p class="desc"><?php echo $productDesc; ?></p>
                            <h6 class="amount">CAD <?php echo $productPrice; ?></h6>
                        </div>
                    </a>
                <?php } ?>
            <?php } else { ?>
                <p class="text-center w-100 text-white">No products found at the moment.</p>
            <?php } ?>
        </div>
    </div>
</section>


<!-- Gallery -->
<?php include "./gallery.php"; ?>


<!-- About us -->
<section class="playground_sec bothSide_gap" id="aboutusId" style="background: #000;">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h6 class="sub_heading white">About Us</h6>
            <h2 class="heading white fw-bold">The Playground</h2>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php
            try {
                if (!$pdo) {
                    throw new PDOException('Database connection unavailable.');
                }
                $players  = $pdo->query("SELECT COUNT(*) FROM ca_users WHERE USERTYPE = 'Player' AND LOG_STATUS = 'Y'")->fetchColumn();
                $clubs    = $pdo->query("SELECT COUNT(*) FROM ca_users WHERE USERTYPE = 'Host' AND LOG_STATUS = 'Y'")->fetchColumn();
                $sessions = $pdo->query("SELECT COUNT(*) FROM ca_events WHERE STATUS = 'Completed'")->fetchColumn();
            } catch (PDOException $e) {
                $players = $clubs = $sessions = 0;
            }

            $stats = [
                ['icon' => 'fa-users', 'count' => $players, 'label' => 'Total Players'],
                ['icon' => 'fa-building', 'count' => $clubs, 'label' => 'Total Clubs'],
                ['icon' => 'fa-play', 'count' => $sessions, 'label' => 'Total Sessions'],
                ['icon' => 'fa-fire', 'count' => $sessions * 12, 'label' => 'Total Matches'],
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


<!-- Read me -->
<section class="category_sec bothSide_gap">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h2 class="heading text-center">Read Me</h2>
        </div>
        <div class="categorycard_wrap">
            <?php
            $navs = [
                ['url' => 'about-us.php', 'icon' => 'fa-address-card', 'title' => 'Casa About Us'],
                ['url' => 'organiser.php', 'icon' => 'fa-briefcase', 'title' => 'Casa for Organiser'],
                ['url' => 'players.php', 'icon' => 'fa-users', 'title' => 'Casa for Players'],
                ['url' => 'casa-trainers.php', 'icon' => 'fa-user-tie', 'title' => 'Casa for Trainers'],
                ['url' => 'casa-clubs.php', 'icon' => 'fa-hotel', 'title' => 'Casa for Clubs'],
            ];
            foreach ($navs as $nav): ?>
                <a href="<?= $nav['url'] ?>" class="category_card">
                    <div class="icon">
                        <i class="fa-solid <?= $nav['icon'] ?>"></i>
                    </div>
                    <h4 class="name"><?= $nav['title'] ?></h4>
                    <div class="d-flex align-items-center justify-content-center">
                        <p class="readmore_btn btn">Learn More</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- Event posters / cards -->
<div id="eventCard_post">
    <?php include "./poster.php"; ?>
</div>


<!-- Popular sports -->
<section class="popularSports_sec bothSide_gap">
    <div class="cust_container">

        <div class="text-center d-flex flex-column align-items-center">
            <h2 class="heading">Select The Sports</h2>
        </div>

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
    </div>
</section>


<!-- Community voices -->
<section class="testimonials_sec bothSide_gap" style="background: #0f172a;">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h2 class="heading white fw-bold">What Our Players Say</h2>
        </div>

        <div class="netflix-slider-wrapper" id="netflixSlider">
            <div class="netflix-track d-flex gap-4">
                <?php
                try {
                    if (!$pdo) {
                        throw new PDOException('Database connection unavailable.');
                    }

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
                            $userImg = !empty($row['PROFILE_IMAGE'])
                                ? "profile_img/" . $row['PROFILE_IMAGE']
                                : "assets/images/profile.jpg";
                ?>
                            <div class="testimonial-card">
                                <div class="text-warning mb-2 small">
                                    <?php
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
                                        style="background: url('<?= htmlspecialchars($userImg) ?>') center/cover; width: 45px; height: 45px; border: 1px solid rgba(34, 211, 238, 0.5); flex-shrink: 0;">
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
                    echo "<p class='text-white opacity-50 px-5'>No reviews found.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</section>


<!-- Contact us -->
<?php include "./contact-us.php"; ?>


<!-- Footer -->
<?php
include "includes/footer.php";
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
