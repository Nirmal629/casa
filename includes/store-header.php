<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'dbConnection.php';

$cartCount = isset($_SESSION['cart'])
    ? array_sum(array_column($_SESSION['cart'], 'quantity'))
    : 0;

$user = null;
if (isset($_SESSION['user_id'])) {
    $res = mysqli_query($conn, "SELECT * FROM ca_users WHERE ID='{$_SESSION['user_id']}'");
    $user = mysqli_fetch_assoc($res);
}
?>
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

                    <li><a href="product-listing.php" class="nav_link btn"><span class="">Store</span></a></li>
                    <li><a href="<?=isset($_SESSION['usertype']) && $_SESSION['usertype']=='Host'?'host-dashboard.php':'player-dashboard.php'?>" class="nav_link btn"><span class="">Play</span></a></li>
                </ul>

                <!---Account------>
                 
                <!-----Account----->
                    <div  class="d-flex align-items-center gap-1 justify-content-end">
                    <div class="d-flex align-items-center gap-1" style="<?=isset($_SESSION['user_id'])?'display:block':'visibility:hidden'?>">
                        <a href="addToCart.php" class="btn headeraddtocart_btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="count"><?=$cartCount?></span>
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-1" style="<?=isset($_SESSION['user_id'])?'display:block':'visibility:hidden'?>">
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
</div>
            </div>
        </div>
    </section>