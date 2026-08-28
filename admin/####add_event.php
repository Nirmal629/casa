<?php
include('header.php');
include('sidebar.php');
?>

<style>
	/* Sets the height of the rich text editors */
	.ck-editor__editable_inline {
		min-height: 200px;
	}

	/* Ensure the editor matches your form width */
	.ck-editor {
		width: 100% !important;
	}

	.error-msg {
		color: #d9534f;
		font-size: 12px;
		margin-top: 5px;
		display: none;
	}

	.has-error input,
	.has-error select,
	.has-error textarea {
		border-color: #d9534f !important;
	}

	#flash-container {
		position: fixed;
		top: 20px;
		right: 20px;
		z-index: 9999;
		min-width: 300px;
	}

	/* Container that holds the image and button together */
	.preview-wrapper {
		position: relative;
		/* Essential for positioning the button */
		display: inline-block;
		/* Shrinks to fit the content */
		margin-top: 10px;
		display: none;
		/* Hidden until image is selected */
	}

	/* 1. Make the image a standard size */
	.preview-wrapper img {
		width: 200px;
		/* Standard width */
		height: 150px;
		/* Standard height */
		object-fit: cover;
		/* Crops image neatly if ratio doesn't match */
		border: 1px solid #ccc;
		border-radius: 4px;
		/* Rounded corners */
		padding: 3px;
		background: #fff;
	}

	/* 2. The Red Close Button */
	.remove-btn {
		position: absolute;
		/* Float over the image */
		top: -10px;
		/* Move up slightly off the edge */
		right: -10px;
		/* Move right slightly off the edge */

		background-color: #d9534f;
		/* Red color */
		color: white;
		border: 2px solid white;
		/* White border to make it pop */
		border-radius: 50%;
		/* Make it a circle */

		width: 25px;
		height: 25px;

		/* Center the X */
		display: flex;
		align-items: center;
		justify-content: center;

		font-weight: bold;
		cursor: pointer;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
		/* Small shadow for depth */
		transition: background 0.2s;
		z-index: 10;
	}

	.remove-btn:hover {
		background-color: #c9302c;
		/* Darker red on hover */
	}
</style>

<div id="flash-container"></div>

