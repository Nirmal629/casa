<?php
session_start();
include('dbConnection.php');
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
// print_r($_SESSION);
// exit;


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
                 <div class="d-flex align-items-center gap-1">
                    <a href="addToCart.php" class="btn headeraddtocart_btn">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="count"><?=$cartCount?></span>
                    </a>
                </div>
                <!-----Account----->

            </div>
        </div>
    </section>