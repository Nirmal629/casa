<?php
if (!class_exists('App\Config\Database')) {
    require_once __DIR__ . '/../../../Config/env.php';
    require_once __DIR__ . '/../../../Config/Database.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use App\Config\Database;
$conn = Database::getMysqli();

$advertisements = [];
$today = date('Y-m-d');
$advertisementQuery = "
    SELECT id, short_text, main_image, redirect_url, start_date, end_date, status, priority
    FROM ca_advertisements
    WHERE (status IS NULL OR status = '' OR LOWER(status) = 'active')
      AND (start_date IS NULL OR start_date = '' OR start_date <= ?)
      AND (end_date IS NULL OR end_date = '' OR end_date >= ?)
    ORDER BY priority DESC, id DESC
";
$advertisementStmt = $conn->prepare($advertisementQuery);

if ($advertisementStmt) {
    $advertisementStmt->bind_param('ss', $today, $today);
    $advertisementStmt->execute();
    $advertisementResult = $advertisementStmt->get_result();

    while ($advertisementRow = $advertisementResult->fetch_assoc()) {
        $advertisements[] = $advertisementRow;
    }

    $advertisementStmt->close();
}

$count_ads = count($advertisements);

// Safe user retrieval with in-memory request caching
$user = \App\Helpers\Session::getCurrentUser($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . "/header-links.php"; ?>
    <link rel="stylesheet" href="/assets/css/header.css">
</head>

<body>
    <?php if ($count_ads > 0): ?>
        <!-- Advertisement Section -->
        <section class="advertisement_sec">
            <div class="marquee-container">
                <?php foreach ($advertisements as $fetch_ads):
                    $redirectUrl = !empty($fetch_ads['redirect_url']) ? $fetch_ads['redirect_url'] : '#';
                    $mainImage = !empty($fetch_ads['main_image']) ? $fetch_ads['main_image'] : 'assets/images/advertise-image2.png';
                    $prefix = $assetPrefix ?? ($pathPrefix ?? '');
                    if (strpos($mainImage, '/staging/') === 0) {
                        $mainImage = substr($mainImage, strlen('/staging/'));
                    }
                    if (strpos($mainImage, 'http://') !== 0 && strpos($mainImage, 'https://') !== 0) {
                        $cleanImg = ltrim($mainImage, '/');
                        if (file_exists(__DIR__ . '/../../../../' . $cleanImg)) {
                            $mainImage = $prefix . $cleanImg;
                        } elseif (file_exists(__DIR__ . '/../../../../admin/' . $cleanImg)) {
                            $mainImage = $prefix . 'admin/' . $cleanImg;
                        } else {
                            $mainImage = $prefix . $cleanImg;
                        }
                    }
                    $fallbackImage = $prefix . 'assets/images/advertise-image2.png';
                    ?>
                    <div class="ad-item">
                        <img src="<?= htmlspecialchars($mainImage) ?>" class="marquee_img" alt="advertisement" loading="lazy"
                            onerror="this.onerror=null; this.src='<?= $fallbackImage ?>';" />
                        <span class="marquee_text"><?= htmlspecialchars($fetch_ads['short_text']) ?>
                            <?php if (!empty($fetch_ads['redirect_url'])): ?>
                                <a href="<?= htmlspecialchars($redirectUrl) ?>" target="_blank" class="btn advertiselink_btn">Visit
                                    Now</a>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Main Header Section -->
    <section class="main_header" id="main_Header">
        <div class="cust_container">
            <div class="wraper">

                <a href="/index.php">
                    <figure class="Logo_area m-0">
                        <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                    </figure>
                </a>

                <div class="menubar_box">

                    <div class="top_area">
                        <a href="/index.php" class="Logo_area btn">
                            <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                        </a>
                    </div>

                    <ul class="navber_wrap">
                        <li><a href="/index.php" class="nav_link btn">Home</a></li>
                        <li><a href="#casaTournament_sec" class="nav_link btn">Tournament</a></li>
                        <li><a href="#homesotreid" class="nav_link btn">Store</a></li>
                        <li><a href="#gallerysecId" class="nav_link btn">Gallery</a></li>
                        <li><a href="#aboutusId" class="nav_link btn">About Us</a></li>
                        <li><a href="#contactUsId" class="nav_link btn">Contact</a></li>
                    </ul>

                    <ul class="socialIcon_all">
                        <li><a href="https://facebook.com/" class="link_" target="_blank" aria-label="Facebook"><i
                                    class="fa-brands fa-facebook-f"></i></a></li>
                        <li><a href="https://linkedin.com/" class="link_" target="_blank" aria-label="LinkedIn"><i
                                    class="fa-brands fa-linkedin-in"></i></a></li>
                        <li><a href="https://instagram.com/" class="link_" target="_blank" aria-label="Instagram"><i
                                    class="fa-brands fa-instagram"></i></a></li>
                        <li><a href="https://twitter.com/" class="link_" target="_blank" aria-label="Twitter"><i
                                    class="fa-brands fa-x-twitter"></i></a></li>
                    </ul>
                </div>

                <button class="responsivemenubar_btn btn" id="open_Sidebar" aria-label="Toggle Navigation Menu">
                    <span class="menuBar_line"></span>
                </button>

            </div>
        </div>
    </section>

    <!-- Register Modal -->
    <?php include __DIR__ . "/../../auth/resister.php"; ?>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotpwModal" tabindex="-1" aria-labelledby="forgotpwModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="forgotpwModalLabel">Forgot Password</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Oops! Forgot your password? <br />
                        Please contact the admin team and they will help you reset it quickly.<br />
                        <a href="mailto:info.casagames@gmail.com">📧 info.casagames@gmail.com</a>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>