<section role="main" class="content-body">
	<header class="page-header">
		<h2>Add Event</h2>
		<div class="right-wrapper pull-right">
			<ol class="breadcrumbs">
				<li><a href="dashboard.php"><i class="fa fa-home"></i></a></li>
				<li><span>Add Event</span></li>
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
					<h2 class="panel-title">Tournament Details</h2>
				</header>
				<div class="panel-body">
					<form class="form-horizontal" id="addEventForm" enctype="multipart/form-data">

						<!-- Host Name -->
						<div class="form-group">
							<label class="col-md-3 control-label">Host Name <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="host_name" id="host_name" class="form-control" value="Casa Badminton Club Toronto">
								<span class="error-msg" id="err_host_name">Host name is required.</span>
							</div>
						</div>

						<!-- Cup Name -->
						<div class="form-group">
							<label class="col-md-3 control-label">Cup Name <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="cup_name" id="cup_name" class="form-control" value="Casa Cup 2026">
								<span class="error-msg" id="err_cup_name">Cup name is required.</span>
							</div>
						</div>

						<!-- Country -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Country <span>*</span></label>
							<div class="col-md-6">
								<select name="event_country" id="event_country" class="form-control">
									<option value="Canada">Canada</option>
								</select>
							</div>
						</div>

						<!-- Province -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Province <span>*</span></label>
							<div class="col-md-6">
								<select name="event_province" id="event_province" class="form-control">
									<option value="Ontario">Ontario</option>
								</select>
							</div>
						</div>

						<!-- City -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event City <span>*</span></label>
							<div class="col-md-6">
								<select name="event_city" id="event_city" class="form-control">
									<option value="GTA">GTA</option>
								</select>
							</div>
						</div>

						<!-- Currency -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Currency <span>*</span></label>
							<div class="col-md-6">
								<select name="event_currency" id="event_currency" class="form-control">
									<option value="CAD">CAD</option>
									<option value="USD">USD</option>
								</select>
							</div>
						</div>

						<!-- Venue -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Venue <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="event_venue" id="event_venue" class="form-control" value="Epic">
								<span class="error-msg" id="err_venue">Venue is required.</span>
							</div>
						</div>

						<!-- Event Category -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Category <span>*</span></label>
							<div class="col-md-6">
								<select name="event_category" id="event_category" class="form-control">
									<option value="Open">Open</option>
									<option value="30+">30+</option>
									<option value="35+">35+</option>
									<option value="40+">40+</option>
								</select>
							</div>
						</div>

						<!-- Gender Category -->
						<div class="form-group">
							<label class="col-md-3 control-label">Gender Category <span>*</span></label>
							<div class="col-md-6">
								<select name="gender_category" id="gender_category" class="form-control">
									<option value="Male">Male</option>
									<option value="Female">Female</option>
									<option value="Mixed">Mixed</option>
								</select>
							</div>
						</div>

						<!-- Skill Level -->
						<div class="form-group">
							<label class="col-md-3 control-label">Skill Level <span>*</span></label>
							<div class="col-md-6">
								<select name="skill_level" id="skill_level" class="form-control">
									<option value="All">All</option>
									<option value="Adv">Adv</option>
									<option value="Int+">Int+</option>
									<option value="Int">Int</option>
								</select>
							</div>
						</div>

						<!-- Event Type -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Type <span>*</span></label>
							<div class="col-md-6">
								<select name="event_type" id="event_type" class="form-control">
									<option value="Single">Single</option>
									<option value="Doubles">Doubles</option>
								</select>
							</div>
						</div>

						<!-- Event Date & Time -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Date & Time <span>*</span></label>
							<div class="col-md-3">
								<input type="date" name="event_date" id="event_date" class="form-control" value="2026-03-14">
							</div>
							<div class="col-md-3">
								<input type="time" name="from_time" id="from_time" class="form-control" value="10:00">
								<span class="help-block">From Time (EST)</span>
							</div>
							<div class="col-md-3">
								<input type="time" name="to_time" id="to_time" class="form-control" value="14:00">
								<span class="help-block">To Time (EST)</span>
							</div>
						</div>

						<!-- Freeze Date & Time -->
						<div class="form-group">
							<label class="col-md-3 control-label">Registration Freeze <span>*</span></label>
							<div class="col-md-3">
								<input type="date" name="freeze_date" id="freeze_date" class="form-control" value="2026-03-13">
							</div>
							<div class="col-md-3">
								<input type="time" name="freeze_time" id="freeze_time" class="form-control" value="10:00">
								<span class="help-block">Freeze Time (EST)</span>
							</div>
						</div>

						<!-- Event Cost -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Cost ($) <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="event_cost" id="event_cost" class="form-control" value="80">
								<span class="error-msg" id="err_cost">Cost is required.</span>
							</div>
						</div>

						<!-- Event Description -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Description <span>*</span></label>
							<div class="col-md-6">
								<textarea name="event_description" id="event_description" class="form-control">I Play Every Day But Don't Have a Trophy</textarea>
								<span class="error-msg" id="err_description">Description is required.</span>
							</div>
						</div>

						<!-- Event Message -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Message <span>*</span></label>
							<div class="col-md-6">
								<textarea name="event_message" id="event_message" class="form-control">Men’s Badminton Tournament in the GTA featuring competitive players from across the region...</textarea>
							</div>
						</div>

						<!-- Banner Image Field -->
						<div class="form-group">
							<label class="col-md-3 control-label">Banner Image <span>*</span></label>
							<div class="col-md-6">
								<!-- Input -->
								<input type="file" name="banner" id="banner" class="form-control" accept="image/*" onchange="previewImage(this)">

								<!-- Preview Container -->
								<div class="preview-wrapper" id="previewWrapper">
									<img id="imagePreview" src="" alt="Banner Preview">
									<!-- The 'x' character makes the cross icon -->
									<div class="remove-btn" onclick="removeImage()">&#10005;</div>
								</div>

								<span class="error-msg" id="err_banner">Banner is required.</span>
							</div>
						</div>
						<!-- 1. Timing & Schedule Section -->
						<div class="form-group">
							<label class="col-md-3 control-label">Tournament Schedule <span>*</span></label>
							<div class="col-md-3">
								<input type="time" name="reporting_time" id="reporting_time" class="form-control" value="09:00">
								<span class="help-block">Reporting Time</span>
							</div>
							<div class="col-md-3">
								<input type="time" name="match_start_time" id="match_start_time" class="form-control" value="10:00">
								<span class="help-block">Match Start Time</span>
							</div>
							<div class="col-md-3">
								<input type="date" name="draw_announcement" id="draw_announcement" class="form-control" value="2026-03-12">
								<span class="help-block">Draw Announcement Date</span>
							</div>
						</div>

						<!-- 2. Shuttle & Format Section -->
						<div class="form-group">
							<label class="col-md-3 control-label">Match Details <span>*</span></label>
							<div class="col-md-3">
								<select name="shuttle_type" class="form-control">
									<option value="Feather">Feather</option>
									<option value="Nylon">Nylon</option>
								</select>
								<span class="help-block">Shuttle Type</span>
							</div>
							<div class="col-md-6">
								<input type="text" name="match_format" class="form-control" placeholder="e.g. Best of 3 games / 21 Points" value="Best of 3 games">
								<span class="help-block">Match Format Description</span>
							</div>
						</div>

						<!-- 3. Payment Details Section -->
						<div class="form-group">
							<label class="col-md-3 control-label">Payment Details <span>*</span></label>
							<div class="col-md-3">
								<input type="number" name="amount" id="amount" class="form-control" value="80">
								<span class="help-block">Display Amount ($)</span>
							</div>
							<div class="col-md-3">
								<input type="text" name="payment_id" id="payment_id" class="form-control" value="casaclubpayment1@gmail.com">
								<span class="help-block">Payment ID (E-transfer Email)</span>
							</div>
							<div class="col-md-3">
								<input type="date" name="payment_deadline" id="payment_deadline" class="form-control" value="2026-03-13">
								<span class="help-block">Payment Deadline</span>
							</div>
						</div>

						<!-- 4. Registration Success Popup Template -->
						<!-- Registration Success Popup Template -->
						<!-- Registration Success Popup Template -->
