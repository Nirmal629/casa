<?php
include('header.php');
include('sidebar.php');
?>

<style>
	.error-msg {
		color: #d9534f;
		font-size: 12px;
		margin-top: 5px;
		display: none;
	}

	.has-error input,
	.has-error select {
		border-color: #d9534f !important;
	}

	#flash-container {
		position: fixed;
		top: 20px;
		right: 20px;
		z-index: 9999;
		min-width: 300px;
	}

	/* Formatting to match modal style */
	.control-label {
		font-weight: bold;
	}

	.whatsapp-logo {
		background-color: #25d366;
		color: white;
		border: none;
	}
	    .page-header {
    padding-left: 1cm; /* 👈 creates the left gap */
    }
    
    .left-wrapper {
        display: flex;
        align-items: center;
    }
    
    .breadcrumbs {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 10px;
    }
    
    .breadcrumbs li span {
        font-size: 20px;
        font-weight: 700; /* 👈 bold */
        color: #000;
    }
    
    .breadcrumbs li a {
        color: #000;
        font-size: 18px;
    }
</style>

<div id="flash-container"></div>

<section role="main" class="content-body">
	<header class="page-header">
		<!--<h2>Add User</h2>-->
		<div class="left-wrapper">
			<ol class="breadcrumbs">
				<li><a href="dashboard.php"><i class="fa fa-home"></i></a></li>
				<li><span>User</span></li>
			</ol>
		</div>
	</header>

	<div class="row">
		<div class="col-lg-12">
			<section class="panel">
				<header class="panel-heading">
				    <div class="panel-actions">
						<!--<a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>-->
						<a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
					</div>
					<h2 class="panel-title">Add User</h2>
				</header>
				<div class="panel-body">
					<form class="form-horizontal" id="addUserForm">

						<!-- Hidden Database Requirements (Logic preserved but removed from UI) -->
						<input type="hidden" name="password" value="123456">
						<input type="hidden" name="usertype" value="Player">
						<input type="hidden" name="premium" value="N">
						<input type="hidden" name="call_permission" value="Y">
						<input type="hidden" name="email_permission" value="Y">
						<input type="hidden" name="address" value="N/A">
						<input type="hidden" name="games" value="Badminton">

						<!-- 1. Name (Auto Capitalization) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Name*</label>
							<div class="col-md-6">
								<input type="text" name="name" id="name" class="form-control"
									style="text-transform: capitalize;"
									oninput="this.value = this.value.replace(/\b\w/g, l => l.toUpperCase())" required>
								<span class="error-msg" id="err_name">Name is required.</span>
							</div>
						</div>

						<!-- 2. Email -->
						<div class="form-group">
							<label class="col-md-3 control-label">Email*</label>
							<div class="col-md-6">
								<input type="email" name="email" id="email" class="form-control" required>
								<span class="error-msg" id="err_email">Valid email is required.</span>
							</div>
						</div>

						<!-- 3. WhatsApp Number (Single Line with Logo) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Contact*</label>
							<div class="col-md-6">
								<div class="input-group">
									<span class="input-group-addon whatsapp-logo"><i class="fa fa-whatsapp"></i></span>
									<input type="number" name="whatsapp_number" id="whatsapp_number" class="form-control" placeholder="Whatsapp number" required>
								</div>
								<span class="error-msg" id="err_whatsapp">WhatsApp number is required.</span>
							</div>
						</div>

						<!-- 4. DOB (Default current date) -->
						<div class="form-group">
							<label class="col-md-3 control-label">DOB</label>
							<div class="col-md-6">
								<input type="date" name="dob" id="dob" class="form-control" value="<?php echo date('Y-m-d'); ?>">
							</div>
						</div>

						<!-- 5. Gender (Side by Side) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Gender*</label>
							<div class="col-md-6">
								<label class="radio-inline"><input type="radio" name="gender" value="Male" checked> Male</label>
								<label class="radio-inline"><input type="radio" name="gender" value="Female"> Female</label>
							</div>
						</div>

						<!-- 6. Level (Default Intermediate) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Skill*</label>
							<div class="col-md-6">
								<select name="level" id="level" class="form-control">
									<option>Beginner</option>
									<option>Amateur</option>
									<option selected>Intermediate</option>
									<option>Intermediate +</option>
									<option>Advance</option>
								</select>
							</div>
						</div>

						<!-- 7. Country (Dropdown - Default Canada) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Country</label>
							<div class="col-md-6">
								<select name="country" class="form-control">
									<option value="Canada" selected>Canada</option>
									<option value="USA">USA</option>
									<option value="India">India</option>
								</select>
							</div>
						</div>

						<!-- 8. Province (Dropdown - Default Ontario) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Province</label>
							<div class="col-md-6">
								<select name="province" class="form-control">
									<option value="Ontario" selected>Ontario</option>
									<option value="Quebec">Quebec</option>
									<option value="BC">British Columbia</option>
								</select>
							</div>
						</div>

						<!-- 9. City (Dropdown - Default GTA) -->
						<div class="form-group">
							<label class="col-md-3 control-label">City</label>
							<div class="col-md-6">
								<select name="city" id="city" class="form-control">
									<option value="GTA" selected>GTA</option>
									<option value="Toronto">Toronto</option>
									<option value="Mississauga">Mississauga</option>
								</select>
							</div>
						</div>

						<!-- 10. Referral Source (Light text placeholder) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Referral</label>
							<div class="col-md-6">
								<input type="text" name="referral" id="referral" class="form-control" placeholder="Existing player name, Online, Club name">
							</div>
						</div>

						<!-- Submit Button -->
						<div class="form-group">
							<div class="col-md-9 col-md-offset-3">
								<button type="submit" id="submitBtn" class="btn btn-primary btn-lg">
									Submit Registration
								</button>
							</div>
						</div>
					</form>
				</div>
			</section>
		</div>
	</div>
</section>

<?php include('footer.php'); ?>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	function showFlash(type, message) {
		const alertClass = (type === 'success') ? 'alert-success' : 'alert-danger';
		const html = `<div class="alert ${alertClass} shadow-lg"><button type="button" class="close" data-dismiss="alert">×</button><strong>${message}</strong></div>`;
		const $msg = $(html).appendTo('#flash-container');
		setTimeout(() => {
			$msg.fadeOut(1000, function() {
				$(this).remove();
			});
		}, 4000);
	}

	$(document).ready(function() {
		$("#addUserForm").on('submit', function(e) {
			e.preventDefault();
			$(".error-msg").hide();
			$(".form-group").removeClass('has-error');

			if ($("#name").val().trim() === "") {
				$("#err_name").show();
				$("#name").closest('.form-group').addClass('has-error');
				return false;
			}

			let formData = new FormData(this);
			$("#submitBtn").html("<i class='fa fa-spinner fa-spin'></i> Processing...").prop('disabled', true);

			$.ajax({
				url: "api/add_user.php",
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				success: function(response) {
					let res = (typeof response === 'object') ? response : JSON.parse(response);
					if (res.status === "success") {
						showFlash('success', 'User Registered Successfully!');
						$("#addUserForm")[0].reset();
					} else {
						showFlash('error', res.message);
					}
					$("#submitBtn").html('Submit Registration').prop('disabled', false);
				},
				error: () => {
					showFlash('error', "Server error.");
					$("#submitBtn").html('Submit Registration').prop('disabled', false);
				}
			});
		});
	});
</script>