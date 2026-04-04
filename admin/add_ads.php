<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

if(isset($_POST['submit']))
{
    // echo "insert into ca_ads(DESCRIPTION) values('".trim($_POST['name'])."')";
    $insert  = mysqli_query($conn,"insert into ca_ads(DESCRIPTION) values('".mysqli_real_escape_string($conn,trim($_POST['name']))."')");
    if($insert)
    {
                echo "<script>alert('Data inserted successfully!'); window.location.href='manage_ads.php';</script>";

    }
    else
    {
        echo mysqli_error();
    }
}



?>
<section role="main" class="content-body">
					<header class="page-header">
						<h2>Add Ads</h2>
					
						<div class="right-wrapper pull-right">
							<ol class="breadcrumbs">
								<li>
									<a href="dashboard.php">
										<i class="fa fa-home"></i>
									</a>
								</li>
								<li><span>Manage User</span></li>
								<li><span>Add User</span></li>
							</ol>
					
							<a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
						</div>
					</header>

						<div class="row">
							<div class="col-lg-12">
								<section class="panel">
									<header class="panel-heading">
										<div class="panel-actions">
											<a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
											<a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
										</div>
						
										<h2 class="panel-title">Add Ads</h2>
									</header>
									<div class="panel-body">
                    <form  method="POST">
                        <div class="form-group">
                            <label style="font-weight:bold"  for="name">Name<span>*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" value="" required>
                        </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" name="submit">Save</button>
                    </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>
<?php
include('footer.php');
?>
