<?php
session_start();
include('dbConnection.php');
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
$select_user = mysqli_query($conn, "select * from ca_users where ID='" . $_SESSION['user_id'] . "'");
$user = mysqli_fetch_assoc($select_user);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "header-links.php"; ?>

    <style>
        .marquee-container {
            height: 70px;
            overflow: hidden;
            position: relative;
            gap: 50px;
        }

        .ad-item {
            display: flex;
            position: absolute;
            inset: 0;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(18px) scale(0.98);
            transition: opacity 0.7s ease, transform 0.7s ease;
            width: 100%;
            pointer-events: none;
        }

        .ad-item.active {
            display: flex;
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
    </style>
</head>

<body>
    <?php
    if ($count_ads > 0) {
    ?>
        <!----advertisement-------->
        <section class="advertisement_sec">
            <div class="marquee-container">
                <?php foreach ($advertisements as $fetch_ads) {
                    $redirectUrl = !empty($fetch_ads['redirect_url']) ? $fetch_ads['redirect_url'] : '#';
                    $mainImage = !empty($fetch_ads['main_image']) ? $fetch_ads['main_image'] : 'assets/images/advertise-image2.png';

                    if ($mainImage !== 'assets/images/advertise-image2.png' && strpos($mainImage, 'http://') !== 0 && strpos($mainImage, 'https://') !== 0 && strpos($mainImage, '/') !== 0) {
                        $mainImage = '../admin/' . ltrim($mainImage, '/');
                    }
                ?>
                    <div class="ad-item">
                        <img src="<?= htmlspecialchars($mainImage) ?>" class="marquee_img" />
                        <span class="marquee_text"><?= htmlspecialchars($fetch_ads['short_text']) ?>
                            <?php if (!empty($fetch_ads['redirect_url'])) { ?>
                                <a href="<?= htmlspecialchars($redirectUrl) ?>" target="_blank" class="btn advertiselink_btn">Visit Now</a>
                            <?php } ?>
                        </span>
                    </div>
                <?php } ?>
            </div>
        </section>

    <?php
    }
    ?>


    <!-----advertisement old---->
    <!-- <section class="advertisement_sec">
        <marquee class="marquee py-1" onmouseover="this.stop();" onmouseout="this.start();">
            <//?php
            foreach ($advertisements as $fetch_ads) {
                $redirectUrl = !empty($fetch_ads['redirect_url']) ? $fetch_ads['redirect_url'] : '#';
                $mainImage = !empty($fetch_ads['main_image']) ? $fetch_ads['main_image'] : 'assets/images/advertise-image2.png';

                if ($mainImage !== 'assets/images/advertise-image2.png' && strpos($mainImage, 'http://') !== 0 && strpos($mainImage, 'https://') !== 0 && strpos($mainImage, '/') !== 0) {
                    $mainImage = '../admin/' . ltrim($mainImage, '/');
                }
            ?>
                <img src="<//?= htmlspecialchars($mainImage, ENT_QUOTES, 'UTF-8') ?>" class="marquee_img" alt="advertisement" />
                <span class="marquee_text"><//?= htmlspecialchars($fetch_ads['short_text'], ENT_QUOTES, 'UTF-8') ?></span>
                <//?php if (!empty($fetch_ads['redirect_url'])) { ?>
                    <a href="<//?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn advertiselink_btn">Visit Now</a>
                <//?php } ?>
            <//?php
            }
            ?>
        </marquee>
    </section> -->

    <!------Main Header------->
    <section class="main_header" id="main_Header">
        <div class="cust_container">
            <div class="wraper">

                <a href="index.php">
                    <figure class="Logo_area m-0">
                        <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                    </figure>
                </a>

                <div class="menubar_box">

                    <div class="top_area">
                        <a href="index.php" class="Logo_area btn">
                            <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                        </a>
                    </div>

                    <ul class="navber_wrap">
                        <li><a href="index.php" class="nav_link btn">Home</a></li>
                        <li><a href="#casaTournament_sec" class="nav_link btn">Tournament</a></li>
                        <li><a href="#homesotreid" class="nav_link btn">Store</a></li>
                        <li><a href="#gallerysecId" class="nav_link btn">Gallery</a></li>
                        <li><a href="#aboutusId" class="nav_link btn">About Us</a></li>
                        <!-- <li><a href="#eventCard_post" class="nav_link btn">Event</a></li> -->

                        <!-- <li><a href="#" class="nav_link btn">Sports</a>
                            <span class="icon"></span>
                            <ul class="sub-menu">
                                <li><a href="#">Badminton</a></li>
                                <li><a href="#">Football (Coming Soon)</a></li>
                                <li><a href="#">Cricket (Coming Soon)</a></li>
                                <li><a href="#">Swimming (Coming Soon)</a></li>
                                <li><a href="#">Tennis (Coming Soon)</a></li>
                                <li><a href="#">Cricket (Coming Soon)</a></li>
                                <li><a href="#">Table Tennis (Coming Soon)</a></li>
                            </ul>
                        </li> -->

                        <!-- <li><a href="#casaStadium_sec" class="nav_link btn">Stadium</a></li> -->
                        <!--<li><a href="player-dashboard.php" class="nav_link btn <//?=isset($_SESSION['user_id']) && $_SESSION['usertype']=='Player' ?'':'disabled' ?>">Play</a></li>-->
                        <!--<li><a href="host-dashboard.php" class="nav_link btn <//?=isset($_SESSION['user_id']) && $_SESSION['usertype']=='Host' || $_SESSION['usertype']=='Trainer' ?'':'disabled' ?>">Host/Trainer</a></li>-->
                        <!--<li><a href="#" class="nav_link btn <//?=isset($_SESSION['user_id']) && $_SESSION['usertype']=='Player' ?'':'disabled' ?>">Train</a></li>-->


                        <li><a href="#contactUsId" class="nav_link btn">Contact</a></li>
                    </ul>

                    <ul class="socialIcon_all">
                        <li><a href="https://facebook.com/" class="link_" target="_blank"><i class="fa-brands fa-facebook-f"></i></a></li>
                        <li><a href="https://linkedin.com/" class="link_" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a></li>
                        <li><a href="https://instagram.com/" class="link_" target="_blank"><i class="fa-brands fa-instagram"></i></a></li>
                        <li><a href="https://twitter.com/" class="link_" target="_blank"><i class="fa-brands fa-x-twitter"></i></a></li>
                    </ul>
                </div>

                <!-- <div>
                    <a href="#" id="resisterEvent" class="getQuote_btn btn">
                        <i class="fa-solid fa-file-pen"></i>
                        <span>Register Request</span>
                    </a>
                </div> -->

                <button class="responsivemenubar_btn btn" id="open_Sidebar">
                    <span class="menuBar_line"></span>
                </button>


            </div>
        </div>
    </section>


    <!-------Resister-Modal------->

    <?php include "includes/Auth/resister.php"; ?>

    <!-------Resister-Modal-End------->


    <!-----Forgot Password modal-------->
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
