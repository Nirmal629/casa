<?php
session_start();
// print_r($_SESSION);exit;
?>
<div class="inner-wrapper">
	<!-- start: sidebar -->
	<aside id="sidebar-left" class="sidebar-left">

		<div class="sidebar-header">
			<!--<div class="sidebar-title">-->
			<!--	Navigation-->
			<!--</div>-->
			
			<div id="userbox" class="userbox">
				<a href="#" data-toggle="dropdown">
					<figure class="profile-picture">
						<img src="assets/images/!logged-user.jpg" alt="Joseph Doe" class="img-circle" data-lock-picture="assets/images/!logged-user.jpg" />
					</figure>
					<div class="profile-info" data-lock-name="John Doe" data-lock-email="johndoe@okler.com">
						<span class="name">  <?php
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
							<li>
								<a href="dashboard.php">
									<i class="fa fa-home" aria-hidden="true"></i>
									<span>Dashboard</span>
								</a>
							</li>
							<li class="nav-parent">
								
								<!--    Commented the existing User code by Anurag to remove multi clicks-->
								<!--<a href="javascript:void(0)">-->
									<!--<i class="fa fa-list-alt" aria-hidden="true"></i>-->
									<!--<span>User</span>-->
								<!--</a>-->
								<!--<ul class="nav nav-children">-->
									<!--<li>-->
									<!--    <a href="add_user.php">-->
									<!--        Add User-->
									<!--    </a>-->
									<!--</li>-->
									<!--<li>-->
										<!--<a href="manage_user.php">-->
											<!--List User-->
										<!--</a>-->
									<!--</li>-->
								<!--</ul>-->
								
								<!--    Clean User code added by Anurag to remove multi clicks-->
								<li>
                                    <a href="manage_user.php">
                                        <i class="fa fa-list-alt" aria-hidden="true"></i>
                                        <span>User</span>
                                    </a>
                                </li>

								<!--                 <ul class="nav nav-children">-->
								<!--                     <li>-->
								<!--                         <a href="add_ads.php">-->
								<!--                             Add Ads-->
								<!--                         </a>-->
								<!--                     </li>-->
								<!--<li>-->
								<!--                         <a href="manage_ads.php">-->
								<!--                             List Ads-->
								<!--                         </a>-->
								<!--                     </li>-->
								<!--                 </ul>-->
							</li>
							<li class="nav-parent">
								<a href="javascript:void(0)">
									<i class="fa fa-list-alt" aria-hidden="true"></i>
									<span>Advertisement</span>
								</a>

								<ul class="nav nav-children">
									<li>
										<a href="add_ads.php">
											Add Ads
										</a>
									</li>
									<li>
										<a href="manage_ads.php">
											List Ads
										</a>
									</li>
								</ul>
							</li>
							<li class="nav-parent">
								<a href="javascript:void(0)">
									<i class="fa fa-list-alt" aria-hidden="true"></i>
									<span>Product</span>
								</a>

								<ul class="nav nav-children">
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
									<li>
										<a href="add_product.php">
											Add Product
										</a>
									</li>
									<li>
										<a href="manage_products.php">
											List Product
										</a>
									</li>
								</ul>
							</li>
							<!--<li class="nav-parent">-->
							<!--	<a href="javascript:void(0)">-->
							<!--		<i class="fa fa-list-alt" aria-hidden="true"></i>-->
							<!--		<span>Products</span>-->
							<!--	</a>-->

							<!--	<ul class="nav nav-children">-->
							<!--		<li>-->
							<!--			<a href="add_product.php">-->
							<!--				Add Product-->
							<!--			</a>-->
							<!--		</li>-->
							<!--		<li>-->
							<!--			<a href="manage_products.php">-->
							<!--				List Product-->
							<!--			</a>-->
							<!--		</li>-->
							<!--	</ul>-->
							<!--</li>-->
							<li class="nav-parent">
								<a href="manage_order.php">
									<i class="fa fa-list-alt" aria-hidden="true"></i>
									<span>Order</span>
								</a>

								<!--<ul class="nav nav-children">-->

								<!--	<li>-->
								<!--		<a href="manage_order.php">-->
								<!--			Manage Order-->
								<!--		</a>-->
								<!--	</li>-->
								<!--	<li>-->
								<!--		<a href="manual_order.php">-->
								<!--			Manual Order-->
								<!--		</a>-->
								<!--	</li>-->
								<!--</ul>-->
							</li>
							<li class="nav-parent">
								<a href="javascript:void(0)">
									<i class="fa fa-list-alt" aria-hidden="true"></i>
									<span>Contact Us</span>
								</a>

								<ul class="nav nav-children">
									<li>
										<a href="contact_list.php">
											List Contact us
										</a>
									</li>
								</ul>
							</li>
							<li class="nav-parent">
								<a href="javascript:void(0)">
									<i class="fa fa-list-alt" aria-hidden="true"></i>
									<span>Event Management</span>
								</a>

								<ul class="nav nav-children">

									<li>
										<a href="event_description.php">
											Event Description
										</a>
									</li>
									<li>
										<a href="event_venue.php">
											Event Venue
										</a>
									</li>
									<li>
										<a href="event_category.php">
											Event Category
										</a>
									</li>
									<li>
										<a href="gallery.php">
											Gallery
										</a>
									</li>
								</ul>
							</li>

							<li>
                                <a href="tournaments_list.php">
                                    <i class="fa fa-list-alt" aria-hidden="true"></i>
                                    <span>Tournament</span>
                                </a>
                            </li>						
						
							<!--<li class="nav-parent">-->
							<!--	<a href="tournaments_list.php">-->
							<!--		<i class="fa fa-list-alt" aria-hidden="true"></i>-->
							<!--		<span>Tournament</span>-->
							<!--	</a>-->
							<!--	<ul class="nav nav-children">-->
							<!--		<li>-->
       <!--                                 <a href="add_event.php">-->
		     <!--                               Add Tournament-->
		     <!--                           </a>-->
		     <!--                       </li>-->
		     <!--                       <li>-->
							
		     <!--                           <a href="tournaments_list.php">-->
		     <!--                               List Tournament-->
		     <!--                           </a>-->
		     <!--                       </li>-->
		     <!--                       <li>-->
		     <!--                           <a href="enrolled_tournaments.php">-->
		     <!--                               Enrolled User-->
		     <!--                           </a>-->
		     <!--                       </li>-->
		     <!--                       <li>-->
		     <!--                           <a href="registration_message.php">-->
		     <!--                               Registration Message-->
		     <!--                           </a>-->
		     <!--                       </li>-->
							<!--		<li>-->
		     <!--                           <a href="tournament_logindetails.php">-->
		     <!--                               Tournament Access & Login Details-->
		     <!--                           </a>-->
		     <!--                       </li>-->
		     <!--                   </ul>-->
							<!--</li>-->

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