<div class="form-group">
    <label class="col-md-3 control-label">Registration Popup Message</label>
    <div class="col-md-9">
        <textarea name="popup_message" id="popup_message" class="form-control">
            <h3>Tournament Registration Confirmation</h3>
            <p>Thank you for registering for the <strong>[Tournament Name]</strong>. Your registration has been successfully received.</p>
            
            <p>To confirm your participation, please complete the registration payment as per the details below:</p>
            <ul>
                <li><strong>Amount:</strong> [Currency] [Amount]</li>
                <li><strong>Payment Method:</strong> E-transfer</li>
                <li><strong>Payment ID:</strong> [Payment ID]</li>
                <li><strong>Payment Deadline:</strong> [Deadline Date]</li>
            </ul>

            <h4>Event Details</h4>
            <ul>
                <li><strong>Category:</strong> [Category] ([Type])</li>
                <li><strong>Match Date(s):</strong> [Date]</li>
                <li><strong>Venue:</strong> [Venue]</li>
                <li><strong>Schedule:</strong>
                    <ul>
                        <li>Reporting Time: [Reporting Time]</li>
                        <li>Match Start Time: [Match Time]</li>
                        <li>Draw Announcement: [Draw Date]</li>
                    </ul>
                </li>
            </ul>

            <h4>Match Rules</h4>
            <ul>
                <li><strong>Scoring Format:</strong> Matches will be played to 21 points (Deuce at 20-20). Match format: [Match Format].</li>
                <li><strong>Shuttle Type:</strong> [Shuttle] shuttle will be used.</li>
            </ul>

            <h4>Dress Code</h4>
            <ul>
                <li>Proper non-marking court shoes are mandatory.</li>
                <li>Sports attire required (no casual wear).</li>
                <li>Team events must wear matching jerseys (if applicable).</li>
            </ul>

            <h4>Refund Policy</h4>
            <ul>
                <li>Registration fees are non-refundable once payment is confirmed.</li>
                <li>Refunds will be issued only if the tournament is canceled by the organizers.</li>
                <li>No refunds for withdrawals, no-shows, or schedule conflicts.</li>
            </ul>

            <p>Once the payment is confirmed, the tournament administrator will contact you with your login credentials for casa-games.com and further tournament instructions.</p>
            <p>We appreciate your participation and look forward to welcoming you.</p>
            <p>— Casa Games Admin Team 🏸</p>
        </textarea>
    </div>
</div>


						<!-- 5. Payment Reminder Email Template -->
						<!-- Payment Reminder Email Template -->
						<div class="form-group">
							<label class="col-md-3 control-label">Payment/Login Email Template</label>
							<div class="col-md-9">
								<textarea name="payment_mail" id="payment_mail" class="form-control">
            <p>Dear Participant,</p>
            <p>We have not received your payment yet for <strong>[Tournament Name]</strong>. We are pleased to share your login credentials for casa-games.com:</p>
            <p><strong>Website:</strong> https://casa-games.com</p>
            <p><strong>Username:</strong> [Username]<br><strong>Temporary Password:</strong> [Password]</p>
            <hr>
            <p><strong>Payment Instructions:</strong></p>
            <ul>
                <li>Amount: [Currency] [Amount]</li>
                <li>Payment ID: [Payment ID]</li>
                <li>Deadline: [Deadline Date]</li>
            </ul>
            <p>— Casa Games Admin Team 🏸</p>
        </textarea>
							</div>
						</div>
						<!-- Submit Button -->
						<div class="form-group">
							<div class="col-md-9 col-md-offset-3">
								<button type="submit" id="submitBtn" class="btn btn-primary">
									<i class="fa fa-save"></i> Create Event
								</button>
							</div>
						</div>
					</form>
				</div>
			</section>
		</div>
	</div>
