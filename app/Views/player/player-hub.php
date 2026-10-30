<?php
/**
 * Player Hub View Template Orchestrator
 */

ob_start();
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load Centralized Helper Functions
require_once __DIR__ . '/../../Helpers/player_hub_helper.php';

$views = [
    'header'       => __DIR__ . '/../layouts/header/inner-header.php',
    'review'       => __DIR__ . '/../partials/player-hub-review.php',
    'footer_links' => __DIR__ . '/../layouts/footer/footer-links.php',
    'footer'       => __DIR__ . '/../layouts/footer/footer.php',
];

$esc = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

// Authenticated user ID passed from controller
$current_user_id     ??= $_SESSION['user_id'] ?? null;
$player_prefs        ??= [];
$player_game         ??= $player_prefs['GAMES'] ?? 'Badminton';
$player_country      ??= $player_prefs['COUNTRY'] ?? '';
$player_province     ??= $player_prefs['PROVINCE'] ?? '';
$player_city         ??= $player_prefs['CITY'] ?? '';
$clubs               ??= [];
$upcomingTournaments ??= [];
$totalSessions       ??= $total_sessions ?? 0;
$user_premium        ??= 'N';
$products            ??= [];
$orderMap            ??= [];
$reviews             ??= [];
$pathPrefix          ??= '/';

// Pre-calculated view display parameters
$prefix        = $pathPrefix ?? '../';
$imagePath     = $user_profile_img ?? ($prefix . 'assets/images/profile.jpg');
$playerName    = $player_name ?? 'N/A';
$playerGender  = $player_gender ?? 'N/A';
$playerLevel   = $player_level ?? 'N/A';
$playerArea    = $player_area ?? 'N/A';
$sessionsCount = (int) ($totalSessions ?? 0);

include $views['header'];
?>

<!-- Extracted Page Stylesheet -->
<link rel="stylesheet" href="/app/Views/css/player/player-hub.css">

