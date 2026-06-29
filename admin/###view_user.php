<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');


// Fetch user ID from query string or default to a specific ID
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM ca_users WHERE ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // echo "<pre>";
    // print_r($user);exit;
} else {
    die("User not found.");
}

?>

<head>
    <meta charset="UTF-8">
    <!--<title>View User</title>-->
    <link rel="stylesheet" href="path_to_your_fontawesome.css">
    <link rel="stylesheet" href="path_to_bootstrap.css">

<style>

body { font-family: Arial, sans-serif; padding: 20px; }

/* Container for each field */
.view-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: nowrap; /* never wrap */
    margin-bottom: 12px;
    overflow-x: auto; /* allow horizontal scroll on small screens */
}

/* Label styling */
.view-group label {
    font-weight: 700;
    min-width: 140px; /* adjust based on longest label */
    white-space: nowrap; /* prevent label wrapping */
}

/* Value box styling */
.view-group .value {
    flex: 1 1 auto; /* shrink if needed */
    padding: 6px 10px;
    border-radius: 4px;
    background-color: #f8f9fa;
    white-space: nowrap; /* keep text in one line */
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Optional: horizontal scroll indicator */
.view-group::-webkit-scrollbar {
    height: 6px;
}
.view-group::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}
</style>


</head>

<section role="main" class="content-body">
					<header class="page-header">
    <h2>View User</h2>
</header>

						<div class="row">
							<div class="col-lg-12">
								<section class="panel">
									<header class="panel-heading">
										<div class="panel-actions">
											<!--<a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>-->
											<a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
										</div>
						
										<h2 class="panel-title">View User</h2>
									</header>
									<div class="panel-body">

                                    <div class="view-group">
                                        <label>Premium:</label>
                                        <div class="value"><?= $user['PREMIUM'] === 'Y' ? 'Yes' : 'No' ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>Name:</label>
                                        <div class="value"><?= htmlspecialchars($user['NAME']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>Email:</label>
                                        <div class="value"><?= htmlspecialchars($user['EMAIL']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>Password:</label>
                                        <div class="value"><?= htmlspecialchars($user['PASSWORD']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>EmailPermission:</label>
                                        <div class="value"><?= $user['EMAIL_PERMISSION'] === 'Yes' ? 'Yes' : 'No' ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Contact Number:</label>
                                        <div class="value"><?= htmlspecialchars($user['WHATSAPP_NUMBER']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>CallPermission:</label>
                                        <div class="value"><?= $user['CALL_PERMISSION'] === 'Yes' ? 'Yes' : 'No' ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Date of Birth:</label>
                                        <div class="value"><?= htmlspecialchars($user['DOB']) ?></div>
                                    </div>                                 
                                    
                                    <div class="view-group">
                                        <label>Gender:</label>
                                        <div class="value"><?= htmlspecialchars($user['GENDER']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>Country:</label>
                                        <div class="value"><?= htmlspecialchars($user['COUNTRY']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Province:</label>
                                        <div class="value"><?= htmlspecialchars($user['PROVINCE']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>City:</label>
                                        <div class="value"><?= htmlspecialchars($user['CITY']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Area:</label>
                                        <div class="value"><?= htmlspecialchars($user['AREA']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Address:</label>
                                        <div class="value"><?= htmlspecialchars($user['ADDRESS']) ?></div>
                                    </div>                                    
                                    
                                    <div class="view-group">
                                        <label>Currency:</label>
                                        <div class="value"><?= htmlspecialchars($user['CURRENCY']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>Time Zone:</label>
                                        <div class="value"><?= htmlspecialchars($user['TIMEZONE_OFFSET']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Games:</label>
                                        <div class="value"><?= htmlspecialchars($user['GAMES']) ?></div>
                                    </div>                                    
                                    
                                    <div class="view-group">
                                        <label>Level:</label>
                                        <div class="value"><?= htmlspecialchars($user['LEVEL']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>Verified Level:</label>
                                        <div class="value"><?= htmlspecialchars($user['VERIFIED_LEVEL']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>Referrel:</label>
                                        <div class="value"><?= htmlspecialchars($user['REFERRAL']) ?></div>
                                    </div>
                                    
                                    <div class="view-group">
                                        <label>User Type:</label>
                                        <div class="value"><?= htmlspecialchars($user['USERTYPE']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>LogStatus:</label>
                                        <div class="value"><?= htmlspecialchars($user['LOG_STATUS']) ?></div>
                                    </div>

                                    <div class="view-group">
                                        <label>DelStatus:</label>
                                        <div class="value"><?= htmlspecialchars($user['DEL_STATUS']) ?></div>
                                    </div>                                    

                                    <div class="view-group">
                                        <label>CurrentRank:</label>
                                        <div class="value"><?= htmlspecialchars($user['CURRENT_RANKING']) ?></div>
                                    </div>     
                                    
                                    <a href="manage_user.php" class="btn btn-primary">Back to Users</a>

                    </form>
                </div>
            </section>
        </div>
    </div>
</section>

<script>
$(document).on('click', '[data-panel-dismiss]', function (e) {
    e.preventDefault();
    
        var redirectUrl = 'manage_user.php';

    
    var $panel = $(this).closest('.panel');
    $panel.fadeOut(300, function () {
        window.location.href = redirectUrl;
    });
});
</script>