</section>
<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>

<?php include('footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	// 1. Initialize variables for all 4 CKEditors
	let descEditor, msgEditor, popupEditor, mailEditor;

	// 2. Define the "Master Templates" with FULL CONTENT
	const masterPopupTemplate = `
		<h3>Tournament Registration Confirmation</h3>
		<p>Thank you for registering for the <strong>[Tournament Name]</strong>. Your registration has been successfully received.</p>
		
		<p>To confirm your participation, please complete the registration payment as per the details below:</p>
		<ul>
			<li><strong>Amount:</strong> [Currency] [Amount]</li>
			<li><strong>Payment Method:</strong> E-transfer</li>
			<li><strong>Payment ID:</strong> [Payment ID]</li>
			<li><strong>Payment Deadline:</strong> [Deadline Date]</li>
		</ul>

		<h4>Event Details</h4>
		<ul>
			<li><strong>Category:</strong> [Category] ([Type])</li>
			<li><strong>Match Date(s):</strong> [Date]</li>
			<li><strong>Venue:</strong> [Venue]</li>
			<li><strong>Schedule:</strong>
				<ul>
					<li>Reporting Time: [Reporting Time]</li>
					<li>Match Start Time: [Match Time]</li>
					<li>Draw Announcement: [Draw Date]</li>
				</ul>
			</li>
		</ul>

		<h4>Match Rules</h4>
		<ul>
			<li><strong>Scoring Format:</strong> Matches will be played to 21 points (Deuce at 20-20). Match format: [Match Format].</li>
			<li><strong>Shuttle Type:</strong> [Shuttle] shuttle will be used.</li>
		</ul>

		<h4>Dress Code</h4>
		<ul>
			<li>Proper non-marking court shoes are mandatory.</li>
			<li>Sports attire required (no casual wear).</li>
		</ul>

		<h4>Refund Policy</h4>
		<ul>
			<li>Registration fees are non-refundable once payment is confirmed.</li>
			<li>Refunds will be issued only if the tournament is canceled by the organizers.</li>
		</ul>

		<p>Once the payment is confirmed, the tournament administrator will contact you with your login credentials for casa-games.com and further tournament instructions.</p>
		<p>We appreciate your participation and look forward to welcoming you.</p>
		<p>— Casa Games Admin Team 🏸</p>`;

	const masterMailTemplate = `
		<p>Dear Participant,</p>
		<p>We have not received your payment yet for <strong>[Tournament Name]</strong>. We are pleased to share your login credentials for casa-games.com:</p>
		<p><strong>Website:</strong> https://casa-games.com</p>
		<p><strong>Username:</strong> [Username]<br><strong>Temporary Password:</strong> [Password]</p>
		<hr>
		<p><strong>Payment Instructions:</strong></p>
		<ul>
			<li>Amount: [Currency] [Amount]</li>
			<li>Payment ID: [Payment ID]</li>
			<li>Deadline: [Deadline Date]</li>
		</ul>
		<p>— Casa Games Admin Team 🏸</p>`;

	// 3. Initialize Editors
	const cfg = {
		toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
	};

	ClassicEditor.create(document.querySelector('#event_description'), cfg).then(ed => descEditor = ed);
	ClassicEditor.create(document.querySelector('#event_message'), cfg).then(ed => msgEditor = ed);
	ClassicEditor.create(document.querySelector('#popup_message'), cfg).then(ed => popupEditor = ed);
	ClassicEditor.create(document.querySelector('#payment_mail'), cfg).then(ed => mailEditor = ed);

	// 4. The Live Update Function (Using .replaceAll for multiple instances)
	function updateDynamicTemplates() {
		let cupName   = $('#cup_name').val() || '[Tournament Name]';
		let amount    = $('#amount').val() || '[Amount]';
		let currency  = $('#event_currency').val() || 'CAD';
		let payId     = $('#payment_id').val() || 'casaclubpayment1@gmail.com';
		let deadline  = $('#payment_deadline').val() || '[Deadline Date]';
		let category  = $('#event_category').val();
		let type      = $('#event_type').val();
		let date      = $('#event_date').val() || '[Date]';
		let venue     = $('#event_venue').val() || '[Venue]';
		let repTime   = $('#reporting_time').val() || '[Reporting Time]';
		let matchTime = $('#match_start_time').val() || '[Match Time]';
		let drawDate  = $('#draw_announcement').val() || '[Draw Date]';
		let shuttle   = $('select[name="shuttle_type"]').val() || 'Feather';
		let format    = $('input[name="match_format"]').val() || 'Best of 3 games';

		// Replace placeholders in Popup string (replaceAll ensures every instance is updated)
		let newPopup = masterPopupTemplate
			.replaceAll('[Tournament Name]', cupName)
			.replaceAll('[Currency]', currency)
			.replaceAll('[Amount]', amount)
			.replaceAll('[Payment ID]', payId)
			.replaceAll('[Deadline Date]', deadline)
			.replaceAll('[Category]', category)
			.replaceAll('[Type]', type)
			.replaceAll('[Date]', date)
			.replaceAll('[Venue]', venue)
			.replaceAll('[Reporting Time]', repTime)
			.replaceAll('[Match Time]', matchTime)
			.replaceAll('[Draw Date]', drawDate)
			.replaceAll('[Shuttle]', shuttle)
			.replaceAll('[Match Format]', format);

		// Replace placeholders in Mail string
		let newMail = masterMailTemplate
			.replaceAll('[Tournament Name]', cupName)
			.replaceAll('[Currency]', currency)
			.replaceAll('[Amount]', amount)
			.replaceAll('[Payment ID]', payId)
			.replaceAll('[Deadline Date]', deadline);

		if (popupEditor) popupEditor.setData(newPopup);
		if (mailEditor) mailEditor.setData(newMail);
	}

	// 5. Image Preview Functions
	function previewImage(input) {
		if (input.files && input.files[0]) {
			const reader = new FileReader();
			reader.onload = (e) => {
				$('#imagePreview').attr('src', e.target.result);
				$('#previewWrapper').css('display', 'inline-block');
				$("#err_banner").hide();
				$("#banner").closest('.form-group').removeClass('has-error');
			};
			reader.readAsDataURL(input.files[0]);
		}
	}

	function removeImage() {
		$('#banner').val("");
		$('#previewWrapper').hide();
		$('#imagePreview').attr('src', "");
	}

	function showFlash(type, message) {
		const alertClass = (type === 'success') ? 'alert-success' : 'alert-danger';
		const html = `<div class="alert ${alertClass} shadow-lg"><button type="button" class="close" data-dismiss="alert">×</button><strong>${message}</strong></div>`;
		const $msg = $(html).appendTo('#flash-container');
		setTimeout(() => { $msg.fadeOut(1000, function() { $(this).remove(); }); }, 4000);
	}

	// 6. Main Document Logic
	$(document).ready(function() {

		// Trigger initial template fill
		setTimeout(updateDynamicTemplates, 1500);

		// Watch all relevant inputs for changes
		$(document).on('input change', '#cup_name, #amount, #event_currency, #payment_id, #payment_deadline, #event_category, #event_type, #event_date, #event_venue, #reporting_time, #match_start_time, #draw_announcement, select[name="shuttle_type"], input[name="match_format"]', function() {
			updateDynamicTemplates();
		});

		$("#addEventForm").on('submit', function(e) {
			e.preventDefault();

			// Sync editors
			if (descEditor) document.getElementById('event_description').value = descEditor.getData();
			if (msgEditor) document.getElementById('event_message').value = msgEditor.getData();
			if (popupEditor) document.getElementById('popup_message').value = popupEditor.getData();
			if (mailEditor) document.getElementById('payment_mail').value = mailEditor.getData();

			$(".error-msg").hide();
			$(".form-group").removeClass('has-error');

			if ($("#cup_name").val().trim() === "" || $("#banner")[0].files.length === 0) {
				showFlash('error', 'Cup Name and Banner are required.');
				return false;
			}

			let formData = new FormData(this);
			$("#submitBtn").html("<i class='fa fa-spinner fa-spin'></i> Saving...").prop('disabled', true);

			$.ajax({
				url: "api/add_tournament.php",
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				success: function(response) {
					let res = (typeof response === 'object') ? response : JSON.parse(response);
					if (res.status === "success") {
						showFlash('success', res.message);
						$("#addEventForm")[0].reset();
						removeImage();
						updateDynamicTemplates(); // Reset editors to blank templates
					} else {
						showFlash('error', res.message);
					}
					$("#submitBtn").html('<i class="fa fa-save"></i> Create Event').prop('disabled', false);
				},
				error: () => {
					showFlash('error', "Server Error.");
					$("#submitBtn").html('<i class="fa fa-save"></i> Create Event').prop('disabled', false);
				}
			});
		});
	});
</script>