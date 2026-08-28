<!-----Header------>
<?php
session_start();
include('dbConnection.php');
include "includes/inner-header.php";
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
                                src="<?= $user['PROFILE_IMAGE'] != '' ? 'profile_img/' . $user['PROFILE_IMAGE'] : 'assets/images/profile.jpg' ?>"
                                alt="Profile" class="rounded-circle">
                            <h2><?= $_SESSION['name'] ?></h2>
                            <h3><?= $_SESSION['usertype'] ?></h3>
                            
                            <input type="file" id="profileImage" name="profileImage" accept="image/*" class="d-none">
                            <div class="image-buttons">
                                <button type="button" class="btn btn-outline-primary btn-action btn-sm" onclick="document.getElementById('profileImage').click();" title="Choose new image">
                                    <i class="fa-solid fa-image"></i> Choose
                                </button>
                                <button id="uploadBtn" class="btn btn-primary btn-action btn-sm" title="Upload new profile image">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload
                                </button>
                                <button id="deleteBtn" class="btn btn-danger btn-action btn-sm" title="Remove my profile image">
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
                                <form id="userUpdateForm" action="update_user.php" method="POST">
                                    
                                    <!-- Section 1: Account Information -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-circle-user"></i> Account Details
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="name">Name<span>*</span></label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly" id="name" name="name" 
                                                       value="<?= $user['NAME'] ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="email">Email<span>*</span></label>
                                            <div class="readonly-wrapper">
                                                <input type="email" class="form-control-modern form-control-readonly" id="email" name="email" 
                                                       value="<?= $user['EMAIL'] ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="number">WhatsApp Number<span>*</span></label>
                                            <div class="readonly-wrapper">
                                                <input type="number" class="form-control-modern form-control-readonly" id="number" name="number" 
                                                       value="<?= $user['WHATSAPP_NUMBER'] ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="password">Password<span>*</span></label>
                                            <input type="text" class="form-control-modern" id="password" name="password" 
                                                   value="<?= $user['PASSWORD'] ?>">
                                        </div>

                                        <div class="form-group">
                                            <label>Gender<span>*</span></label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly" value="<?= $user['GENDER'] ?>" readonly>
                                                <input type="hidden" name="GenderRadioOptions" value="<?= $user['GENDER'] ?>">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="dateofbirth">Date of Birth<span>*</span></label>
                                            <input type="date" class="form-control-modern" id="dateofbirth" name="dateofbirth" 
                                                   value="<?= $user['DOB'] ?>">
                                        </div>
                                    </div>

                                    <!-- Section 2: Preferences & Settings -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-sliders"></i> Preferences & Settings
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="level">Skill Level<span>*</span></label>
                                            <select class="form-control-modern" id="level" name="level" required>
                                                <option value="Beginner" <?= $user['LEVEL'] === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                                                <option value="Amateur" <?= $user['LEVEL'] === 'Amateur' ? 'selected' : '' ?>>Amateur</option>
                                                <option value="Intermediate" <?= $user['LEVEL'] === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                                <option value="Intermediate +" <?= $user['LEVEL'] === 'Intermediate +' ? 'selected' : '' ?>>Intermediate +</option>
                                                <option value="Advance" <?= $user['LEVEL'] === 'Advance' ? 'selected' : '' ?>>Advance</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="currency">Currency<span>*</span></label>
                                            <select class="form-control-modern" id="currency" name="currency" required>
                                                <option value="INR" <?= $user['CURRENCY'] === 'INR' ? 'selected' : '' ?>>INR - Indian Rupee</option>
                                                <option value="CAD" <?= $user['CURRENCY'] === 'CAD' ? 'selected' : '' ?>>CAD - Canadian Dollar</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="timezone-offset">Time Zone<span>*</span></label>
                                            <select class="form-control-modern" name="timezone_offset" id="timezone-offset" required>
                                                <option value="-05:00" <?= $user['TIMEZONE_OFFSET'] === '-05:00' ? 'selected' : '' ?>>(GMT -5:00) Eastern Time (Canada)</option>
                                                <option value="+05:30" <?= $user['TIMEZONE_OFFSET'] === '+05:30' ? 'selected' : '' ?>>(GMT +5:30) Indian Standard Time</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="usertype">User Type<span>*</span></label>
                                            <div class="readonly-wrapper">
                                                <input type="text" class="form-control-modern form-control-readonly" value="<?= $user['USERTYPE'] ?>" readonly>
                                                <input type="hidden" name="usertype" value="<?= $user['USERTYPE'] ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 3: Location Details -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-map-location-dot"></i> Location Information
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="City">City<span>*</span></label>
                                            <input type="text" class="form-control-modern" id="City" name="City" 
                                                   value="<?= $user['CITY'] ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="Province">Province<span>*</span></label>
                                            <input type="text" class="form-control-modern" id="Province" name="Province" 
                                                   value="<?= $user['PROVINCE'] ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="Country">Country<span>*</span></label>
                                            <input type="text" class="form-control-modern" id="Country" name="Country" 
                                                   value="<?= $user['COUNTRY'] ?>" required>
                                        </div>
                                    </div>

                                    <!-- Section 4: Privacy & Permissions -->
                                    <div class="form-section-title">
                                        <i class="fa-solid fa-shield-halved"></i> Permissions & Privacy
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Email Notification Permission<span>*</span></label>
                                            <div class="modern-toggle-group">
                                                <label>
                                                    <input type="radio" name="EmailPermission" value="Yes"
                                                           <?= $user['EMAIL_PERMISSION'] === 'Yes' ? 'checked' : '' ?>>
                                                    <span>Yes</span>
                                                </label>
                                                <label>
                                                    <input type="radio" name="EmailPermission" value="No"
                                                           <?= $user['EMAIL_PERMISSION'] === 'No' ? 'checked' : '' ?>>
                                                    <span>No</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Call, Text & Chat Consent<span>*</span></label>
                                            <div class="modern-toggle-group">
                                                <label>
                                                    <input type="radio" name="CallPermission" value="Yes"
                                                           <?= $user['CALL_PERMISSION'] === 'Yes' ? 'checked' : '' ?>>
                                                    <span>Yes</span>
                                                </label>
                                                <label>
                                                    <input type="radio" name="CallPermission" value="No"
                                                           <?= $user['CALL_PERMISSION'] === 'No' ? 'checked' : '' ?>>
                                                    <span>No</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="user_id" id="user_id" value="<?= $_SESSION['user_id'] ?>">

                                    <div class="text-end mt-4 pb-2">
                                        <button type="submit" class="btn-primary-modern">Save Profile Changes</button>
                                    </div>
                                </form>
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
<?php include "includes/footer.php"; ?>
<script>
    $(document).ready(function () {
        $("form").on("submit", function (e) {
            e.preventDefault(); // Prevent the default form submission

            var formData = $(this).serialize(); // Serialize the form data

            $.ajax({
                url: 'api/update_user.php', // The API endpoint that will handle the update
                type: 'POST',
                data: formData, // Send the form data as POST request
                success: function (response) {
                    // Check if the response contains success
                    var res = JSON.parse(response)
                    if (res.success) {
                        alert('User updated successfully!');
                        window.location.href = 'player-profile.php';
                        // Optionally, you can redirect or update the UI here
                    } else {
                        alert('Error: ' + res.message); // Display error message
                    }
                },
                error: function () {
                    alert('Something went wrong! Please try again.');
                }
            });
        });
    });

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
            url: 'api/upload_profile_image.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                alert(response);
                window.location.href = 'player-profile.php';
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