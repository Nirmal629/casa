<?php

session_start();

if(!isset($_SESSION['user_id']))

{

    header("Location: index.php");

    exit();

}

?>

<!doctype html>

<html class="fixed">

	<head>



		<!-- Basic -->

		<meta charset="UTF-8">



		<title>Casa</title>

		<meta name="keywords" content="HTML5 Admin Template" />

		<meta name="description" content="Casa">

		<meta name="author" content="okler.net">



		<!-- Mobile Metas -->

		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />



		<!-- Web Fonts  -->

		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">



		<!-- Vendor CSS -->

		<link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.css" />



		<link rel="stylesheet" href="assets/vendor/font-awesome/css/font-awesome.css" />

		<link rel="stylesheet" href="assets/vendor/magnific-popup/magnific-popup.css" />

		<link rel="stylesheet" href="assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css" />



		<!-- Specific Page Vendor CSS -->

		<link rel="stylesheet" href="assets/vendor/jquery-ui/jquery-ui.css" />

		<link rel="stylesheet" href="assets/vendor/jquery-ui/jquery-ui.theme.css" />

		<link rel="stylesheet" href="assets/vendor/bootstrap-multiselect/bootstrap-multiselect.css" />

		<link rel="stylesheet" href="assets/vendor/morris.js/morris.css" />



		<!-- Theme CSS -->
		<link rel="stylesheet" href="assets/stylesheets/theme.css?v=2" />



		<!-- Skin CSS -->

		<link rel="stylesheet" href="assets/stylesheets/skins/default.css" />



		<!-- Theme Custom CSS -->

		<link rel="stylesheet" href="assets/stylesheets/theme-custom.css">



		<!-- Head Libs -->

		<script src="assets/vendor/modernizr/modernizr.js"></script>

		<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">





	</head>

	<body>

		<section class="body">



			<!-- start: header -->
<!-- 
			<header class="header">

				<div class="logo-container">

					<a href="../1.7.0" class="logo">

						<img src="assets/images/logo.png" width="75" height="35" alt="Casa Admin" />

					</a>

					<div class="visible-xs toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">

						<i class="fa fa-bars" aria-label="Toggle sidebar"></i>

					</div>

				</div>


				<div class="header-right">

			

					<form action="pages-search-results.html" class="search nav-form">

						<div class="input-group input-search">

							<input type="text" class="form-control" name="q" id="q" placeholder="Search...">

							<span class="input-group-btn">

								<button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>

							</span>

						</div>

					</form>

			

					<span class="separator"></span>

			

					<div id="userbox" class="userbox">

						<a href="#" data-toggle="dropdown">

							<figure class="profile-picture">

								<img src="assets/images/!logged-user.jpg" alt="Joseph Doe" class="img-circle" data-lock-picture="assets/images/!logged-user.jpg" />

							</figure>

							<div class="profile-info" data-lock-name="John Doe" data-lock-email="johndoe@okler.com">

								<span class="name">  <//?php

				                    if($_SESSION['type'] == 'SUPER')

				                    {

				                    echo "Anurag Gupta";

				                    }

				                    else

				                    {

				                        echo "Partner";

				                    }

				                    ?>

				                    </span>

								<span class="role">administrator</span>

							</div>

			

							<i class="fa custom-caret"></i>

						</a>

			

						<div class="dropdown-menu">

							<ul class="list-unstyled">

								<li class="divider"></li>

								<li>

									<a role="menuitem" tabindex="-1" href="#"><i class="fa fa-user"></i> My Profile</a>

								</li>

								<li>

									<a role="menuitem" tabindex="-1" href="#" data-lock-screen="true"><i class="fa fa-lock"></i> Lock Screen</a>

								</li>

								<li>

									<a role="menuitem" tabindex="-1" href="logout.php"><i class="fa fa-power-off"></i> Logout</a>

								</li>

							</ul>

						</div>

					</div>

				</div>

			</header> -->

			<!-- end: header -->