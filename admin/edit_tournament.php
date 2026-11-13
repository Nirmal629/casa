<?php
include('header.php');
include('sidebar.php');

// 1. Get ID and Fetch Data
$id = $_GET['id'] ?? 0;
$host    = 'localhost';
$db      = 'casa_test';
$user    = 'casa_test';
$pass    = 'casa_test123#';
$dsn     = "mysql:host=$host;dbname=$db;charset=utf8";
$conn    = new PDO($dsn, $user, $pass, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Fetch tournament and banner
$sql = "SELECT t.*, b.IMGAE as banner_image 
        FROM to_tournaments t 
        LEFT JOIN to_tournamet_banners b ON t.ID = b.EVENTS_ID 
        WHERE t.ID = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $id]);
$event = $stmt->fetch();

if (!$event) {
    echo "<div class='alert alert-danger'>Tournament not found.</div>";
    exit;
}
?>

<style>
	.ck-editor__editable_inline { min-height: 200px; }
	.ck-editor { width: 100% !important; }
	.error-msg { color: #d9534f; font-size: 12px; display: none; }
	#flash-container { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; }
	.preview-wrapper { position: relative; display: inline-block; margin-top: 10px; }
	.preview-wrapper img { width: 200px; height: 150px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px; padding: 3px; background: #fff; }
	.remove-btn { position: absolute; top: -10px; right: -10px; background-color: #d9534f; color: white; border: 2px solid white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; z-index: 10; }
</style>

<div id="flash-container"></div>

<section role="main" class="content-body">
	<header class="page-header">
		<h2>Edit Tournament</h2>
	</header>

	<div class="row">
		<div class="col-lg-12">
			<section class="panel">
				<header class="panel-heading">
					<h2 class="panel-title">Edit Tournament: <?php echo htmlspecialchars($event['CUP_NAME']); ?></h2>
				</header>
				<div class="panel-body">
					<form class="form-horizontal" id="editEventForm" enctype="multipart/form-data">
						<input type="hidden" name="action" value="update">
						<input type="hidden" name="event_id" value="<?php echo $event['ID']; ?>">

						<!-- Basic Info -->
						<div class="form-group">
							<label class="col-md-3 control-label">Host Name <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="host_name" id="host_name" class="form-control" value="<?php echo htmlspecialchars($event['HOST_NAME']); ?>">
							</div>
						</div>

						<div class="form-group">
							<label class="col-md-3 control-label">Cup Name <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="cup_name" id="cup_name" class="form-control" value="<?php echo htmlspecialchars($event['CUP_NAME']); ?>">
							</div>
						</div>

						<!-- Location & Currency -->
						<div class="form-group">
							<label class="col-md-3 control-label">Location Details</label>
							<div class="col-md-2">
								<select name="event_country" class="form-control">
									<option value="Canada" <?php if($event['EVENT_COUNTRY'] == 'Canada') echo 'selected'; ?>>Canada</option>
								</select>
							</div>
							<div class="col-md-2">
								<select name="event_province" class="form-control">
									<option value="Ontario" <?php if($event['EVENT_PROVINCE'] == 'Ontario') echo 'selected'; ?>>Ontario</option>
								</select>
							</div>
							<div class="col-md-2">
								<select name="event_currency" id="event_currency" class="form-control">
									<option value="CAD" <?php if($event['EVENT_CURRENCY'] == 'CAD') echo 'selected'; ?>>CAD</option>
									<option value="USD" <?php if($event['EVENT_CURRENCY'] == 'USD') echo 'selected'; ?>>USD</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-md-3 control-label">Venue <span>*</span></label>
							<div class="col-md-6">
								<input type="text" name="event_venue" id="event_venue" class="form-control" value="<?php echo htmlspecialchars($event['EVENT_VENUE']); ?>">
							</div>
						</div>

						<!-- Categories & Skill -->
						<div class="form-group">
							<label class="col-md-3 control-label">Tournament Config</label>
							<div class="col-md-2">
								<select name="event_category" id="event_category" class="form-control">
									<option value="Open" <?php if($event['EVENT_CATEGORY'] == 'Open') echo 'selected'; ?>>Open</option>
									<option value="30+" <?php if($event['EVENT_CATEGORY'] == '30+') echo 'selected'; ?>>30+</option>
									<option value="40+" <?php if($event['EVENT_CATEGORY'] == '40+') echo 'selected'; ?>>40+</option>
								</select>
								<span class="help-block">Category</span>
							</div>
							<div class="col-md-2">
								<select name="gender_category" id="gender_category" class="form-control">
									<option value="Male" <?php if($event['GENDER_CATEGORY'] == 'Male') echo 'selected'; ?>>Male</option>
									<option value="Female" <?php if($event['GENDER_CATEGORY'] == 'Female') echo 'selected'; ?>>Female</option>
									<option value="Mixed" <?php if($event['GENDER_CATEGORY'] == 'Mixed') echo 'selected'; ?>>Mixed</option>
								</select>
								<span class="help-block">Gender</span>
							</div>
							<div class="col-md-2">
								<select name="event_type" id="event_type" class="form-control">
									<option value="Single" <?php if($event['EVENT_TYPE'] == 'Single') echo 'selected'; ?>>Single</option>
									<option value="Doubles" <?php if($event['EVENT_TYPE'] == 'Doubles') echo 'selected'; ?>>Doubles</option>
								</select>
								<span class="help-block">Type</span>
							</div>
						</div>

						<div class="form-group">
							<label class="col-md-3 control-label">Skill Level</label>
							<div class="col-md-6">
								<select name="skill_level" id="skill_level" class="form-control">
									<option value="All" <?php if($event['GENDER_SKILL_LEVEL'] == 'All') echo 'selected'; ?>>All</option>
									<option value="Adv" <?php if($event['GENDER_SKILL_LEVEL'] == 'Advance') echo 'selected'; ?>>Adv</option>
									<option value="Int+" <?php if($event['GENDER_SKILL_LEVEL'] == 'Intermediate+') echo 'selected'; ?>>Int+</option>
									<option value="Int" <?php if($event['GENDER_SKILL_LEVEL'] == 'Intermediate') echo 'selected'; ?>>Int</option>
								</select>
							</div>
						</div>

						<!-- Dates & Times -->
						<div class="form-group">
							<label class="col-md-3 control-label">Event Schedule</label>
							<div class="col-md-2">
								<input type="date" name="event_date" id="event_date" class="form-control" value="<?php echo $event['EVENT_DATE']; ?>">
								<span class="help-block">Event Date</span>
							</div>
							<div class="col-md-2">
								<input type="time" name="from_time" class="form-control" value="<?php echo $event['EVENT_TIME']; ?>">
								<span class="help-block">Start Time</span>
							</div>
							<div class="col-md-2">
								<input type="time" name="to_time" class="form-control" value="<?php echo $event['TO_TIME']; ?>">
								<span class="help-block">End Time</span>
							</div>
						</div>

						<!-- Registration Freeze (DB uses CANCEL_DATE/TIME) -->
						<div class="form-group">
							<label class="col-md-3 control-label">Registration Freeze</label>
							<div class="col-md-3">
								<input type="date" name="freeze_date" class="form-control" value="<?php echo $event['CANCEL_DATE']; ?>">
							</div>
							<div class="col-md-3">
								<input type="time" name="freeze_time" class="form-control" value="<?php echo $event['CANCEL_TIME']; ?>">
							</div>
						</div>

						<!-- Reporting & Draw -->
						<div class="form-group">
							<label class="col-md-3 control-label">Reporting & Draw</label>
							<div class="col-md-3">
								<input type="time" name="reporting_time" id="reporting_time" class="form-control" value="<?php echo $event['REPORTING_TIME']; ?>">
								<span class="help-block">Reporting Time</span>
							</div>
							<div class="col-md-3">
								<input type="time" name="match_start_time" id="match_start_time" class="form-control" value="<?php echo $event['MATCH_START_TIME']; ?>">
								<span class="help-block">Match Start Time</span>
							</div>
							<div class="col-md-3">
								<input type="date" name="draw_announcement" id="draw_announcement" class="form-control" value="<?php echo $event['DRAW_ANNOUNCEMENT']; ?>">
								<span class="help-block">Draw Date</span>
							</div>
						</div>

						<!-- Shuttle & Format -->
						<div class="form-group">
							<label class="col-md-3 control-label">Match Details</label>
							<div class="col-md-3">
								<select name="shuttle_type" id="shuttle_type" class="form-control">
									<option value="Feather" <?php if($event['SHUTTLE_TYPE'] == 'Feather') echo 'selected'; ?>>Feather</option>
									<option value="Nylon" <?php if($event['SHUTTLE_TYPE'] == 'Nylon') echo 'selected'; ?>>Nylon</option>
								</select>
							</div>
							<div class="col-md-6">
								<input type="text" name="match_format" id="match_format" class="form-control" value="<?php echo htmlspecialchars($event['MATCH_FORMAT']); ?>">
							</div>
						</div>

						<!-- Payment Details -->
						<div class="form-group">
							<label class="col-md-3 control-label">Costs & Payment</label>
							<div class="col-md-2">
								<input type="text" name="event_cost" class="form-control" value="<?php echo $event['EVENT_COST']; ?>">
								<span class="help-block">System Cost ($)</span>
							</div>
							<div class="col-md-2">
								<input type="number" name="amount" id="amount" class="form-control" value="<?php echo $event['AMOUNT']; ?>">
								<span class="help-block">Display Amount ($)</span>
							</div>
							<div class="col-md-3">
								<input type="text" name="payment_id" id="payment_id" class="form-control" value="<?php echo htmlspecialchars($event['PAYMENT_ID']); ?>">
								<span class="help-block">Payment E-mail</span>
							</div>
							<div class="col-md-2">
								<input type="date" name="payment_deadline" id="payment_deadline" class="form-control" value="<?php echo $event['PAYMENT_DEADLINE']; ?>">
								<span class="help-block">Deadline</span>
							</div>
						</div>

						<!-- Description & Message -->
						<div class="form-group">
							<label class="col-md-3 control-label">Description</label>
							<div class="col-md-9"><textarea name="event_description" id="event_description"><?php echo $event['EVENT_DESCRIPTION']; ?></textarea></div>
						</div>

						<div class="form-group">
							<label class="col-md-3 control-label">Event Message</label>
							<div class="col-md-9"><textarea name="event_message" id="event_message"><?php echo $event['EVENT_MESSAGE']; ?></textarea></div>
						</div>

						<!-- Templates -->
						<div class="form-group">
							<label class="col-md-3 control-label">Success Popup Template</label>
							<div class="col-md-9"><textarea name="popup_message" id="popup_message"><?php echo $event['POPUP_MESSAGE']; ?></textarea></div>
						</div>

						<div class="form-group">
							<label class="col-md-3 control-label">Payment Email Template</label>
							<div class="col-md-9"><textarea name="payment_mail" id="payment_mail"><?php echo $event['PAYMENT_MAIL']; ?></textarea></div>
						</div>

						<!-- Banner -->
						<div class="form-group">
							<label class="col-md-3 control-label">Banner Image</label>
							<div class="col-md-6">
								<input type="file" name="banner" id="banner" class="form-control" accept="image/*" onchange="previewImage(this)">
								<div class="preview-wrapper" id="previewWrapper" style="display: <?php echo !empty($event['banner_image']) ? 'inline-block' : 'none'; ?>;">
									<img id="imagePreview" src="assets/images/tournaments_banner/<?php echo $event['banner_image']; ?>" alt="Banner Preview">
									<div class="remove-btn" onclick="removeImage()">&#10005;</div>
								</div>
							</div>
						</div>

						<!-- Submit -->
						<div class="form-group">
							<div class="col-md-9 col-md-offset-3">
								<button type="submit" id="submitBtn" class="btn btn-primary">
									<i class="fa fa-save"></i> Save Changes
								</button>
								<a href="tournaments_list.php" class="btn btn-default">Cancel</a>
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

<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>

<script>
	// 1. Initialize variables for all 4 CKEditors
	let descEditor, msgEditor, popupEditor, mailEditor;

	// 2. Define the "Master Templates" (Same as Add Page)
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
		</ul>
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
	const cfg = { toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'] };

	ClassicEditor.create(document.querySelector('#event_description'), cfg).then(ed => descEditor = ed);
	ClassicEditor.create(document.querySelector('#event_message'), cfg).then(ed => msgEditor = ed);
	ClassicEditor.create(document.querySelector('#popup_message'), cfg).then(ed => popupEditor = ed);
	ClassicEditor.create(document.querySelector('#payment_mail'), cfg).then(ed => mailEditor = ed);

	// 4. The Live Update Function (Using .replaceAll for all instances)
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
		let mStartTime= $('#match_start_time').val() || '[Match Time]';
		let drawDate  = $('#draw_announcement').val() || '[Draw Date]';
		let shuttle   = $('#shuttle_type').val() || 'Feather';
		let format    = $('#match_format').val() || 'Best of 3 games';

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
			.replaceAll('[Match Time]', mStartTime)
			.replaceAll('[Draw Date]', drawDate)
			.replaceAll('[Shuttle]', shuttle)
			.replaceAll('[Match Format]', format);

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
				$('#previewWrapper').show();
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

	// 6. Main Document Ready Logic
	$(document).ready(function() {
		
		// Initial Fill: Wait for editors to be ready then fill with current DB values
		setTimeout(updateDynamicTemplates, 1500);

		// Live Listeners: Update templates as user types
		$(document).on('input change', '#cup_name, #amount, #event_currency, #payment_id, #payment_deadline, #event_category, #event_type, #event_date, #event_venue, #reporting_time, #match_start_time, #draw_announcement, #shuttle_type, #match_format', function() {
			updateDynamicTemplates();
		});

		$("#editEventForm").on('submit', function(e) {
			e.preventDefault();

			// Sync ALL 4 CKEditors to textareas
			if (descEditor) document.getElementById('event_description').value = descEditor.getData();
			if (msgEditor) document.getElementById('event_message').value = msgEditor.getData();
			if (popupEditor) document.getElementById('popup_message').value = popupEditor.getData();
			if (mailEditor) document.getElementById('payment_mail').value = mailEditor.getData();

			let formData = new FormData(this);
			$("#submitBtn").html("<i class='fa fa-spinner fa-spin'></i> Updating...").prop('disabled', true);

			$.ajax({
				url: "api/manage_tournament.php",
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				success: function(response) {
					let res = (typeof response === 'object') ? response : JSON.parse(response);
					if (res.status === "success") {
						showFlash('success', res.message);
						setTimeout(() => window.location.href = 'tournaments_list.php', 1500);
					} else {
						showFlash('error', res.message);
						$("#submitBtn").html('<i class="fa fa-save"></i> Save Changes').prop('disabled', false);
					}
				},
				error: () => {
					showFlash('error', "Internal server error.");
					$("#submitBtn").html('<i class="fa fa-save"></i> Save Changes').prop('disabled', false);
				}
			});
		});
	});
</script>