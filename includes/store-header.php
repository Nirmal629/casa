<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../dbConnection.php';

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

                <div class="site-header-left">
                    <button type="button" class="site-back-btn" aria-label="Go back to previous page" title="Go back">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>

                    <a href="index.php">
                        <figure class="Logo_area m-0">
                            <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                        </figure>
                    </a>
                </div>

                <ul class="innerpagemenu_wrap">
                    <li><a href="index.php" class="nav_link btn active">
                            <span class="">Home</span>
                        </a>
                    </li>

                    <li><a href="product-listing.php" class="nav_link btn"><span class="">Store</span></a></li>
                    <li><a href="<?= isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'Host' ? 'host-dashboard.php' : 'player-dashboard.php' ?>"
                            class="nav_link btn"><span class="">Play</span></a></li>
                </ul>

                <!---Account------>

                <!-----Account----->
                <div class="d-flex align-items-center gap-1 justify-content-end">
                    <div class="d-flex align-items-center gap-1"
                        style="<?= isset($_SESSION['user_id']) ? 'display:block' : 'visibility:hidden' ?>">
                        <a href="addToCart.php" class="btn headeraddtocart_btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="count">
                                <?= $cartCount ?>
                            </span>
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-1"
                        style="<?= isset($_SESSION['user_id']) ? 'display:block' : 'visibility:hidden' ?>">
                        <!--<a href="addToCart.php" class="btn headeraddtocart_btn">-->
                        <!--    <i class="fa-solid fa-cart-shopping"></i>-->
                        <!--    <span class="count">12</span>-->
                        <!--</a>-->
                        <div style="position: relative;">
                            <div class="user-profile-dropdown">
                                <div class="avatar-trigger" id="profileDropdownTrigger">
                                    <img src="<?= $user['PROFILE_IMAGE'] != '' ? 'profile_img/' . $user['PROFILE_IMAGE'] : 'assets/images/profile.jpg' ?>"
                                        alt="avatar">
                                </div>
                                <div class="dropdown-menu-container" id="profileDropdownMenu">
                                    <div class="dropdown-user-info">
                                        <span class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                                    </div>
                                    <ul class="dropdown-menu-list">
                                        <li><a href="player-profile.php"><i class="fa-regular fa-user"></i>My
                                                Profile</a></li>
                                        <li><a href="my-order.php"><i class="fa-regular fa-user"></i>My Order</a></li>
                                        <li><a href="logout.php"><i
                                                    class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Profile Dropdown Toggle
            var trigger = document.getElementById('profileDropdownTrigger');
            var menu = document.getElementById('profileDropdownMenu');
            if (trigger && menu) {
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menu.classList.toggle('show');
                });

                document.addEventListener('click', function (e) {
                    if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
            }

            var siteBackButton = document.querySelector('.site-back-btn');
            if (!siteBackButton) {
                return;
            }

            siteBackButton.addEventListener('click', function () {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                window.location.href = 'player-hub.php';
            });
        });
    </script>