<section class="playerhub_sec bothSide_gap">
    <div class="cust_container">
        <div class="wraper">
            <!-- Header Welcome & Location Filters -->
            <div class="header_box">
                <div class="header_Box_left">
                    <span><i class="fa-solid fa-house-chimney-user"></i></span>
                    <span>PLAYER HUB Welcome back, Player !</span>
                </div>
                <div class="header_Box_Right">
                    <!-- Country -->
                    <select name="country" disabled>
                        <option value="">--Country--</option>
                        <?php if (!empty($player_country)): ?>
                            <option value="<?= $esc($player_country) ?>" selected>
                                <?= $esc($player_country) ?></option>
                        <?php endif; ?>
                    </select>

                    <!-- Province -->
                    <select name="province" disabled>
                        <option value="">--Province--</option>
                        <?php if (!empty($player_province)): ?>
                            <option value="<?= $esc($player_province) ?>" selected>
                                <?= $esc($player_province) ?></option>
                        <?php endif; ?>
                    </select>

                    <!-- City -->
                    <select name="area" disabled>
                        <option value="">--City--</option>
                        <?php if (!empty($player_city)): ?>
                            <option value="<?= $esc($player_city) ?>" selected>
                                <?= $esc($player_city) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Main Grid Component Layout -->
            <div class="main-grid">
                <!-- LEFT PANEL: Dossier Stats -->
                <div class="panel left-panel">
                    <h3 class="text-center">The Casa Dossier</h3>
                    <div class="dossierCard">
                        <div class="profileDetails">
                            <div class="profile">
                                <img src="<?= $imagePath ?>" alt="Profile Image" loading="eager" class="no-lazy"
                                    onerror="this.onerror=null; this.src='<?= $prefix ?>assets/images/profile.jpg';">
                            </div>
                            <div class="info">
                                <p><b>Name:</b> <?= $playerName ?></p>
                                <p><b>Gender:</b> <?= $playerGender ?></p>
                                <p><b>Level:</b> <?= $playerLevel ?></p>
                                <p><b>Area:</b> <?= $playerArea ?></p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="stats">
                            <div class="statInnDiv"><span><?= $sessionsCount ?></span>Total Sessions</div>
                            <div class="statInnDiv"><span><?= $sessionsCount * 12 ?></span>Total Games</div>
                            <div class="statInnDiv">
                                <div class="upcmngBtn"><img src="<?= $prefix ?>assets/images/upcoming.png" alt="upcoming icon"></div>
                                <span>5</span>No-Show
                            </div>
                            <div class="statInnDiv">
                                <div class="upcmngBtn"><img src="<?= $prefix ?>assets/images/upcoming.png" alt="upcoming icon"></div>
                                <span>210</span>Recorded
                            </div>
                        </div>

                        <!-- Win Ratio -->
                        <div class="win">
                            <div class="upcmngBtn"><img src="<?= $prefix ?>assets/images/upcoming.png" alt="upcoming icon"></div>
                            <div class="winParaDiv">
                                <p>
                                    <span>Win Ratio</span>
                                    <b>62%</b>
                                    <span class="up">▲</span>
                                    <small>(Based on recorded games)</small>
                                </p>
                            </div>
                            <p><b>Top Partner:</b> Alex</p>
                            <p><b>Rating:</b> ⭐ 4.5</p>
                        </div>

                        <!-- Tournaments Mini Slider -->
                        <div class="block">
                            <h4>Tournaments <img src="<?= $prefix ?>assets/images/upcoming.png" alt="upcoming icon"></h4>
                            <div class="tournamentBtm">
                                <div class="tourSlider tourSlick">
                                    <div class="tourSlide">
                                        <div class="tourCard"><span class="tourSpan">Casa Open • Summer Smash</span></div>
                                    </div>
                                    <div class="tourSlide">
                                        <div class="tourCard"><span class="tourSpan">Casa Open • Summer Smash</span></div>
                                    </div>
                                    <div class="tourSlide">
                                        <div class="tourCard"><span class="tourSpan">Casa Open • Summer Smash</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Skill Badges -->
                        <div class="block">
                            <h4>Skill Badges <img src="<?= $prefix ?>assets/images/upcoming.png" alt="upcoming icon"></h4>
                            <div class="badges">
                                <div class="badgeSlider badgeSlick">
                                    <div class="badgeSlide">
                                        <div class="badgeCard"><span class="badge smash">Smash Master</span></div>
                                    </div>
                                    <div class="badgeSlide">
                                        <div class="badgeCard"><span class="badge net">Net Ninja</span></div>
                                    </div>
                                    <div class="badgeSlide">
                                        <div class="badgeCard"><span class="badge def">Iron Defense</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Refer Button -->
                        <button class="refer" type="button" data-bs-toggle="modal" data-bs-target="#ReferModal">
                            Refer Good Player
                        </button>
                    </div>
                </div>

                <!-- CENTER PANEL: Clubs & Tournaments -->
                <div class="panel center-panel">
                    <!-- ALL CLUBS SECTION -->
                    <h3 class="text-center">All Clubs</h3>
                    <div class="scrollbox">
                        <?php if (!empty($clubs)): ?>
                            <?php foreach ($clubs as $club_data): ?>
                                <?php
                                if (empty($club_data) || !is_array($club_data)) continue;

                                $clubName = $esc($club_data['club_name'] ?? 'Unnamed Club');
                                $hostName = $esc($club_data['host_name'] ?? 'Unknown Host');
                                $hostId   = (int) ($club_data['host_id'] ?? 0);
                                $clubId   = (int) ($club_data['id'] ?? 0);

                                $clubInfoVal      = trim((string) ($club_data['club_info'] ?? ''));
                                $clubInfoSafe     = $esc($clubInfoVal !== '' ? $clubInfoVal : 'There is no data available.');
                                $clubCostVal      = trim((string) ($club_data['cost_info'] ?? ''));
                                $clubCostSafe     = $esc($clubCostVal !== '' ? $clubCostVal : 'There is no data available.');
                                $clubScheduleVal  = trim((string) ($club_data['schedule'] ?? ''));
                                $clubScheduleSafe = $esc($clubScheduleVal !== '' ? $clubScheduleVal : 'There is no data available.');

                                $clubLogoSrc      = get_club_logo_url($club_data['logo'] ?? '');
                                $membershipStatus = $club_data['membership_status'] ?? 'not_joined';
                                ?>
                                <div class="card">
                                    <div class="d-flex align-items-center gap-2 mb-0">
                                        <div class="club-logo-container">
                                            <img src="<?= $esc($clubLogoSrc) ?>" alt="<?= $clubName ?>" loading="eager" class="no-lazy"
                                                onerror="this.onerror=null; this.src='assets/images/logo/Final-Logo.png';" />
                                        </div>
                                        <div>
                                            <h4 class="club-header-title"><?= $clubName ?></h4>
                                            <div class="club-host-subtext">By <?= $hostName ?></div>
                                        </div>
                                    </div>
                                    <div class="content">
                                        <div class="action-bar-unified">
                                            <!-- Modal Triggers -->
                                            <button type="button" class="btn-stacked-synced club-info-btn" data-bs-toggle="modal"
                                                data-bs-target="#Infomodal" data-club-name="<?= $clubName ?>" data-club-info="<?= $clubInfoSafe ?>">
                                                <svg width="18" height="18" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="50" cy="50" r="50" fill="#3b72c4"/>
                                                    <path d="M57 32.5c0 3.6-2.9 6.5-6.5 6.5s-6.5-2.9-6.5-6.5 2.9-6.5 6.5-6.5 6.5 2.9 6.5 6.5zm-1.8 48.3c-7.8 2.5-12.8 3.5-16.7 3.5-5.9 0-8.3-2.9-8.3-7.5 0-3.3.7-7.3 2.1-11.9l4.5-14.7c.9-2.9 1.4-5.4 1.4-7.1 0-2.3-1.2-3.4-4.5-3.4-1.8 0-4.1.4-6.8 1.2l.9-3.9c7.6-2.5 12.9-3.7 16.9-3.7 5.8 0 8.1 3 8.1 7.6 0 2.4-.6 6.1-2 10.9l-4.7 15.5c-1 3.2-1.5 5.7-1.5 7.3 0 2.4 1.3 3.6 4.6 3.6 1.8 0 4.2-.5 7.2-1.6l-1.2 4.2z" fill="#ffffff"/>
                                                </svg>
                                                <span>Info</span>
                                            </button>

                                            <button type="button" class="btn-stacked-synced club-cost-btn" data-bs-toggle="modal"
                                                data-bs-target="#Costingmodal" data-club-name="<?= $clubName ?>" data-club-cost="<?= $clubCostSafe ?>">
                                                <svg width="18" height="18" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                                    <defs>
                                                        <linearGradient id="costingGrad_<?= $clubId ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                                            <stop offset="0%" stop-color="#2b7beb"/>
                                                            <stop offset="100%" stop-color="#4aa4f7"/>
                                                        </linearGradient>
                                                    </defs>
                                                    <rect width="512" height="512" rx="140" fill="url(#costingGrad_<?= $clubId ?>)"/>
                                                    <path d="M256 95v322M200 165c0-24 25-42 56-42s56 18 56 42-25 38-56 48-56 24-56 48 25 42 56 42 56-18 56-42" stroke="#ffffff" stroke-width="46" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                                <span>Costing</span>
                                            </button>

                                            <button type="button" class="btn-stacked-synced club-schedule-btn" data-bs-toggle="modal"
                                                data-bs-target="#GameSchedulemodal" data-club-name="<?= $clubName ?>" data-club-schedule="<?= $clubScheduleSafe ?>">
                                                <i class="fa-solid fa-calendar-check"></i>
                                                <span>Schedule</span>
                                            </button>

                                            <?php if ($membershipStatus === 'accepted'): ?>
                                                <div class="btn-stacked-synced status-member">
                                                    <i class="fa-solid fa-user-shield"></i>
                                                    <span>Member</span>
                                                </div>
                                                <a href="/player/player-dashboard.php?host_id=<?= $hostId ?>" class="btn-stacked-synced action-view">
                                                    <i class="fa-solid fa-gamepad"></i>
                                                    <span>Games</span>
                                                </a>
                                            <?php elseif ($membershipStatus === 'pending'): ?>
                                                <div class="btn-stacked-synced status-pending-state">
                                                    <i class="fa-solid fa-hourglass-half"></i>
                                                    <span>Pending</span>
                                                </div>
                                                <a href="#" class="btn-stacked-synced action-view disabled" title="Awaiting host approval">
                                                    <i class="fa-solid fa-gamepad"></i>
                                                    <span>Games</span>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn-stacked-synced join-club-btn" data-club-id="<?= $clubId ?>">
                                                    <i class="fa-solid fa-right-to-bracket"></i>
                                                    <span>Join</span>
                                                </button>
                                                <a href="#" class="btn-stacked-synced action-view disabled" title="Must join club first">
                                                    <i class="fa-solid fa-gamepad"></i>
                                                    <span>Games</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="label text-center text-secondary my-3">No clubs found matching your preferred game.</p>
                        <?php endif; ?>
                    </div>

                    <!-- TOURNAMENTS SECTION -->
                    <h3 class="text-center">Tournaments</h3>
                    <div class="scrollbox">
                        <?php if (!empty($upcomingTournaments)): ?>
                            <?php foreach ($upcomingTournaments as $t): ?>
                                <div class="tournamentCard">
                                    <div class="banner">
                                        <img src="<?= $t['imgPath'] ?>" alt="<?= $t['cupName'] ?>" loading="eager" class="no-lazy"
                                            onerror="this.onerror=null; this.src='<?= $esc($pathPrefix) ?>assets/images/default.jpg';">
                                    </div>
                                    <div class="card-body">
                                        <div class="card-header">
                                            <div class="headwrap">
                                                <div class="title"><?= $t['cupName'] ?></div>
                                            </div>
                                            <div class="subtitle">
                                                <p><?= $t['eventCategory'] ?> - <?= $t['genderCategory'] ?> - <?= $t['eventType'] ?></p>
                                            </div>
                                        </div>

                                        <div class="quoteDiv">
                                            <div class="quote">"<?= $t['quote'] ?>"</div>
                                            <?php if ($t['isRegistrationOpen']): ?>
                                                <div class="regBtn"><p>Registration Open</p></div>
                                            <?php else: ?>
                                                <div class="regBtn reg-closed">
                                                    <p>Registration Closed</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="content">
                                            <div class="info-grid">
                                                <div class="info"><i class="fa fa-calendar-alt"></i><span>Date:</span> <?= $t['date'] ?></div>
                                                <div class="info"><i class="fa fa-clock"></i><span>Time:</span> <?= $t['time'] ?></div>
                                                <div class="info"><i class="fa fa-map-marker-alt"></i><span>Venue:</span> <?= $t['eventVenue'] ?></div>
                                                <div class="info"><i class="fa-solid fa-comment-dollar"></i><span><?= $t['eventCost'] ?> <strong>per player</strong></span></div>
                                                <div class="info"><i class="fa-solid fa-feather-pointed"></i><span>Birdie: <strong><?= $t['shuttleType'] ?></strong></span></div>
                                                <div class="info">
                                                    <i class="fa fa-check-circle"></i>
                                                    <span><?= $t['joinedCount'] ?><?= $t['maxTeams'] !== '' ? ' / ' . $t['maxTeams'] : '' ?> <strong>teams joined</strong></span>
                                                </div>
                                            </div>

                                            <div class="actions">
                                                <?php switch ($t['paymentState']):
                                                    case 'unpaid': ?>
                                                        <button type="button" class="joinbtn pay-now-trigger"
                                                            data-bs-toggle="modal" data-bs-target="#PaymentModal"
                                                            data-id="<?= $t['id'] ?>" data-name="<?= $t['cupName'] ?>" data-amount="<?= $t['amount'] ?>">
                                                            Mark Paid
                                                        </button>
                                                        <?php break; ?>

                                                    <?php case 'pending': ?>
                                                        <span class="joinbtn status-pending">Pending</span>
                                                        <?php break; ?>

                                                    <?php case 'paid': ?>
                                                        <span class="joinbtn status-paid">Payment Success</span>
                                                        <?php break; ?>

                                                    <?php case 'not_joined':
                                                    default: ?>
                                                        <?php if ($t['isRegistrationOpen']): ?>
                                                            <a href="tournament-details.php?id=<?= $t['id'] ?>" class="joinbtn">Join Now</a>
                                                        <?php else: ?>
                                                            <button type="button" class="joinbtn status-disabled" disabled>
                                                                Join Now
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php break; ?>
                                                <?php endswitch; ?>

                                                <?php if ($t['isDrawLive']): ?>
                                                    <a href="tournament/?id=<?= $t['id'] ?>" class="joinbtn">View Live</a>
                                                <?php else: ?>
                                                    <button type="button" class="joinbtn"
                                                        onclick="alert('The Draw is almost here! Live match schedules and brackets will go live on <?= $t['drawDisplayDate'] ?> at 10:00 am EST. Stay tuned!');">
                                                        View Live
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="label text-center text-secondary my-3">No upcoming tournaments found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT PANEL: Casa Store Preview -->
                <div class="panel right-panel">
                    <h3 class="text-center">The Casa Store</h3>
                    <div class="scrollbox">
                        <div class="casaStore">
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product):
                                    $productID   = (int) $product['ID'];
                                    $productName = $product['display_name'] ?? 'N/A';
                                    $price       = $product['formatted_price'] ?? '0.00';
                                    $imagePath   = $product['image_path'] ?? ($pathPrefix . 'assets/images/t-shirt.jpg');
                                    $orderData   = $orderMap[$productID] ?? null;
                                ?>
                                    <div class="store-item">
                                        <img src="<?= $imagePath ?>" alt="<?= $productName ?>" loading="eager" class="no-lazy"
                                            onerror="this.onerror=null; this.src='<?= $esc($pathPrefix) ?>assets/images/t-shirt.jpg';">
                                        <div class="item-info">
                                            <div class="storeItemTop">
                                                <h4><?= $productName ?></h4>
                                                <p class="price">CAD <?= $price ?></p>
                                            </div>

                                            <?php if ($orderData): ?>
                                                <p class="order">
                                                    Last order: <b><?= $esc((string)$orderData['QUANTITY']) ?> pcs – CAD <?= $esc(number_format((float)$orderData['PRICE'], 2)) ?></b>
                                                </p>
                                                <div class="order-capsule">
                                                    <span>Last Order </span>
                                                    <b><?= $esc((string)$orderData['QUANTITY']) ?> Pcs</b>
                                                    <span class="divider">|</span>
                                                    <b>CAD <?= $esc(number_format((float)$orderData['PRICE'], 2)) ?></b>
                                                    <?php if (!empty($orderData['ORDER_DATE'])): ?>
                                                        <span class="divider">|</span>
                                                        <span><?= date("M d, Y", strtotime($orderData['ORDER_DATE'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                $status = trim((string)($orderData['STATUS'] ?? ''));
                                                if (!empty($status)) {
                                                    $lowerStatus = strtolower($status);
                                                    if ($lowerStatus === 'completed' || $lowerStatus === 'delivered') {
                                                        echo '<span class="status delivered"><i class="fa fa-check-circle me-1"></i>Delivered</span>';
                                                    } elseif ($lowerStatus === 'active' || $lowerStatus === 'pending' || $lowerStatus === 'processing') {
                                                        echo '<span class="status active"><i class="fa fa-clock me-1"></i>' . ucfirst($lowerStatus) . '</span>';
                                                    } else {
                                                        $safeStatus = $esc($status);
                                                        $statusClass = strtolower(str_replace(' ', '-', $safeStatus));
                                                        echo '<span class="status ' . $statusClass . '">' . $safeStatus . '</span>';
                                                    }
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-secondary my-3">No products found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (function_exists('canViewProductListing') && canViewProductListing()): ?>
                        <a href="product-listing.php" class="casastore_btn mt-4">The Casa Store</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- REVIEW SECTION -->
            <?php include $views['review']; ?>
        </div>
    </div>
</section>

<!-- MODALS COMPONENT -->
<?php render_scrollable_modal('Infomodal', 'Club Info'); ?>
<?php render_scrollable_modal('Costingmodal', 'Game Costing'); ?>
<?php render_scrollable_modal('GameSchedulemodal', 'Game Schedule', 'modal-lg'); ?>

<!-- Refer Modal -->
<div class="modal fade" id="ReferModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="ReferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ReferModalLabel">Refer Good Player</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>Take a moment to fill in your details using “Register Here”</li>
                    <li>Once registered, DM the club admin to receive your login credentials</li>
                    <li>Change your password after logging in for the first time (for security reasons)</li>
                    <li>Check the Game Card for available games by category & level</li>
                    <li>Click Join based on your availability</li>
                    <li>More details and game schedule are available on the portal</li>
                </ul>
                <p><i class="fa-solid fa-envelope"></i><span>info.casagames@gmail.com</span></p>
                <p><i class="fa-solid fa-globe"></i><span>https://casa-games.com</span></p>
                <p><i class="fa-solid fa-dumbbell"></i><span>Stay fit and active!</span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<?php $csrfToken = $_SESSION['csrf_token'] ?? ''; ?>
<div class="modal fade" id="PaymentModal" tabindex="-1" aria-labelledby="PaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="PaymentModalLabel">Notify Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="paymentForm">
                <input type="hidden" name="csrf_token" value="<?= $esc($csrfToken) ?>">
                <input type="hidden" name="tournament_id" id="modalTournamentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-1">Tournament: <strong id="modalTournamentName"></strong></p>
                        <p>Total Amount: <strong id="modalTournamentAmountDisplay"></strong></p>
                    </div>
                    <div id="paymentError" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label>Enter Payment Amount</label>
                        <input type="number" step="0.01" name="payment_amount" class="form-control" value="40.00"
                            required readonly>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Enter Payment Date</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control" required
                                readonly>
                        </div>
                        <div class="col-6 mb-3">
                            <label>Enter Payment Time</label>
                            <input type="time" id="payment_time" name="payment_time" class="form-control" required
                                readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Payment Type</label>
                        <select name="payment_type" class="form-control" required>
                            <option value="" disabled selected>Select Type</option>
                            <option value="Internet" selected>Interac</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Any Payment Details (Optional)</label>
                        <input type="text" name="payment_details" class="form-control"
                            placeholder="e.g. Reference number">
                    </div>
                    <div class="mb-3">
                        <label>Message (Optional)</label>
                        <textarea name="payment_message" class="form-control" rows="2"
                            placeholder="Your message here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submitBtn" class="btn btn-success">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transaction Pending Verification / Success Overlay -->
<div id="successOverlay">
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h1 class="success-title">Transaction Pending Verification!</h1>
        <p class="success-desc">We have logged your <br><strong>CAD <span
                    id="dispAmt"></span>(Interac/Cash) payment</strong></p>
        <p class="success-note">Admin verifies the transfer and confirms your entry. Your button will turn green and
            display "Payment Success". See you on the court soon!</p>
        <button type="button" onclick="location.reload()" class="btn btn-danger mt-4 success-close-btn">Close</button>
    </div>
</div>

<?php
include $views['footer_links'];
?>

<!-- Extracted Interactivity JavaScript -->
<script src="/app/script/player-hub.js"></script>

<?php
include $views['footer'];
ob_end_flush();
?>