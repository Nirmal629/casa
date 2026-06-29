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

                    <!-- <li><a href="index.php" class="nav_link btn active">

                            <span class="">Home</span>

                        </a>

                    </li> -->

                    <?php

                    if($_SESSION['usertype']=='Player')

                    {

                    ?>

                    <li><a href="player-hub.php" class="nav_link btn">

                            <span class="">The Player hub</span>

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

                    <li><a href="product-listing.php" class="nav_link btn"><span class="">The Casa Store</span></a></li>

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

                                <li><a href="my-order.php"><i class="fa-regular fa-user"></i>My Order</a></li>

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

    <div id="profileModal" class="editProfileModal d-none">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-body">
                    <!-- Profile Picture -->
                    <div class="profile-pic-section">
                        <div class="profile-pic-container">
                            <div class="profile-pic">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="pic-actions">
                            <button class="pic-btn" title="Edit Picture">
                                <i class="fas fa-pencil"></i>
                            </button>
                            <button class="pic-btn delete" title="Delete Picture">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Form Fields -->
                    <form id="profileForm">
                        <!-- Name (Non-editable) -->
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="field-input-readonly" value="John David Smith" readonly>
                        </div>

                        <!-- Email (Non-editable) + Permission -->
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <input type="email" class="field-input-readonly" style="flex: 1;" value="john.smith@example.com" readonly>
                                <div class="permission-group" style="margin-top: 3px; gap: 12px;">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="emailYes" name="emailPermission" value="yes" checked>
                                        <label for="emailYes">Yes</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="emailNo" name="emailPermission" value="no">
                                        <label for="emailNo">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact (Non-editable) + Permissions -->
                        <div class="form-group">
                            <label class="form-label">Contact</label>
                            <input type="text" class="field-input-readonly" value="+1 (555) 123-4567" readonly style="margin-bottom: 12px;">
                            
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <div class="permission-group">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="callYes" name="callPermission" value="yes" checked>
                                        <label for="callYes">Call</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="callNo" name="callPermission" value="no">
                                        <label for="callNo">No Call</label>
                                    </div>
                                </div>

                                <div class="permission-group">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="textYes" name="textPermission" value="yes" checked>
                                        <label for="textYes">Text</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="textNo" name="textPermission" value="no">
                                        <label for="textNo">No Text</label>
                                    </div>
                                </div>

                                <div class="permission-group">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="chatYes" name="chatPermission" value="yes" checked>
                                        <label for="chatYes">Chat</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="chatNo" name="chatPermission" value="no">
                                        <label for="chatNo">No Chat</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DOB (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <div class="editable-field">
                                <input type="date" class="field-input" id="dobInput" disabled value="1990-05-15">
                                <button type="button" class="edit-btn" id="dobEditBtn" onclick="toggleEdit('dob')">
                                    <i class="fas fa-pencil"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Gender (Non-editable) -->
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <input type="text" class="field-input-readonly" value="Male" readonly>
                        </div>

                        <!-- Level (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Level</label>
                            <div class="editable-field">
                                <select class="field-select" id="levelInput" disabled>
                                    <option>Select Level</option>
                                    <option selected>Intermediate</option>
                                    <option>Beginner</option>
                                    <option>Advanced</option>
                                    <option>Expert</option>
                                </select>
                                <button type="button" class="edit-btn" id="levelEditBtn" onclick="toggleEdit('level')">
                                    <i class="fas fa-pencil"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Location (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <div class="select-group">
                                <div class="editable-field">
                                    <select class="field-select" id="countryInput" disabled>
                                        <option>Country</option>
                                        <option selected>Canada</option>
                                        <option>United States</option>
                                        <option>United Kingdom</option>
                                        <option>Australia</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="countryEditBtn" onclick="toggleEdit('country')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>

                                <div class="editable-field">
                                    <select class="field-select" id="provinceInput" disabled>
                                        <option>Province</option>
                                        <option selected>Ontario</option>
                                        <option>British Columbia</option>
                                        <option>Alberta</option>
                                        <option>Quebec</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="provinceEditBtn" onclick="toggleEdit('province')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>

                                <div class="editable-field">
                                    <select class="field-select" id="cityInput" disabled>
                                        <option>City</option>
                                        <option selected>Toronto</option>
                                        <option>Vancouver</option>
                                        <option>Calgary</option>
                                        <option>Montreal</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="cityEditBtn" onclick="toggleEdit('city')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>

                                <div class="editable-field">
                                    <select class="field-select" id="areaInput" disabled>
                                        <option>Area</option>
                                        <option selected>Downtown</option>
                                        <option>Midtown</option>
                                        <option>Uptown</option>
                                        <option>Suburbs</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="areaEditBtn" onclick="toggleEdit('area')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="handleCancel()">Cancel</button>
                    <button class="btn btn-primary" onclick="handleSubmit()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>