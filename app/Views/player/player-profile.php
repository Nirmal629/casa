<!-----Header------>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use App\Config\Database;
$conn = Database::getMysqli();
include __DIR__ . '/../layouts/header/inner-header.php';
// Fetch user data
$select_user = mysqli_query($conn, "select * from ca_users where ID='" . $_SESSION['user_id'] . "'");
$user = mysqli_fetch_assoc($select_user);


?>

<section class="section profile bothSide_gap modern-profile">
    <div class="cust_container">
        <h2 class="heading">Profile Settings</h2>
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <div class="profile-image-edit-container">
                            <img id="previewImage"
                                src="<?= $user['PROFILE_IMAGE'] != '' ? '../profile_img/' . $user['PROFILE_IMAGE'] : '../assets/images/profile.jpg' ?>"
                                alt="Profile" class="rounded-circle">
                            <h2><?= $_SESSION['name'] ?></h2>
                            <h3><?= $_SESSION['usertype'] ?></h3>

                            <input type="file" id="profileImage" name="profileImage" accept="image/*" class="d-none">
                            <div class="image-buttons">
                                <button type="button" class="btn btn-outline-primary btn-action btn-sm"
                                    onclick="document.getElementById('profileImage').click();" title="Choose new image">
                                    <i class="fa-solid fa-image"></i> Choose
                                </button>
                                <button id="uploadBtn" class="btn btn-primary btn-action btn-sm"
                                    title="Upload new profile image">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload
                                </button>
                                <button id="deleteBtn" class="btn btn-danger btn-action btn-sm"
                                    title="Remove my profile image">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-4 px-4">
                        <div class="tab-content pt-2">
                            <div class="tab-pane fade profile-edit show active" id="profile-edit">
                                <div class="profile-details-view">
                                    <!-- Section 1: Account Information -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-circle-user"></i> Account Details
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['NAME'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Email</label>
                                            <div class="readonly-wrapper">
                                                <input type="email" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['EMAIL'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>WhatsApp Number</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['WHATSAPP_NUMBER'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Gender</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['GENDER'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Date of Birth</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['DOB'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 2: Preferences & Settings -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-sliders"></i> Preferences & Settings
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Skill Level</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['LEVEL'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Currency</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['CURRENCY'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Time Zone</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['TIMEZONE_OFFSET'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>User Type</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['USERTYPE'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 3: Location Details -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-map-location-dot"></i> Location Information
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>City</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['CITY'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Province</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['PROVINCE'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Country</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['COUNTRY'] ?? '', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 4: Privacy & Permissions -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-shield-halved"></i> Permissions & Privacy
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Email Notification Permission</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['EMAIL_PERMISSION'] ?? 'Yes', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Call, Text & Chat Consent</label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly"
                                                    value="<?= htmlspecialchars($user['CALL_PERMISSION'] ?? 'Yes', ENT_QUOTES) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!--<div class="tab-pane fade pt-3" id="profile-settings">-->

                            <!--    <form>-->

                            <!--        <div class="row mb-3">-->
                            <!--            <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Active Permission:</label>-->
                            <!--            <div class="col-md-8 col-lg-9">-->
                            <!--                <div class="form-check">-->
                            <!--                    <input class="form-check-input" type="checkbox" id="Emailpermission" checked>-->
                            <!--                    <label class="form-check-label" for="Emailpermission ">-->
                            <!--                        Email Permission-->
                            <!--                    </label>-->
                            <!--                </div>-->
                            <!--                <div class="form-check">-->
                            <!--                    <input class="form-check-input" type="checkbox" id="CallPermission" checked>-->
                            <!--                    <label class="form-check-label" for="CallPermission ">-->
                            <!--                        Phone Call Permission-->
                            <!--                    </label>-->
                            <!--                </div>-->
                            <!--                <div class="form-check">-->
                            <!--                    <input class="form-check-input" type="checkbox" id="Textpermission" checked>-->
                            <!--                    <label class="form-check-label" for="Textpermission  ">-->
                            <!--                        Phone Text Permission-->
                            <!--                    </label>-->
                            <!--                </div>-->
                            <!--                <div class="form-check">-->
                            <!--                    <input class="form-check-input" type="checkbox" id="whatsappCallPermission">-->
                            <!--                    <label class="form-check-label" for="whatsappCallPermission ">-->
                            <!--                        WhatsApp Call Permission-->
                            <!--                    </label>-->
                            <!--                </div>-->
                            <!--                <div class="form-check">-->
                            <!--                    <input class="form-check-input" type="checkbox" id="whatsappTextpermission" checked>-->
                            <!--                    <label class="form-check-label" for="whatsappTextpermission  ">-->
                            <!--                        WhatsApp Text Permission-->
                            <!--                    </label>-->
                            <!--                </div>-->
                            <!--            </div>-->
                            <!--        </div>-->

                            <!--        <div class="text-center">-->
                            <!--            <button type="submit" class="btn btn-primary">Save Changes</button>-->
                            <!--        </div>-->
                            <!--    </form>-->

                            <!--</div>-->

                            <!--<div class="tab-pane fade pt-3" id="profile-change-password">-->
                            <!-- Change Password Form -->
                            <!--    <form>-->

                            <!--        <div class="row mb-3">-->
                            <!--            <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Current Password</label>-->
                            <!--            <div class="col-md-8 col-lg-9">-->
                            <!--                <input name="password" type="password" class="form-control" id="currentPassword" required>-->
                            <!--            </div>-->
                            <!--        </div>-->

                            <!--        <div class="row mb-3">-->
                            <!--            <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">New Password</label>-->
                            <!--            <div class="col-md-8 col-lg-9">-->
                            <!--                <input name="newpassword" type="password" class="form-control" id="newPassword" required>-->
                            <!--            </div>-->
                            <!--        </div>-->

                            <!--        <div class="row mb-3">-->
                            <!--            <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Re-enter New Password</label>-->
                            <!--            <div class="col-md-8 col-lg-9">-->
                            <!--                <input name="renewpassword" type="password" class="form-control" id="renewPassword" required>-->
                            <!--            </div>-->
                            <!--        </div>-->

                            <!--        <div class="text-center">-->
                            <!--            <button type="submit" class="btn btn-primary">Change Password</button>-->
                            <!--        </div>-->
                            <!--    </form>-->
                            <!-- End Change Password Form -->

                        </div>

                    </div><!-- End Bordered Tabs -->

                </div>
            </div>

        </div>
    </div>
    </div>
</section>


<!------footer------>
<?php include __DIR__ . "/../layouts/footer/footer.php"; ?>
<script>
    $('#profileImage').on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#previewImage').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Upload image using AJAX
    $('#uploadBtn').click(function (e) {
        e.preventDefault();

        var fileInput = $('#profileImage')[0];
        if (fileInput.files.length === 0) {
            alert("Please select an image.");
            return;
        }

        var formData = new FormData();
        formData.append('profileImage', fileInput.files[0]);
        formData.append('user_id', '<?= $_SESSION['user_id'] ?>');

        $.ajax({
            url: '../api/upload_profile_image.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                alert(response);
                window.location.href = '/player/player-profile.php';
            },
            error: function () {
                alert('Error uploading image.');
            }
        });
    });

    // Delete image using AJAX
    $('#deleteBtn').click(function () {
        if (confirm("Are you sure you want to delete this image?")) {
            $.ajax({
                url: 'delete_profile.php',
                type: 'POST',
                data: { user_id: 1 },
                success: function (response) {
                    alert(response);
                    $('#previewImage').attr('src', 'assets/images/default.jpg'); // fallback/default image
                },
                error: function () {
                    alert('Error deleting image.');
                }
            });
        }
    });

</script>