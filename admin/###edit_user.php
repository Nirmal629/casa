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

$stmt->close();
$conn->close();


?>

<style>
    .inline-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap; /* force same line */
    }
    
    .inline-group label {
        font-weight: 700;
        min-width: 140px; /* adjust for long labels */
        margin: 0;
    }
    
    .inline-group input,
    .inline-group select {
        flex: 1 1 auto;
        min-width: 0; /* allows shrinking on mobile */
    }
    
    .radio-group {
        display: flex;
        gap: 15px;
        flex-wrap: nowrap; /* keep radios in line */
    }
    
    .radio-inline {
        display: flex;
        align-items: center;
        gap: 5px;
        margin: 0;
        font-weight: 500;
    }
    
    /* OPTIONAL: Horizontal scroll on tiny screens */
    @media (max-width: 480px) {
        .inline-group {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

</style>
<section role="main" class="content-body">
					<header class="page-header">
    <h2>Edit User</h2>
</header>

						<div class="row">
							<div class="col-lg-12">
								<section class="panel">
									<header class="panel-heading">
										<div class="panel-actions">
											<!--<a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>-->
											<a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
										</div>
						
										<h2 class="panel-title">Edit User</h2>
									</header>
									<div class="panel-body">
                    <form  id="updateUserForm" action="update_user.php" method="POST">

                        <div class="form-group inline-group">
                            <label for="premium">Premium <span>*</span></label>
                        
                            <div class="radio-group">
                                <label class="radio-inline">
                                    <input type="radio" name="Premium" value="Y"
                                        <?=$user['PREMIUM'] === 'Y' ? 'checked' : ''?> required>
                                    Yes
                                </label>
                        
                                <label class="radio-inline">
                                    <input type="radio" name="Premium" value="N"
                                        <?=$user['PREMIUM'] === 'N' ? 'checked' : ''?> required>
                                    No
                                </label>
                            </div>
                        </div>

                        <!--<div class="form-group" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">-->
                        <!--    <label style="font-weight:bold" for="email_permission">Premium<span>*</span></label>-->
                        <!--    <div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="Premium" id="Premium" value="Y" <?=$user['PREMIUM'] === 'Y' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="Premium">Yes</label>-->
                        <!--        </div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="Premium" id="Premium2" value="N" <?=$user['PREMIUM'] === 'N' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="Premium2">No</label>-->
                        <!--        </div>-->
                        <!--    </div>-->
                        <!--</div>-->

                        <div class="form-group inline-group">
                            <label for="name">Name <span>*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Enter full name"
                                value="<?=$user['NAME']?>" required>
                        </div>
                        
                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold"  for="name">Name<span>*</span></label>-->
                        <!--    <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" value="<?=$user['NAME']?>" required>-->
                        <!--</div>-->

                        <div class="form-group inline-group">
                            <label for="name">Email <span>*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Enter your email"
                                value="<?=$user['EMAIL']?>" required>
                        </div>
                        
                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="email">Email<span>*</span></label>-->
                        <!--    <input type="email" class="form-control" id="email" name="email" placeholder="Enter Your Email" value="<?=$user['EMAIL']?>" required>-->
                        <!--</div>-->

                        <div class="form-group inline-group">
                            <label for="email">Password <span>*</span></label>
                            <input type="text" class="form-control" id="password" name="password"
                                placeholder="Enter your password"
                                value="<?=$user['PASSWORD']?>" required>
                        </div>
                        
                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="email">Password<span>*</span></label>-->
                        <!--    <input type="text" class="form-control" id="password" name="password" placeholder="Enter Your Password" value="<?=$user['PASSWORD']?>">-->
                        <!--</div>-->

                        <div class="form-group inline-group">
                            <label for="email_permission">EmailPermission <span>*</span></label>
                        
                            <div class="radio-group">
                                <label class="radio-inline">
                                    <input type="radio" name="EmailPermission" value="Yes"
                                        <?=$user['EMAIL_PERMISSION'] === 'Yes' ? 'checked' : ''?> required>
                                    Yes
                                </label>
                        
                                <label class="radio-inline">
                                    <input type="radio" name="EmailPermission" value="No"
                                        <?=$user['EMAIL_PERMISSION'] === 'No' ? 'checked' : ''?> required>
                                    No
                                </label>
                            </div>
                        </div>

                        <!--<div class="form-group" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">-->
                        <!--    <label style="font-weight:bold" for="email_permission">Email Permission<span>*</span></label>-->
                        <!--    <div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="EmailPermission" id="EmailPermission1" value="Yes" <?=$user['EMAIL_PERMISSION'] === 'Yes' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="EmailPermission1">Yes</label>-->
                        <!--        </div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="EmailPermission" id="EmailPermission2" value="No" <?=$user['EMAIL_PERMISSION'] === 'No' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="EmailPermission2">No</label>-->
                        <!--        </div>-->
                        <!--    </div>-->
                        <!--</div>-->

                        <div class="form-group inline-group">
                            <label for="number">Contact Number <span>*</span></label>
                            
                            <input type="number" class="form-control" id="number" name="number"
                                placeholder="Enter your contact number"
                                value="<?=$user['WHATSAPP_NUMBER']?>" required>
                        </div>

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="number">WhatsApp Number<span>*</span></label>-->
                        <!--    <input type="number" class="form-control" id="number" name="number" placeholder="Enter Your WhatsApp number" value="<?=$user['WHATSAPP_NUMBER']?>" required>-->
                        <!--</div>-->
                        
                        <div class="form-group inline-group">
                            <label for="call_permission">CallPermission <span>*</span></label>
                        
                            <div class="radio-group">
                                <label class="radio-inline">
                                    <input type="radio" name="CallPermission" value="Yes"
                                        <?=$user['CALL_PERMISSION'] === 'Yes' ? 'checked' : ''?> required>
                                    Yes
                                </label>
                        
                                <label class="radio-inline">
                                    <input type="radio" name="CallPermission" value="No"
                                        <?=$user['CALL_PERMISSION'] === 'No' ? 'checked' : ''?> required>
                                    No
                                </label>
                            </div>
                        </div>

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="call_permission">Call, Text and Chat Permission<span>*</span></label>-->
                        <!--    <div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="CallPermission" id="CallPermission1" value="Yes" <?=$user['CALL_PERMISSION'] === 'Yes' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="CallPermission1">Yes</label>-->
                        <!--        </div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="CallPermission" id="CallPermission2" value="No" <?=$user['CALL_PERMISSION'] === 'No' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="CallPermission2">No</label>-->
                        <!--        </div>-->
                        <!--    </div>-->
                        <!--</div>-->

                        <!-- Date of Birth -->
                        <div class="form-group inline-group">
                            <label for="dateofbirth">Date of Birth <span>*</span></label>
                            <input type="date" class="form-control" id="dateofbirth" name="dateofbirth" value="<?=$user['DOB']?>" required>
                        </div>
                        
                        <!-- Gender -->
                        <div class="form-group inline-group">
                            <label for="gender">Gender <span>*</span></label>
                            <div class="radio-group">
                                <label class="radio-inline">
                                    <input type="radio" name="GenderRadioOptions" value="Male" <?=$user['GENDER'] === 'Male' ? 'checked' : ''?> required> Male
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="GenderRadioOptions" value="Female" <?=$user['GENDER'] === 'Female' ? 'checked' : ''?> required> Female
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="GenderRadioOptions" value="Kid" <?=$user['GENDER'] === 'Kid' ? 'checked' : ''?> required> Kid
                                </label>
                            </div>
                        </div>
                        
                        <!-- City -->
                        <div class="form-group inline-group">
                            <label for="City">City <span>*</span></label>
                            <input type="text" class="form-control" id="City" name="City" placeholder="Enter Your City" value="<?=$user['CITY']?>" required>
                        </div>
                        
                        <!-- Country -->
                        <div class="form-group inline-group">
                            <label for="Country">Country <span>*</span></label>
                            <input type="text" class="form-control" id="Country" name="Country" placeholder="Enter Your Country" value="<?=$user['COUNTRY']?>" required>
                        </div>
                        
                        <!-- Province -->
                        <div class="form-group inline-group">
                            <label for="Province">Province <span>*</span></label>
                            <input type="text" class="form-control" id="Province" name="Province" placeholder="Enter Your Province" value="<?=$user['PROVINCE']?>" required>
                        </div>
                        
                        <!-- Currency -->
                        <div class="form-group inline-group">
                            <label for="currency">Currency <span>*</span></label>
                            <select class="form-control" id="currency" name="currency" required>
                                <option value="<?=$user['CURRENCY']?>" selected><?=$user['CURRENCY']?></option>
                                <option value="INR">INR - Indian Rupee (India)</option>
                                <option value="CAD">CAD - Canadian Dollar (Canada)</option>
                            </select>
                        </div>
                        
                        <!-- Level -->
                        <div class="form-group inline-group">
                            <label for="level">Level <span>*</span></label>
                            <select class="form-control" id="level" name="level" required>
                                <option value="<?=$user['LEVEL']?>" selected><?=$user['LEVEL']?></option>
                                <option value="Beginner">Beginner</option>
                                <option value="Amateur">Amateur</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Intermediate +">Intermediate +</option>
                                <option value="Advance">Advance</option>
                            </select>
                        </div>
                        
                        <!-- Verified Level -->
                        <div class="form-group inline-group">
                            <label for="vlevel">Verified Level <span>*</span></label>
                            <select class="form-control" id="vlevel" name="vlevel" required>
                                <option value="<?=$user['VERIFIED_LEVEL']?>" selected><?=$user['VERIFIED_LEVEL']?></option>
                                <option value="Beginner">Beginner</option>
                                <option value="Amateur">Amateur</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Intermediate +">Intermediate +</option>
                                <option value="Advance">Advance</option>
                            </select>
                        </div>
                        
                        <!-- Time Zone -->
                        <div class="form-group inline-group">
                            <label for="timezone-offset">Time Zone <span>*</span></label>
                            <select class="form-control" name="timezone_offset" id="timezone-offset" required>
                                <option value="-05:00" <?=$user['TIMEZONE_OFFSET']=="-05:00"?'selected':''?>>(GMT -5:00) Eastern Time (Canada)</option>
                                <option value="+05:30" <?=$user['TIMEZONE_OFFSET']=="+05:30"?'selected':''?>>(GMT +5:30) Indian Standard Time (New Delhi)</option>
                            </select>
                        </div>
                        
                        <!-- User Type -->
                        <div class="form-group inline-group">
                            <label for="usertype">Type <span>*</span></label>
                            <select class="form-control" name="usertype" id="usertype" required>
                                <option value="<?=$user['USERTYPE']?>" selected><?=$user['USERTYPE']?></option>
                                <option value="Player">Player</option>
                                <option value="Host">Host</option>
                                <option value="Trainer">Trainer</option>
                            </select>
                        </div>

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="dateofbirth">Date of Birth<span>*</span></label>-->
                        <!--    <input type="date" class="form-control" id="dateofbirth" name="dateofbirth" value="<?=$user['DOB']?>">-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="gender">Gender<span>*</span></label>-->
                        <!--    <div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="GenderRadioOptions" id="GenderRadioOptions1" value="Male" <?=$user['GENDER'] === 'Male' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="GenderRadioOptions1">Male</label>-->
                        <!--        </div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="GenderRadioOptions" id="GenderRadioOptions2" value="Female" <?=$user['GENDER'] === 'Female' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="GenderRadioOptions2">Female</label>-->
                        <!--        </div>-->
                        <!--        <div class="form-check form-check-inline">-->
                        <!--            <input class="form-check-input" type="radio" name="GenderRadioOptions" id="GenderRadioOptions3" value="Kid" <?=$user['GENDER'] === 'Kid' ? 'checked' : ''?> required>-->
                        <!--            <label style="font-weight:bold" class="form-check-label" for="GenderRadioOptions3">Kid</label>-->
                        <!--        </div>-->
                        <!--    </div>-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="city">City<span>*</span></label>-->
                        <!--    <input type="text" class="form-control" id="City" name="City" placeholder="Enter Your City" value="<?=$user['CITY']?>" required>-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="country">Country<span>*</span></label>-->
                        <!--    <input type="text" class="form-control" id="Country" name="Country" placeholder="Enter Your Country" value="<?=$user['COUNTRY']?>" required>-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="province">Province<span>*</span></label>-->
                        <!--    <input type="text" class="form-control" id="Province" name="Province" placeholder="Enter Your Province" value="<?=$user['PROVINCE']?>" required>-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="currency">Currency<span>*</span></label>-->
                        <!--    <select class="form-control" id="currency" name="currency" required>-->
                        <!--        <option value="<?=$user['CURRENCY']?>" selected><?=$user['CURRENCY']?></option>-->
                                <!--<option value="USD">USD - United States Dollar (America)</option>-->
                        <!--        <option value="INR">INR - Indian Rupee (India)</option>-->
                                <!--<option value="EUR">EUR - Euro (Europe)</option>-->
                                <!--<option value="ZAR">ZAR - South African Rand (Africa)</option>-->
                        <!--        <option value="CAD">CAD - Canadian Dollar (Canada)</option>-->
                                <!-- Add more currencies as needed -->
                        <!--    </select>-->
                        <!--</div>-->
                        
                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="currency">Level<span>*</span></label>-->
                        <!--    <select class="form-control" id="level" name="level" required>-->
                        <!--        <option value="<?=$user['LEVEL']?>" selected><?=$user['LEVEL']?></option>-->
                        <!--        <option value="Beginner">Beginner</option>-->
                        <!--        <option value="Amateur">Amateur</option>-->
                        <!--        <option value="Intermediate">Intermediate</option>-->
                        <!--        <option value="Intermediate +">Intermediate +</option>-->
                        <!--        <option value="Advance">Advance</option>-->
                                <!-- Add more currencies as needed -->
                        <!--    </select>-->
                        <!--</div>-->
                        
                        <!-- <div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="currency">Verified Level<span>*</span></label>-->
                        <!--    <select class="form-control" id="vlevel" name="vlevel" required>-->
                        <!--        <option value="<?=$user['VERIFIED_LEVEL']?>" selected><?=$user['VERIFIED_LEVEL']?></option>-->
                        <!--        <option value="Beginner">Beginner</option>-->
                        <!--        <option value="Amateur">Amateur</option>-->
                        <!--        <option value="Intermediate">Intermediate</option>-->
                        <!--        <option value="Intermediate +">Intermediate +</option>-->
                        <!--        <option value="Advance">Advance</option>-->
                                <!-- Add more currencies as needed -->
                        <!--    </select>-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="timezone_offset">Time Zone<span>*</span></label>-->
                        <!--    <select class="form-control" name="timezone_offset" id="timezone-offset" required>-->
                                <!-- <option value="-12:00" <?=$user['TIMEZONE_OFFSET']=="-12:00"?'selected':''?>>(GMT -12:00) Eniwetok, Kwajalein</option>-->
                                <!--<option value="-11:00" <?=$user['TIMEZONE_OFFSET']=="-11:00"?'selected':''?>>(GMT -11:00) Midway Island, Samoa</option>-->
                                <!--<option value="-10:00" <?=$user['TIMEZONE_OFFSET']=="-10:00"?'selected':''?>>(GMT -10:00) Hawaii</option>-->
                                <!--<option value="-09:50" <?=$user['TIMEZONE_OFFSET']=="-09:50"?'selected':''?>>(GMT -9:30) Taiohae</option>-->
                                <!--<option value="-09:00" <?=$user['TIMEZONE_OFFSET']=="-09:00"?'selected':''?>>(GMT -9:00) Alaska</option>-->
                                <!--<option value="-08:00" <?=$user['TIMEZONE_OFFSET']=="-08:00"?'selected':''?>>(GMT -8:00) Pacific Time (US &amp; Canada)</option>-->
                                <!--<option value="-07:00" <?=$user['TIMEZONE_OFFSET']=="-07:00"?'selected':''?>>(GMT -7:00) Mountain Time (US &amp; Canada)</option>-->
                                <!--<option value="-06:00" <?=$user['TIMEZONE_OFFSET']=="-06:00"?'selected':''?>>(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>-->
                                <!--<option value="-05:00" <?=$user['TIMEZONE_OFFSET']=="-05:00"?'selected':''?>>(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>-->
                                <!--<option value="-04:50" <?=$user['TIMEZONE_OFFSET']=="-04:50"?'selected':''?>>(GMT -4:30) Caracas</option>-->
                                <!--<option value="-04:00" <?=$user['TIMEZONE_OFFSET']=="-04:00"?'selected':''?>>(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>-->
                                <!--<option value="-03:50" <?=$user['TIMEZONE_OFFSET']=="-03:50"?'selected':''?>>(GMT -3:30) Newfoundland</option>-->
                                <!--<option value="-03:00" <?=$user['TIMEZONE_OFFSET']=="-03:00"?'selected':''?>>(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>-->
                                <!--<option value="-02:00" <?=$user['TIMEZONE_OFFSET']=="-02:00"?'selected':''?>>(GMT -2:00) Mid-Atlantic</option>-->
                                <!--<option value="-01:00" <?=$user['TIMEZONE_OFFSET']=="-01:00"?'selected':''?>>(GMT -1:00) Azores, Cape Verde Islands</option>-->
                                <!--<option value="+00:00" <?=$user['TIMEZONE_OFFSET']=="+00:00"?'selected':''?>>(GMT) Western Europe Time, London, Lisbon, Casablanca</option>-->
                                <!--<option value="+01:00" <?=$user['TIMEZONE_OFFSET']=="+01:00"?'selected':''?>>(GMT +1:00) Brussels, Copenhagen, Madrid, Paris</option>-->
                                <!--<option value="+02:00" <?=$user['TIMEZONE_OFFSET']=="+02:00"?'selected':''?>>(GMT +2:00) Kaliningrad, South Africa</option>-->
                                <!--<option value="+03:00" <?=$user['TIMEZONE_OFFSET']=="+03:00"?'selected':''?>>(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>-->
                                <!--<option value="+03:50" <?=$user['TIMEZONE_OFFSET']=="+03:50"?'selected':''?>>(GMT +3:30) Tehran</option>-->
                                <!--<option value="+04:00" <?=$user['TIMEZONE_OFFSET']=="+04:00"?'selected':''?>>(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>-->
                                <!--<option value="+04:50" <?=$user['TIMEZONE_OFFSET']=="+04:50"?'selected':''?>>(GMT +4:30) Kabul</option>-->
                                <!--<option value="+05:00" <?=$user['TIMEZONE_OFFSET']=="+05:00"?'selected':''?>>(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>-->
                                <!--<option value="+05:50" <?=$user['TIMEZONE_OFFSET']=="+05:50"?'selected':''?>>(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>-->
                                <!--<option value="+05:75" <?=$user['TIMEZONE_OFFSET']=="+05:75"?'selected':''?>>(GMT +5:45) Kathmandu, Pokhara</option>-->
                                <!--<option value="+06:00" <?=$user['TIMEZONE_OFFSET']=="+06:00"?'selected':''?>>(GMT +6:00) Almaty, Dhaka, Colombo</option>-->
                                <!--<option value="+06:50" <?=$user['TIMEZONE_OFFSET']=="+06:50"?'selected':''?>>(GMT +6:30) Yangon, Mandalay</option>-->
                                <!--<option value="+07:00" <?=$user['TIMEZONE_OFFSET']=="+07:00"?'selected':''?>>(GMT +7:00) Bangkok, Hanoi, Jakarta</option>-->
                                <!--<option value="+08:00" <?=$user['TIMEZONE_OFFSET']=="+08:00"?'selected':''?>>(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>-->
                                <!--<option value="+08:75" <?=$user['TIMEZONE_OFFSET']=="+08:75"?'selected':''?>>(GMT +8:45) Eucla</option>-->
                                <!--<option value="+09:00" <?=$user['TIMEZONE_OFFSET']=="+09:00"?'selected':''?>>(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>-->
                                <!--<option value="+09:50" <?=$user['TIMEZONE_OFFSET']=="+09:50"?'selected':''?>>(GMT +9:30) Adelaide, Darwin</option>-->
                                <!--<option value="+10:00" <?=$user['TIMEZONE_OFFSET']=="+10:00"?'selected':''?>>(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>-->
                                <!--<option value="+10:50" <?=$user['TIMEZONE_OFFSET']=="+10:50"?'selected':''?>>(GMT +10:30) Lord Howe Island</option>-->
                                <!--<option value="+11:00" <?=$user['TIMEZONE_OFFSET']=="+11:00"?'selected':''?>>(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>-->
                                <!--<option value="+11:50" <?=$user['TIMEZONE_OFFSET']=="+11:50"?'selected':''?>>(GMT +11:30) Norfolk Island</option>-->
                                <!--<option value="+12:00" <?=$user['TIMEZONE_OFFSET']=="+12:00"?'selected':''?>>(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>-->
                                <!--<option value="+12:75" <?=$user['TIMEZONE_OFFSET']=="+12:75"?'selected':''?>>(GMT +12:45) Chatham Islands</option>-->
                                <!--<option value="+13:00" <?=$user['TIMEZONE_OFFSET']=="+13:00"?'selected':''?>>(GMT +13:00) Apia, Nukualofa</option>-->
                                <!--<option value="+14:00" <?=$user['TIMEZONE_OFFSET']=="+14:00"?'selected':''?>>(GMT +14:00) Line Islands, Tokelau</option>-->
                                <!--<option value="-08:00" <?=$user['TIMEZONE_OFFSET']=="-08:00"?'selected':''?>>(GMT -8:00) Pacific Time (Canada)</option>-->
                                <!--<option value="-07:00" <?=$user['TIMEZONE_OFFSET']=="-07:00"?'selected':''?>>(GMT -7:00) Mountain Time (Canada)</option>-->
                                <!--<option value="-06:00" <?=$user['TIMEZONE_OFFSET']=="-06:00"?'selected':''?>>(GMT -6:00) Central Time (Canada)</option>-->
                        <!--        <option value="-05:00" <?=$user['TIMEZONE_OFFSET']=="-05:00"?'selected':''?>>(GMT -5:00) Eastern Time (Canada)</option>-->
                                <!--<option value="-04:00" <?=$user['TIMEZONE_OFFSET']=="-04:00"?'selected':''?>>(GMT -4:00) Atlantic Time (Canada)</option>-->
                                <!--<option value="-03:30" <?=$user['TIMEZONE_OFFSET']=="-03:30"?'selected':''?>>(GMT -3:30) Newfoundland Time (Canada)</option>-->
                        <!--        <option value="+05:30" <?=$user['TIMEZONE_OFFSET']=="+05:30"?'selected':''?>>(GMT +5:30) Indian Standard Time (New Delhi)</option>-->



                        <!--    </select>-->
                        <!--</div>-->

                        <!--<div class="form-group">-->
                        <!--    <label style="font-weight:bold" for="usertype">Type<span>*</span></label>-->
                        <!--    <select class="form-control" name="usertype" id="usertype" required>-->
                        <!--        <option value="<?=$user['USERTYPE']?>" selected><?=$user['USERTYPE']?></option>-->
                        <!--        <option value="Player">Player</option>-->
                        <!--        <option value="Host">Host</option>-->
                        <!--        <option value="Trainer">Trainer</option>-->
                        <!--    </select>-->
                        <!--</div>-->
                    <input type="hidden" name="user_id" id="user_id" value="<?=$_GET['user_id']?>">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update User</button>
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
<script>
    $(document).ready(function() {
    $("#updateUserForm").on("submit", function(e) {
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
                    window.location.href='manage_user.php';
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

</script>
