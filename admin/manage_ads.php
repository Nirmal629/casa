<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>
<section role="main" class="content-body">
					<header class="page-header">
						<h2>List User's</h2>
					
						<div class="right-wrapper pull-right">
							<ol class="breadcrumbs">
								<li>
									<a href="index.php">
										<i class="fa fa-home"></i>
									</a>
								</li>
								<li><span>Manage Ads</span></li>
								<li><span>List Ads</span></li>
							</ol>
					
							<a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
						</div>
					</header>

					<!-- start: page -->
						<section class="panel">
							<header class="panel-heading">
								<div class="panel-actions">
									<a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
									<a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
								</div>
						
								<h2 class="panel-title">List Ads</h2>
							</header>
							<div class="panel-body">
							    <div style="overflow-x: auto;">
								<table class="table table-bordered table-striped mb-none" id="datatable-default">
									<thead>
										<tr>
										    <th>SL NO</th>
											<th>ADS</th>
											
											<th>ACTION</th>
											
										</tr>
									</thead>
									<tbody>
										<?php
                                        $sql = "SELECT * FROM ca_ads WHERE 1 ORDER BY ID DESC";
                                        $result = $conn->query($sql);
                                        $i = 1;
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $i . "</td>";
                                                echo "<td>" . $row['DESCRIPTION'] . "</td>";
                                                echo "<td>";
                                                echo " <button class='btn btn-danger delete-user' 
                                                            data-id='" . $row['ID'] . "' 
                                                            onclick='deleteUser(this)'>
                                                            <i class='fa fa-trash'></i> Delete
                                                        </button>";
                                                        echo "</td>";
                                                echo "</tr>";
                                                $i++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='5'>No users found</td></tr>";
                                        }
                                        ?>
									</tbody>
								</table>
								</div>
							</div>
						</section>
					<!-- end: page -->
				</section>
				<script>
    

function deleteUser(button) {
    const userId = $(button).data('id'); // Get user ID from data attribute

    if (confirm('Are you sure you want to delete this user?')) {
        $.ajax({
            url: 'api/delete_ads.php',
            type: 'POST',
            data: { id: userId }, // Send data as form-encoded
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert('User deleted successfully.');
                    location.reload();
                } else {
                    alert('Error deleting user.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An unexpected error occurred.');
            }
        });
    }
}




</script>
<?php
include('footer.php');
?>