<?php
session_start();
include('dbConnection.php');
// print_r($_SESSION);exit;
if(!$_SESSION['user_id'])
{
    header('location:index.php');
    exit;
}
$select_user = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
$user = mysqli_fetch_assoc($select_user);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "header-links.php"; ?>
</head>

<body>
    <!------Main Header------->
    <section class="main_header innerpageHeader" id="main_Header">
        <div class="cust_container">
            <div class="wraper">

                <a href="index.php">
                    <figure class="Logo_area m-0">
                        <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                    </figure>
                </a>

                <ul class="innerpagemenu_wrap">
                    <li><a href="index.php" class="nav_link btn active">
                            <span class="">Home</span>
                        </a>
                    </li>
                    <?php
                    if($_SESSION['usertype']=='Player')
                    {
                    ?>
                    <li><a href="player-dashboard.php" class="nav_link btn">
                            <span class="">Play</span>
                        </a>
                    </li>
                    <?php
                    }
                     if($_SESSION['usertype']=='Host' || $_SESSION['usertype']=='Trainer')
                    {
                    ?>
                    <li><a href="host-dashboard.php" class="nav_link btn">
                            <span class="">Host/Trainer</span>
                        </a>
                    </li>
                    <?php
                    }
                    // if($_SESSION['usertype']=='Trainer')
                    // {
                    ?>
                    <!--<li><a href="train-dashboard.php" class="nav_link btn">-->
                    <!--        <span class="">Train</span>-->
                    <!--    </a>-->
                    <!--</li>-->
                    <?php
                    
                    // }
                    ?>
                    <li><a href="product-listing.php" class="nav_link btn"><span class="">Store</span></a></li>
                </ul>

                <!---Account------>
                 <div class="d-flex align-items-center gap-1">
                    <!--<a href="addToCart.php" class="btn headeraddtocart_btn">-->
                    <!--    <i class="fa-solid fa-cart-shopping"></i>-->
                    <!--    <span class="count">12</span>-->
                    <!--</a>-->
                    <div style="position: relative;">
                        <div class="navigation">
                            <div class="user-box">
                                <div class="image-box">
                                    <img src="<?=$user['PROFILE_IMAGE']!=''?'profile_img/'.$user['PROFILE_IMAGE']:'assets/images/profile.jpg'?>" alt="avatar">
                                </div>
                                <p class="username"><?=$_SESSION['name']?></p>
                            </div>
                            <div class="menu-toggle"></div>
                            <ul class="menu">
                                <li><a href="player-profile.php"><i class="fa-regular fa-user"></i>My Profile</a></li>
                                <!--<li><a href="notification.php"><i class="fa-regular fa-bell"></i>Notification</a></li>-->
                                <li><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-----Account----->

            </div>
        </div>
    </section>