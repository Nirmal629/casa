<!-----Header------>
<?php 
session_start();
include('dbConnection.php');
include "includes/inner-header.php"; 
// Fetch user data
$select_user = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
$user = mysqli_fetch_assoc($select_user);


?>

<section class="section profile bothSide_gap">
    <div class="cust_container">
        <h2 class="heading">Profile Page</h2>
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <img src="<?=$user['PROFILE_IMAGE']!=''?'profile_img/'.$user['PROFILE_IMAGE']:'assets/images/profile.jpg'?>" alt="Profile" class="rounded-circle">
                        <h2><?=$_SESSION['name']?></h2>
                        <h3><?=$_SESSION['usertype']?></h3>
                        <!--<div class="social-links mt-2">-->
                        <!--    <a href="https://twitter.com/" class="twitter" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>-->
                        <!--    <a href="https://facebook.com/" class="facebook" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>-->
                        <!--    <a href="https://instagram.com/" class="instagram" target="_blank"><i class="fa-brands fa-instagram"></i></a>-->
                        <!--    <a href="https://linkedin.com/" class="linkedin" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>-->
                        <!--</div>-->
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <!-- Bordered Tabs -->
                        <ul class="nav nav-tabs nav-tabs-bordered">

                            <!--<li class="nav-item">-->
                            <!--    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Overview</button>-->
                            <!--</li>-->

                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Profile</button>
                            </li>

                            <!--<li class="nav-item">-->
                            <!--    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>-->
                            <!--</li>-->

                            <!--<li class="nav-item">-->
                            <!--    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change Password</button>-->
                            <!--</li>-->

                        </ul>
                        <div class="tab-content pt-2">

                            <!--<div class="tab-pane fade show active profile-overview" id="profile-overview">-->
                                <!---Overview----->
                            <!--    <h5 class="card-title">Profile Details</h5>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label ">Full Name</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: Anurag MG</div>-->
                            <!--    </div>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label">Club</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: Casa Club Sports</div>-->
                            <!--    </div>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label">Job</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: Host</div>-->
                            <!--    </div>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label">Country</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: India</div>-->
                            <!--    </div>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label">Address</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: 188, Raja S.C. Mallick Rd,. Kolkata – 700032</div>-->
                            <!--    </div>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label">Phone</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: 9876543210</div>-->
                            <!--    </div>-->

                            <!--    <div class="row">-->
                            <!--        <div class="col-lg-3 col-md-4 label">Email</div>-->
                            <!--        <div class="col-lg-9 col-md-8">: Anurag@example.com</div>-->
                            <!--    </div>-->

                            <!--</div>-->

                            <div class="tab-pane fade profile-edit pt-3 show active" id="profile-edit">

                                <!-- Profile Edit Form -->
                        
                                    <div class="row mb-3">
                                      <label for="profileImage" class="col-md-4 col-lg-3 col-form-label">Profile Image</label>
                                      <div class="col-md-8 col-lg-9">
                                        <img id="previewImage" src="<?=$user['PROFILE_IMAGE']!=''?'profile_img/'.$user['PROFILE_IMAGE']:'assets/images/profile.jpg'?>" alt="Profile" width="150" class="img-thumbnail">
                                        <input type="file" id="profileImage" name="profileImage" accept="image/*" class="form-control mt-2">
                                        <div class="pt-2">
                                          <button id="uploadBtn" class="btn btn-primary btn-sm" title="Upload new profile image">
                                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                          </button>
                                          <button id="deleteBtn" class="btn btn-danger btn-sm" title="Remove my profile image">
                                            <i class="fa-solid fa-trash-can"></i>
                                          </button>
                                        </div>
                                      </div>
                                    </div>


                                    <form id="userUpdateForm" action="update_user.php" method="POST">
    <div class="form-group">
        <label style="font-weight:bold" for="name">Name<span>*</span></label>
        <input type="text" class="form-control bg-light" id="name" name="name" 
               value="<?=$user['NAME']?>" readonly style="border:4px solid green">
    </div>

    <div class="form-group">
        <label style="font-weight:bold" for="email">Email<span>*</span></label>
        <input type="email" class="form-control bg-light" id="email" name="email" 
               value="<?=$user['EMAIL']?>" readonly style="border:4px solid green">
    </div>

    <div class="form-group">
        <label style="font-weight:bold" for="password">Password<span>*</span></label>
        <input type="text" class="form-control" id="password" name="password" 
               value="<?=$user['PASSWORD']?>">
    </div>

    <!-- Email permission -->
    <div class="form-group">
        <label style="font-weight:bold">Email Permission<span>*</span></label>
        <div>
            <label class="form-check-inline">
                <input type="radio" class="form-check-input" 
                       name="EmailPermission" value="Yes"
                       <?=$user['EMAIL_PERMISSION']==='Yes'?'checked':''?>> Yes
            </label>
            <label class="form-check-inline">
                <input type="radio" class="form-check-input" 
                       name="EmailPermission" value="No"
                       <?=$user['EMAIL_PERMISSION']==='No'?'checked':''?>> No
            </label>
        </div>
    </div>

    <!-- WhatsApp number (non-editable) -->
    <div class="form-group" >
        <label style="font-weight:bold" for="number">WhatsApp Number<span>*</span></label>
        <input type="number" class="form-control bg-light" id="number" name="number" 
               value="<?=$user['WHATSAPP_NUMBER']?>" readonly style="border:4px solid green">
    </div>

    <!-- Call permission -->
    <div class="form-group">
        <label style="font-weight:bold">Call, Text and Chat Permission<span>*</span></label>
        <div>
            <label class="form-check-inline">
                <input type="radio" class="form-check-input" 
                       name="CallPermission" value="Yes"
                       <?=$user['CALL_PERMISSION']==='Yes'?'checked':''?>> Yes
            </label>
            <label class="form-check-inline">
                <input type="radio" class="form-check-input" 
                       name="CallPermission" value="No"
                       <?=$user['CALL_PERMISSION']==='No'?'checked':''?>> No
            </label>
        </div>
    </div>

    <div class="form-group">
        <label style="font-weight:bold" for="dateofbirth">Date of Birth<span>*</span></label>
        <input type="date" class="form-control" id="dateofbirth" name="dateofbirth" 
               value="<?=$user['DOB']?>">
    </div>

    <!-- Gender (non-editable) -->
    <div class="form-group">
        <label style="font-weight:bold">Gender<span>*</span></label>
        <input type="text" class="form-control bg-light" value="<?=$user['GENDER']?>" readonly style="border:4px solid green">
        <input type="hidden" name="GenderRadioOptions" value="<?=$user['GENDER']?>"> <!-- keep gender in POST -->
    </div>

    <div class="form-group">
        <label style="font-weight:bold" for="City">City<span>*</span></label>
        <input type="text" class="form-control" id="City" name="City" 
               value="<?=$user['CITY']?>" required>
    </div>

    <div class="form-group">
        <label style="font-weight:bold" for="Country">Country<span>*</span></label>
        <input type="text" class="form-control" id="Country" name="Country" 
               value="<?=$user['COUNTRY']?>" required>
    </div>

    <div class="form-group">
        <label style="font-weight:bold" for="Province">Province<span>*</span></label>
        <input type="text" class="form-control" id="Province" name="Province" 
               value="<?=$user['PROVINCE']?>" required>
    </div>

    <!-- Currency dropdown -->
    <div class="form-group">
        <label style="font-weight:bold" for="currency">Currency<span>*</span></label>
        <select class="form-control" id="currency" name="currency" required>
            <option value="INR" <?=$user['CURRENCY']==='INR'?'selected':''?>>INR - Indian Rupee</option>
            <option value="CAD" <?=$user['CURRENCY']==='CAD'?'selected':''?>>CAD - Canadian Dollar</option>
        </select>
    </div>

    <!-- Level dropdown -->
    <div class="form-group">
        <label style="font-weight:bold" for="level">Level<span>*</span></label>
        <select class="form-control" id="level" name="level" required>
            <option value="Beginner" <?=$user['LEVEL']==='Beginner'?'selected':''?>>Beginner</option>
            <option value="Amateur" <?=$user['LEVEL']==='Amateur'?'selected':''?>>Amateur</option>
            <option value="Intermediate" <?=$user['LEVEL']==='Intermediate'?'selected':''?>>Intermediate</option>
            <option value="Intermediate +" <?=$user['LEVEL']==='Intermediate +'?'selected':''?>>Intermediate +</option>
            <option value="Advance" <?=$user['LEVEL']==='Advance'?'selected':''?>>Advance</option>
        </select>
    </div>

    <!-- Timezone -->
    <div class="form-group">
        <label style="font-weight:bold" for="timezone-offset">Time Zone<span>*</span></label>
        <select class="form-control" name="timezone_offset" id="timezone-offset" required>
            <option value="-05:00" <?=$user['TIMEZONE_OFFSET']==='-05:00'?'selected':''?>>(GMT -5:00) Eastern Time (Canada)</option>
            <option value="+05:30" <?=$user['TIMEZONE_OFFSET']==='+05:30'?'selected':''?>>(GMT +5:30) Indian Standard Time</option>
        </select>
    </div>

    <!-- User type (non-editable) -->
    <div class="form-group">
        <label style="font-weight:bold" for="usertype">Type<span>*</span></label>
        <input type="text" class="form-control bg-light" value="<?=$user['USERTYPE']?>" readonly style="border:4px solid green">
        <input type="hidden" name="usertype" value="<?=$user['USERTYPE']?>"> <!-- keep type in POST -->
    </div>

    <input type="hidden" name="user_id" id="user_id" value="<?=$_SESSION['user_id']?>">

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Update User</button>
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
    $(document).ready(function() {
    $("form").on("submit", function(e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = $(this).serialize(); // Serialize the form data

        $.ajax({
            url: 'api/update_user.php', // The API endpoint that will handle the update
            type: 'POST', 
            data: formData, // Send the form data as POST request
            success: function(response) {
                // Check if the response contains success
                var res = JSON.parse(response)
                if(res.success) {
                    alert('User updated successfully!');
                    window.location.href='player-profile.php';
                    // Optionally, you can redirect or update the UI here
                } else {
                    alert('Error: ' + res.message); // Display error message
                }
            },
            error: function() {
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
        window.location.href='player-profile.php';
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

