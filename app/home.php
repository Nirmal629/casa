<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$views = [
    'header' => __DIR__ . "/Views/layouts/header/header.php",
    'login' => __DIR__ . "/Views/auth/login.php",
    'gallery' => __DIR__ . "/Views/pages/gallery/gallery.php",
    'poster' => __DIR__ . "/Views/pages/poster/poster.php",
    'discover_games' => __DIR__ . "/Views/pages/community/discover-games.php",
    'contact_us' => __DIR__ . "/Views/pages/contact/contact-us.php",
    'footer' => __DIR__ . "/Views/layouts/footer/footer.php",
];

$esc = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

$banners ??= [];
if (empty($banners)) {
    $banners = [
        [
            'image' => '/assets/images/default-banner.jpg',
            'sub_heading' => 'WELCOME TO CASA',
            'heading' => 'Play & Host',
            'highlight_text' => 'Games',
            'description1' => 'Join sports games and tournaments near you.',
            'description2' => ''
        ]
    ];
}
$tournaments ??= [];
$products ??= [];
$stats ??= [];
$reviews ??= [];
$posters ??= [];
?>

<!-- Header -->
<?php include $views['header']; ?>

<!-- Page Stylesheet -->
<link rel="stylesheet" href="/app/Views/css/home.css">

<!-- Hero banner -->
<?php if (!empty($banners)): ?>
    <section class="homebanner_sec bottomSide_gap">
        <div class="banner_image herobanner_slider">
            <?php foreach ($banners as $row): ?>
                <div class="item">
                    <div class="item_img">
                        <img src="<?= htmlspecialchars($row['image']) ?>" class="img-fluid" alt="banner-image">
                    </div>

                    <div class="cust_container">
                        <div class="row bothSide_gap">
                            <div class="col-lg-6 col-12">
                                <div class="banner_content">
                                    <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                        <h6 class="Homebanner sub_heading"><?= htmlspecialchars($row['sub_heading']) ?></h6>

                                        <h1 class="Homebanner heading">
                                            <?= htmlspecialchars($row['heading']) ?>
                                            <span><?= htmlspecialchars($row['highlight_text']) ?></span>
                                        </h1>

                                        <p class="bannerdesc desc mb-1"><?= htmlspecialchars($row['description1']) ?></p>

                                        <?php if (!empty($row['description2'])): ?>
                                            <p class="bannerdesc desc"><?= htmlspecialchars($row['description2']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                                <?php include $views['login']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>


<!-- Upcoming tournaments -->
<section class="tournament_sec bottomSide_gap" id="casaTournament_sec">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h6 class="sub_heading">Tournament</h6>
            <h2 class="heading">Upcoming Tournaments</h2>
        </div>

        <div class="tournamentcard_slider">
            <?php if (!empty($tournaments)): ?>
                <?php foreach ($tournaments as $row): ?>
                    <?php
                    $id       = (int)($row['ID'] ?? 0);
                    $name     = $esc($row['display_name'] ?? '');
                    $tagline  = $esc($row['display_tagline'] ?? '');
                    $date     = $esc($row['formatted_date'] ?? '');
                    $time     = $esc($row['formatted_time'] ?? '');
                    $img      = $esc($row['image_path'] ?? 'assets/images/default-tournament.jpg');
                    $open     = !empty($row['is_open']);
                    $gender   = $esc($row['gender_cat'] ?? '');
                    $type     = $esc($row['event_type'] ?? '');
                    $category = $esc($row['event_cat'] ?? '');
                    $venue    = $esc($row['venue'] ?? '');
                    $shuttle  = $esc($row['shuttle'] ?? 'Feather');
                    $fee      = $esc($row['fee_formatted'] ?? '0.00');
                    $joined   = (int)($row['teams_joined'] ?? 0);
                    ?>

                    <?php if ($open): ?>
                        <a href="tournament-details.php?id=<?= $id; ?>" class="tournament_card">
                    <?php else: ?>
                        <div class="tournament_card tournament_card_closed">
                    <?php endif; ?>

                        <div class="image">
                            <img src="<?= $img; ?>" class="img" alt="<?= $name; ?>">
                        </div>

                        <div class="content">
                            <h4 class="name"><?= $name; ?></h4>

                            <?php if ($tagline): ?>
                                <span class="tagline"><?= $tagline; ?></span>
                            <?php endif; ?>

                            <div class="card-info-list">
                                <!-- Category -->
                                <div class="info-row">
                                    <span class="info-icon"><i class="fa-solid fa-user-group"></i></span>
                                    <span class="info-text"><?= $gender; ?> – <?= $type; ?> – <?= $category; ?></span>
                                </div>

                                <!-- Date & Time -->
                                <div class="info-row d-flex align-items-center gap-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="info-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                        <span class="info-text"><?= $date; ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="info-icon"><i class="fa-solid fa-clock"></i></span>
                                        <span class="info-text"><?= $time; ?></span>
                                    </div>
                                </div>

                                <!-- Venue -->
                                <div class="info-row">
                                    <span class="info-icon"><i class="fa-solid fa-location-dot"></i></span>
                                    <span class="info-text"><?= $venue; ?></span>
                                </div>

                                <!-- Price -->
                                <div class="info-row">
                                    <span class="info-icon"><i class="fa-solid fa-comment-dollar"></i></span>
                                    <span class="info-text"><strong><?= $fee; ?></strong> per player</span>
                                </div>

                                <!-- Birdie -->
                                <div class="info-row">
                                    <span class="info-icon"><i class="fa-solid fa-feather-pointed"></i></span>
                                    <span class="info-text"><strong>Birdie:</strong> <?= $shuttle; ?></span>
                                </div>

                                <!-- Joined teams -->
                                <div class="info-row">
                                    <span class="info-icon"><i class="fa-solid fa-circle-check"></i></span>
                                    <span class="info-text"><?= $joined; ?> <?= $joined === 1 ? 'team' : 'teams'; ?> joined</span>
                                </div>
                            </div>

                            <?php if ($open): ?>
                                <div class="openBtn open-btn-active">
                                    <span>Registration open</span>
                                </div>
                            <?php else: ?>
                                <div class="openBtn open-btn-closed">
                                    <span>Registration Closed</span>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php if ($open): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center w-100">No active tournaments found at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- Casa store -->
<section class="homeStore_sec bothSide_gap" id="homesotreid">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h6 class="sub_heading">Store</h6>
            <h2 class="heading white">Casa Store</h2>
        </div>

        <div class="sttoreproduct_slider">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $name      = $esc($product['display_name'] ?? '');
                    $desc      = $esc($product['display_desc'] ?? '');
                    $price     = $esc($product['formatted_price'] ?? '0.00');
                    $imagePath = $esc($product['image_path'] ?? '');
                    ?>

                    <a href="product-listing.php" class="storeproduct_card">
                        <div class="image_wrap">
                            <img
                                src="<?= $imagePath; ?>"
                                class="img"
                                alt="<?= $name; ?>"
                            >
                        </div>

                        <div class="content">
                            <h4 class="name"><?= $name; ?></h4>
                            <p class="desc"><?= $desc; ?></p>
                            <h6 class="amount">$<?= $price; ?></h6>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center w-100 text-white">No products found at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- Gallery -->
<?php include $views['gallery']; ?>


<!-- About us -->
<?php if (!empty($stats)): ?>
    <section class="playground_sec bothSide_gap" id="aboutusId">
        <div class="cust_container">
            <div class="text-center d-flex flex-column align-items-center">
                <h6 class="sub_heading white">About Us</h6>
                <h2 class="heading white fw-bold">The Playground</h2>
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <?php foreach ($stats as $stat): ?>
                    <?php
                    $icon  = htmlspecialchars((string)($stat['icon'] ?? 'fa-chart-simple'), ENT_QUOTES, 'UTF-8');
                    $count = (int)($stat['count'] ?? 0);
                    $label = htmlspecialchars((string)($stat['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="feature-card p-4 text-center">
                        <div class="text-info mb-2"><i class="fa-solid <?= $icon ?>"></i></div>
                        <h3 class="fw-bold text-white count mb-0" data-target="<?= $count ?>"><?= number_format($count) ?></h3>
                        <p class="text-white opacity-75 small mb-0"><?= $label ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>


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
    <?php include $views['poster']; ?>
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

        <div class="clear-both"></div>

        <div>
            <div class="list" id="BadmintonTab">
                <?php include $views['discover_games']; ?>
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
<section class="testimonials_sec bothSide_gap">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h2 class="heading white fw-bold">What Our Players Say</h2>
        </div>

        <div class="netflix-slider-wrapper" id="netflixSlider">
            <div class="netflix-track d-flex gap-4">
                <?php
                if (!empty($reviews)):
                    foreach ($reviews as $row):
                        $profileFile = !empty($row['PROFILE_IMAGE']) ? "profile_img/" . $row['PROFILE_IMAGE'] : "";
                        $userImg = (!empty($profileFile) && file_exists(__DIR__ . '/../' . $profileFile))
                            ? $profileFile
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
                                    style="background-image: url('<?= htmlspecialchars($userImg) ?>');">
                                </div>
                                <div class="user-meta">
                                    <h6 class="text-white fw-bold mb-0 small text-truncate">
                                        <?= htmlspecialchars($row['NAME']) ?>
                                    </h6>
                                    <small class="text-info player-role"><?= htmlspecialchars($row['PLAYER_ROLE']) ?></small>
                                </div>
                            </div>
                        </div>
                        <?php
                    endforeach;
                else:
                    echo "<p class='text-white opacity-50 px-5'>No reviews found.</p>";
                endif;
                ?>
            </div>
        </div>
    </div>
</section>


<!-- Extracted Slider Interactivity Script -->
<script src="/app/script/home.js"></script>

<!-- Contact us -->
<?php
if (!defined('HOME_PAGE_INCLUDED')) {
    define('HOME_PAGE_INCLUDED', true);
}
include $views['contact_us'];
?>

<!-- Footer -->
<?php
include $views['footer'];
ob_end_flush();
?>