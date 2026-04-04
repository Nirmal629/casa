<?php
session_start();
include('dbConnection.php');
$select_ads = mysqli_query($conn,"select * from ca_ads where 1 order by ID desc");
$count_ads = mysqli_num_rows($select_ads);
$select_user = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
$user = mysqli_fetch_assoc($select_user);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "header-links.php"; ?>
</head>

<body>
<?php
if($count_ads > 0)
{
    ?>
    <!----advertisement-------->
    <section class="advertisement_sec">
            <marquee class="marquee" onmouseover="this.stop();" onmouseout="this.start();">
                <!--<img src="assets/images/advertise-image.png" class="marquee_img" alt="image" />-->
                <!--<span class="marquee_text">[Coming soon] A streamlined method for hosting tournaments with a live dashboard tracking progress</span>-->
                <!--<a href="#" target="_blank" class="btn advertiselink_btn">Visit Now</a>-->
                <?php
                while($fetch_ads= mysqli_fetch_assoc($select_ads))
                {
                ?>
                    <img src="assets/images/advertise-image2.png" class="marquee_img" alt="image" style="visibility:hidden"/>
                    <span class="marquee_text"><?=$fetch_ads['DESCRIPTION']?></span>
                    <!--<a href="#" target="_blank" class="btn advertiselink_btn" style="visibility:hidden">Visit Now</a>-->
                <?php
                }
                ?>
            </marquee>
    </section>
<?php
}
?>

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
                        <li><a href="about-us.php" class="nav_link btn">About Us</a></li>
                        <!--<li><a href="#eventCard_post" class="nav_link btn">Event</a></li>-->
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
                        <!--<li><a href="#casaTournament_sec" class="nav_link btn">Tournament</a></li>-->
                        <!--<li><a href="#casaStadium_sec" class="nav_link btn">Stadium</a></li>-->
                        <!--<li><a href="player-dashboard.php" class="nav_link btn <//?=isset($_SESSION['user_id']) && $_SESSION['usertype']=='Player' ?'':'disabled' ?>">Play</a></li>-->
                        <!--<li><a href="host-dashboard.php" class="nav_link btn <//?=isset($_SESSION['user_id']) && $_SESSION['usertype']=='Host' || $_SESSION['usertype']=='Trainer' ?'':'disabled' ?>">Host/Trainer</a></li>-->
                        <!--<li><a href="#" class="nav_link btn <//?=isset($_SESSION['user_id']) && $_SESSION['usertype']=='Player' ?'':'disabled' ?>">Train</a></li>-->
                        <li><a href="gallery.php" class="nav_link btn">Gallery</a></li>
                        <li><a href="product-listing.php" class="nav_link btn">Store</a></li>
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