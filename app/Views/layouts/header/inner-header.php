<?php
/**
 * Inner Page Header & User Profile Navigation
 * Layout View: app/Views/layouts/header/inner-header.php
 */

require_once __DIR__ . '/../../../../init.php';

// 1. Session check BEFORE DB connection & queries
requireLogin('index.php');

// 2. Fetch Cached User Data & Normalize User Profile
$conn        = \App\Config\Database::getMysqli();
$userRaw     = getCurrentUser($conn) ?? [];
$userProfile = normalizeUserData($userRaw);

// 3. Centralized User Role Logic
$isPlayer        = ($userProfile->role === 'player');
$isHost          = ($userProfile->role === 'host');
$isTrainer       = ($userProfile->role === 'trainer');
$displayUserType = ucfirst($userProfile->role);

// 4. Centralized Routes Mapping
$currentInnerPage = getCurrentPage();
$pathPrefix       = '/';

$routes = [
    'home'           => "{$pathPrefix}index.php",
    'playerHub'      => "{$pathPrefix}player/player-hub.php",
    'hostDashboard'  => "{$pathPrefix}host/host-dashboard.php",
    'hostAccount'    => "{$pathPrefix}host/host-account.php",
    'hostAuditLog'   => "{$pathPrefix}host/host-audit-log.php",
    'productListing' => "{$pathPrefix}product-listing.php",
    'playerProfile'  => "{$pathPrefix}player/player-profile.php",
    'myOrder'        => "{$pathPrefix}my-order.php",
    'logout'         => "{$pathPrefix}logout.php"
];

$hideSiteBackButton = ($currentInnerPage === 'player-hub.php' || $currentInnerPage === 'host-dashboard.php');
$defaultBackUrl     = ($isHost || $isTrainer) ? $routes['hostDashboard'] : $routes['playerHub'];

// 5. Standardized Profile Image Path Handling
$cleanProfileImg = !empty($userProfile->profileImage) ? basename(ltrim($userProfile->profileImage, '/\\')) : '';
$rootDir         = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 4);

$profileImgUrl = '';
if (!empty($cleanProfileImg) && file_exists($rootDir . '/profile_img/' . $cleanProfileImg)) {
    $profileImgUrl = "{$pathPrefix}profile_img/" . $cleanProfileImg;
}

$userInitials  = getUserInitials($userProfile->name);
$chipRoleClass = $isHost ? 'host' : ($isTrainer ? 'trainer' : '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "header-links.php"; ?>
    <link rel="stylesheet" href="<?= e($pathPrefix) ?>assets/css/inner-header.css">
    <link rel="stylesheet" href="<?= e($pathPrefix) ?>assets/css/casa-dropdown.css">
    <script src="<?= e($pathPrefix) ?>assets/js/casa-dropdown.js" defer></script>
    <script src="<?= e($pathPrefix) ?>assets/js/casa-pagination.js" defer></script>
    <script src="<?= e($pathPrefix) ?>app/script/inner-header.js" defer></script>
</head>

<body>
    <!-- Main Header Section -->
    <section class="main_header innerpageHeader" id="main_Header">
        <div class="cust_container">
            <div class="wraper">

                <!-- LEFT: Logo & Navigation Back Button -->
                <div class="site-header-left">
                    <button type="button" class="site-back-btn" data-fallback-url="<?= e($defaultBackUrl) ?>" <?php echo $hideSiteBackButton ? 'hidden' : ''; ?> aria-label="Go back to previous page" title="Go back">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <a href="<?= e($routes['home']) ?>">
                        <figure class="Logo_area m-0">
                            <img src="<?= e($pathPrefix) ?>assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                        </figure>
                    </a>
                </div>

                <!-- CENTER: Navigation Links -->
                <ul class="innerpagemenu_wrap">
                    <?php if ($isPlayer): ?>
                        <li><a href="<?= e($routes['playerHub']) ?>" class="nav_link <?php echo $currentInnerPage === 'player-hub.php' ? 'active' : ''; ?>">
                            <span>The Player Hub</span></a></li>
                    <?php endif; ?>
                    <?php if ($isHost || $isTrainer): ?>
                        <li><a href="<?= e($routes['hostDashboard']) ?>" class="nav_link <?php echo $currentInnerPage === 'host-dashboard.php' ? 'active' : ''; ?>">
                            <span>Dashboard</span></a></li>
                    <?php endif; ?>
                    <?php if ($isHost): ?>
                        <li><a href="<?= e($routes['hostAuditLog']) ?>" class="nav_link <?php echo $currentInnerPage === 'host-audit-log.php' ? 'active' : ''; ?>">
                            <span>Audit Log</span></a></li>
                    <?php endif; ?>
                    <?php if (canViewProductListing()): ?>
                    <li><a href="<?= e($routes['productListing']) ?>" class="nav_link <?php echo $currentInnerPage === 'product-listing.php' ? 'active' : ''; ?>">
                        <span>Casa Store</span></a></li>
                    <?php endif; ?>
                </ul>

                <!-- RIGHT: Profile Chip Button -->
                <div class="nav-profile-chip" id="navProfileChip">
                    <button type="button" class="nav-chip-trigger" id="navChipTrigger" aria-expanded="false" aria-haspopup="menu" aria-label="User menu">
                        <div class="nav-chip-avatar">
                            <?php if ($profileImgUrl): ?>
                                <img src="<?= e($profileImgUrl) ?>" alt="Profile" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                <span class="avatar-fallback" style="display:none;"><?= e($userInitials) ?></span>
                            <?php else: ?>
                                <span class="avatar-fallback"><?= e($userInitials) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="nav-chip-text">
                            <span class="nav-chip-name"><?= e($userProfile->name) ?></span>
                            <span class="nav-chip-role <?= e($chipRoleClass) ?>"><?= e($displayUserType) ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down nav-chip-arrow" aria-hidden="true"></i>
                    </button>

                    <div class="nav-chip-dropdown" id="navChipDropdown" role="menu">
                        <div class="ncd-links">
                            <a href="<?= e($routes['playerProfile']) ?>" role="menuitem">
                                <i class="fa-solid fa-id-card" aria-hidden="true"></i> Full Profile
                            </a>
                            <a href="<?= e($routes['myOrder']) ?>" role="menuitem">
                                <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> My Orders
                            </a>
                            <?php if ($isPlayer): ?>
                            <a href="<?= e($routes['playerHub']) ?>" role="menuitem">
                                <i class="fa-solid fa-house" aria-hidden="true"></i> Player Hub
                            </a>
                            <?php elseif ($isHost || $isTrainer): ?>
                            <a href="<?= e($routes['hostAccount']) ?>" role="menuitem">
                                <i class="fa-solid fa-house-chimney-window" aria-hidden="true"></i> Host Account
                            </a>
                            <a href="<?= e($routes['hostDashboard']) ?>" role="menuitem">
                                <i class="fa-solid fa-gauge" aria-hidden="true"></i> Host Dashboard
                            </a>
                            <?php endif; ?>
                            <?php if ($isHost): ?>
                            <a href="<?= e($routes['hostAuditLog']) ?>" role="menuitem">
                                <i class="fa-solid fa-chart-line" aria-hidden="true"></i> Audit Log
                            </a>
                            <?php endif; ?>
                            <div class="ncd-divider"></div>
                            <a href="<?= e($routes['logout']) ?>" class="ncd-logout" role="menuitem">
                                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
