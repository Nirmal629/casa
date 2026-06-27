<?php

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// print_r($_SESSION);exit;

?>

<div class="inner-wrapper">

	<!-- start: sidebar -->

	<aside id="sidebar-left" class="sidebar-left">



		<div class="sidebar-header">

			<!-- The login and logoff navigation in the sidebar-->

			<div id="userbox" class="userbox">

				<a href="#" data-toggle="dropdown">

					<figure class="profile-picture">

						<img src="assets/images/!logged-user.jpg" alt="Joseph Doe" class="img-circle" data-lock-picture="assets/images/!logged-user.jpg" />

					</figure>

					<div class="profile-info" data-lock-name="John Doe" data-lock-email="johndoe@okler.com">

						<span class="name"> <?php

											if ($_SESSION['type'] == 'SUPER') {

												echo "Anurag Gupta";
											} else {

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

						<li><a role="menuitem" tabindex="-1" href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>

					</ul>

				</div>

			</div>



			<div class="sidebar-toggle hidden-xs" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle">

				<i class="fa fa-bars" aria-label="Toggle sidebar"></i>

			</div>

		</div>







		<div class="nano">

			<div class="nano-content">

				<nav id="menu" class="nav-main" role="navigation">



					<ul class="nav nav-main">



						<?php

						if ($_SESSION['type'] == 'SUPER') {

						?>

							<!-- Main Dashboard Section-->

							<li>

								<a href="dashboard.php">

									<i class="fa fa-home" aria-hidden="true"></i>

									<span>Dashboard</span>

								</a>

							</li>

							<!-- User Section -->

							<li class="nav-parent">

							<li>

								<a href="manage_user.php">

									<i class="fa-solid fa-user-tie" aria-hidden="true"></i>

									<span>User</span>

								</a>

							</li>

							</li>

							<!-- Tournament Section -->

							<li>

								<a href="tournaments_list.php">

									<i class="fa-solid fa-chess" aria-hidden="true"></i>

									<span>Tournament</span>

								</a>

							</li>

							<!-- Product Section -->

							<li class="nav-parent">

								<a href="javascript:void(0)">

									<i class="fa-solid fa-baseball" aria-hidden="true"></i>

									<span>Product</span>

								</a>

								<ul class="nav nav-children">
									<li>

										<a href="manage_products.php">

											All Product

										</a>

									</li>

									<li>

										<a href="manage_department.php">

											Department

										</a>

									</li>

									<li>

										<a href="manage_product_type.php">

											Product Type

										</a>

									</li>

								</ul>

							</li>

							<!-- Order Section -->

							<li class="nav-parent">

							<li>

								<a href="manage_order.php">

									<i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>

									<span>Order</span>

								</a>

							</li>

							</li>

							<!-- Host Section -->

							<li class="nav-parent">

							<li>

								<!-- Pending Section -->

								<a href="admin_host.php">

									<i class="fa-solid fa-user-tag" aria-hidden="true"></i>

									<span>Host</span>

								</a>

							</li>

							</li>

							<!-- Advertisement Section -->

							<li class="nav-parent">
							<li>
								<a href="manage_adds.php">

									<i class="fa-solid fa-rectangle-ad" aria-hidden="true"></i>

									<span>Advertisement</span>

								</a>
							</li>

							</li>

							<!-- Hero banner Section -->

							<li class="nav-parent">

							<li>

								<a href="herobanner_list.php">

									<i class="fa-solid fa-image" aria-hidden="true"></i>

									<span>Hero Banner</span>

								</a>

							</li>

							</li>

							<!-- AboutUs Section -->

							<li class="nav-parent">
							<li>
								<a href="media_list.php">

									<i class="fa-solid fa-podcast" aria-hidden="true"></i>

									<span>Media</span>

								</a>
							</li>
							</li>

							<!---Review-->
							<li class="nav-parent">
							<li>
								<a href="aboutus_review.php">

									<i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>

									<span>Review</span>

								</a>
							</li>
							</li>

							<!-- Contact Us Section -->

							<li class="nav-parent">

							<li>

								<a href="contact_list.php">

									<i class="fa-solid fa-address-book" aria-hidden="true"></i>

									<span>ContactUs</span>

								</a>

							</li>

							</li>

							<!-- Event Management Section -->

							<li class="nav-parent">

								<a href="javascript:void(0)">

									<i class="fa fa-list-alt" aria-hidden="true"></i>

									<span>EventManagement</span>

								</a>

								<ul class="nav nav-children">

									<li>

										<a href="event_description.php">

											EventDescription

										</a>

									</li>

									<li>

										<a href="event_venue.php">

											EventVenue

										</a>

									</li>

									<li>

										<a href="event_category.php">

											EventCategory

										</a>

									</li>

								</ul>

							</li>

						<?php

						} else {

						?>

							<li class="nav-parent">

								<a href="javascript:void(0)">

									<i class="fa fa-list-alt" aria-hidden="true"></i>

									<span>Order List</span>

								</a>



								<ul class="nav nav-children">



									<li>

										<a href="manage_order.php">

											Manage Order

										</a>

									</li>

									<li>

										<a href="manual_order.php">

											Manual Order </a>

									</li>

								</ul>

							</li>

						<?php

						}

						?>

					</ul>

				</nav>

			</div>

			<script>
				// Maintain Scroll Position

				if (typeof localStorage !== 'undefined') {

					if (localStorage.getItem('sidebar-left-position') !== null) {

						var initialPosition = localStorage.getItem('sidebar-left-position'),

							sidebarLeft = document.querySelector('#sidebar-left .nano-content');



						sidebarLeft.scrollTop = initialPosition;

					}

				}
			</script>

		</div>

	</aside>

	<!-- end: sidebar -->