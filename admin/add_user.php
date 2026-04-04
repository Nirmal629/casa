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
</style>

<div id="flash-container"></div>

<section role="main" class="content-body">
	<header class="page-header">
		<h2>Add User</h2>
		<div class="right-wrapper pull-right">
			<ol class="breadcrumbs">
				<li><a href="dashboard.php"><i class="fa fa-home"></i></a></li>
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
					<h2 class="panel-title">Add User</h2>
				</header>
				<div class="panel-body">
					<form class="form-horizontal" id="addUserForm" enctype="multipart/form-data">

						<!-- Name -->
						<div class="form-group">
							<label class="col-md-3 control-label">Name <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="name" id="name" class="form-control">
								<span class="error-msg" id="err_name">Name is required.</span>
							</div>
						</div>

						<!-- Email -->
						<div class="form-group">
							<label class="col-md-3 control-label">Email<span>*</span></label>
							<div class="col-md-6">
								<input type="email" name="email" id="email" class="form-control">
								<span class="error-msg" id="err_email">Valid email is required.</span>
							</div>
						</div>

						<!-- Password -->
						<div class="form-group">
							<label class="col-md-3 control-label">Password <span>*</span></label>
							<div class="col-md-6">
								<input type="password" name="password" id="password" class="form-control">
								<span class="error-msg" id="err_password">Password is required.</span>
							</div>
						</div>

						<!-- Photo -->
						<div class="form-group">
							<label class="col-md-3 control-label">Photo <span>*</span></label>
							<div class="col-md-6">
								<input type="file" name="image" id="image" class="form-control" accept="image/*" onchange="previewImage(this)">
								<img id="imagePreview" style="margin-top:10px; max-width:120px; display:none; border:1px solid #ddd; padding:4px;">
								<span class="error-msg" id="err_image">Photo is required.</span>
							</div>
						</div>

						<!-- WhatsApp -->
						<div class="form-group">
							<label class="col-md-3 control-label">WhatsApp Number <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="whatsapp_number" id="whatsapp_number" class="form-control">
								<span class="error-msg" id="err_whatsapp">WhatsApp number is required.</span>
							</div>
						</div>

						<!-- Call Permission -->
						<div class="form-group">
							<label class="col-md-3 control-label">Call, Text Permission <span>*</span></label>
							<div class="col-md-6">
								<label class="radio-inline"><input type="radio" name="call_permission" value="Y" checked> Yes</label>
								<label class="radio-inline"><input type="radio" name="call_permission" value="N"> No</label>
							</div>
						</div>

						<!-- Date of Birth -->
						<div class="form-group">
							<label class="col-md-3 control-label">Date of Birth <span>*</span></label>
							<div class="col-md-6">
								<input type="date" name="dob" id="dob" class="form-control">
								<span class="error-msg" id="err_dob">DOB is required.</span>
							</div>
						</div>

						<!-- Premium -->
						<div class="form-group">
							<label class="col-md-3 control-label">Premium <span>*</span></label>
							<div class="col-md-6">
								<label class="radio-inline"><input type="radio" name="premium" value="Y"> Yes</label>
								<label class="radio-inline"><input type="radio" name="premium" value="N" checked> No</label>
							</div>
						</div>

						<!-- Gender -->
						<div class="form-group">
							<label class="col-md-3 control-label">Gender <span>*</span></label>
							<div class="col-md-6">
								<select name="gender" id="gender" class="form-control">
									<option value="">Select</option>
									<option>Male</option>
									<option>Female</option>
									<option>Kid</option>
								</select>
								<span class="error-msg" id="err_gender">Please select gender.</span>
							</div>
						</div>

						<!-- City -->
						<div class="form-group">
							<label class="col-md-3 control-label">City <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="city" id="city" class="form-control">
								<span class="error-msg" id="err_city">City is required.</span>
							</div>
						</div>

						<!-- Level -->
						<div class="form-group">
							<label class="col-md-3 control-label">Level <span>*</span></label>
							<div class="col-md-6">
								<select name="level" id="level" class="form-control">
									<option>Beginner</option>
									<option>Intermediate</option>
									<option selected>Intermediate +</option>
									<option>Advanced</option>
								</select>
							</div>
						</div>

						<!-- User Type -->
						<div class="form-group">
							<label class="col-md-3 control-label">Type <span>*</span></label>
							<div class="col-md-6">
								<select name="usertype" id="usertype" class="form-control">
									<option>Player</option>
									<option>Host</option>
									<option>Trainer</option>
								</select>
							</div>
						</div>

						<!-- Submit Button -->
						<div class="form-group">
							<div class="col-md-9 col-md-offset-3">
								<button type="submit" id="submitBtn" class="btn btn-success">
									<i class="fa fa-save"></i> Add User
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	function previewImage(input) {
		if (input.files && input.files[0]) {
			const reader = new FileReader();
			reader.onload = (e) => {
				const preview = document.getElementById('imagePreview');
				preview.src = e.target.result;
				preview.style.display = 'block';
			};
			reader.readAsDataURL(input.files[0]);
		}
	}

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

			let isValid = true;

			// Basic validations
			if ($("#name").val().trim() === "") {
				$("#err_name").show();
				$("#name").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if (!$("#email").val().match(/^[^ ]+@[^ ]+\.[a-z]{2,3}$/)) {
				$("#err_email").show();
				$("#email").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if ($("#password").val().trim() === "") {
				$("#err_password").show();
				$("#password").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if ($("#image")[0].files.length === 0) {
				$("#err_image").show();
				$("#image").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if ($("#whatsapp_number").val().trim() === "") {
				$("#err_whatsapp").show();
				$("#whatsapp_number").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if ($("#dob").val() === "") {
				$("#err_dob").show();
				$("#dob").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if ($("#gender").val() === "") {
				$("#err_gender").show();
				$("#gender").closest('.form-group').addClass('has-error');
				isValid = false;
			}
			if ($("#city").val().trim() === "") {
				$("#err_city").show();
				$("#city").closest('.form-group').addClass('has-error');
				isValid = false;
			}

			if (!isValid) {
				showFlash('error', 'Please fix the errors below.');
				return false;
			}

			let formData = new FormData(this);
			$("#submitBtn").html("<i class='fa fa-spinner fa-spin'></i> Saving...").prop('disabled', true);

			$.ajax({
				url: "api/add_user.php",
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				success: function(response) {
					let res = (typeof response === 'object') ? response : JSON.parse(response);
					if (res.status === "success") {
						showFlash('success', res.message);
						$("#addUserForm")[0].reset();
						$("#imagePreview").hide();
					} else {
						showFlash('error', res.message);
					}
					$("#submitBtn").html('<i class="fa fa-save"></i> Add User').prop('disabled', false);
				},
				error: () => {
					showFlash('error', "Server error.");
					$("#submitBtn").html('<i class="fa fa-save"></i> Add User').prop('disabled', false);
				}
			});
		});
	});
</script>