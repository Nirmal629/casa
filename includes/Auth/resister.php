<section class="customModal_wrap" id="resisterEvent_add">
    <div class="customModal_body">
        <h6 class="customModal_head">Resister Request Now</h6>
        <button class="customModal_close btn" id="resisterEvent_close">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form action="#" id="registerForm">
                <div class="mb-3 form-box">
                    <label for="name">Name<span>*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required="">
                </div>

                <div class="mb-3 form-box">
                    <label for="email">Email<span>*</span></label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter Your Email" required="">
                </div>

                <div class="mb-3" style="display: flex ; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <label for="number">Email Permission<span>*</span></label>
                    <div class="pl-1">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="EmailPermission" id="EmailPermission1" value="Yes" required>
                            <label class="form-check-label" for="EmailPermission1">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="EmailPermission" id="EmailPermission2" value="No" required>
                            <label class="form-check-label" for="EmailPermission2">No</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="number">Whatsapp Number<span>*</span></label>
                    <input type="number" class="form-control" id="number" name="number" placeholder="Enter Your whatsapp number" required="">
                </div>

                <div class="mb-3">
                    <label for="number">Call, Text and Chat Permission<span>*</span></label>
                    <div class="pl-1">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="CallPermission" id="CallPermission1" value="Yes" required>
                            <label class="form-check-label" for="CallPermission2">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="CallPermission" id="CallPermission2" value="No" required>
                            <label class="form-check-label" for="CallPermission2">No</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="dateofbirth">Date of birth</label>
                    <input type="date" class="form-control" id="dateofbirth" name="dateofbirth" placeholder="Enter Your date of birth" >
                </div>

                <div class="mb-4">
                    <label for="number">Gender<span>*</span></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="GenderRadioOptions" id="GenderRadioOptions1" value="Male" required>
                            <label class="form-check-label" for="GenderRadioOptions1">Male</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="GenderRadioOptions" id="GenderRadioOptions2" value="Female" required>
                            <label class="form-check-label" for="GenderRadioOptions2">Female</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="GenderRadioOptions" id="GenderRadioOptions3" value="Kid" required>
                            <label class="form-check-label" for="GenderRadioOptions3">Kid</label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="number">Games<span>*</span></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="GamesRadioBadminton" id="GamesRadioOptions1" value="Badminton" required>
                            <label class="form-check-label" for="GamesRadioOptions1">Badminton</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="GamesRadioBadminton" id="GamesRadioOptions2" value="Tennis" required disabled>
                            <label class="form-check-label" for="GamesRadioOptions2">Tennis</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="GamesRadioBadminton" id="GamesRadioOptions3" value="Cricket" required disabled>
                            <label class="form-check-label" for="GamesRadioOptions3">Cricket</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="Address">Address</label>
                    <textarea class="form-control" id="Address" rows="2" placeholder="Enter Your Address" ></textarea>
                </div>

                <div class="mb-3">
                    <label for="City">City<span>*</span></label>
                    <input type="text" class="form-control" id="City" name="City" placeholder="Enter Your City" required="">
                </div>

                <div class="mb-3">
                    <label for="City">Country<span>*</span></label>
                    <input type="text" class="form-control" id="Country" name="Country" placeholder="Enter Your Country" required="">
                </div>

                <div class="mb-3">
                    <label for="City">Province<span>*</span></label>
                    <input type="text" class="form-control" id="Province" name="Province" placeholder="Enter Your Province" required="">
                </div>

                <div class="mb-3">
                    <label for="currency">Currency<span>*</span></label>
                    <select class="form-control ignore" id="currency" name="currency" required>
                        <option selected>Please select your currency</option>
                        <option value="INR">INR - Indian Rupee (India)</option>
                        <option value="CAD">CAD - Canadian Dollar (Canada)</option>

                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="currency">Level<span>*</span></label>
                    <select class="form-control ignore" id="level" name="level" required>
                        <option selected>Please select your level</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Amateur">Amateur</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Intermediate +">Intermediate +</option>
                        <option value="Advance">Advance</option>
                        

                    </select>
                </div>

                <div class="mb-3">
                    <label for="timezone-offset">Time Zone<span>*</span></label>
                    <select class="form-control span5" name="timezone_offset" id="timezone-offset" required>
                        <option selected>Please select your time zone</option>
                        <!--<option value="-12:00">(GMT -12:00) Eniwetok, Kwajalein</option>-->
                        <!--<option value="-11:00">(GMT -11:00) Midway Island, Samoa</option>-->
                        <!--<option value="-10:00">(GMT -10:00) Hawaii</option>-->
                        <!--<option value="-09:50">(GMT -9:30) Taiohae</option>-->
                        <!--<option value="-09:00">(GMT -9:00) Alaska</option>-->
                        <!--<option value="-08:00">(GMT -8:00) Pacific Time (US &amp; Canada)</option>-->
                        <!--<option value="-07:00">(GMT -7:00) Mountain Time (US &amp; Canada)</option>-->
                        <!--<option value="-06:00">(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>-->
                        <!--<option value="-05:00">(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>-->
                        <!--<option value="-04:50">(GMT -4:30) Caracas</option>-->
                        <!--<option value="-04:00">(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>-->
                        <!--<option value="-03:50">(GMT -3:30) Newfoundland</option>-->
                        <!--<option value="-03:00">(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>-->
                        <!--<option value="-02:00">(GMT -2:00) Mid-Atlantic</option>-->
                        <!--<option value="-01:00">(GMT -1:00) Azores, Cape Verde Islands</option>-->
                        <!--<option value="+00:00">(GMT) Western Europe Time, London, Lisbon, Casablanca</option>-->
                        <!--<option value="+01:00">(GMT +1:00) Brussels, Copenhagen, Madrid, Paris</option>-->
                        <!--<option value="+02:00">(GMT +2:00) Kaliningrad, South Africa</option>-->
                        <!--<option value="+03:00">(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>-->
                        <!--<option value="+03:50">(GMT +3:30) Tehran</option>-->
                        <!--<option value="+04:00">(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>-->
                        <!--<option value="+04:50">(GMT +4:30) Kabul</option>-->
                        <!--<option value="+05:00">(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>-->
                        <!--<option value="+05:50">(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>-->
                        <!--<option value="+05:75">(GMT +5:45) Kathmandu, Pokhara</option>-->
                        <!--<option value="+06:00">(GMT +6:00) Almaty, Dhaka, Colombo</option>-->
                        <!--<option value="+06:50">(GMT +6:30) Yangon, Mandalay</option>-->
                        <!--<option value="+07:00">(GMT +7:00) Bangkok, Hanoi, Jakarta</option>-->
                        <!--<option value="+08:00">(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>-->
                        <!--<option value="+08:75">(GMT +8:45) Eucla</option>-->
                        <!--<option value="+09:00">(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>-->
                        <!--<option value="+09:50">(GMT +9:30) Adelaide, Darwin</option>-->
                        <!--<option value="+10:00">(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>-->
                        <!--<option value="+10:50">(GMT +10:30) Lord Howe Island</option>-->
                        <!--<option value="+11:00">(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>-->
                        <!--<option value="+11:50">(GMT +11:30) Norfolk Island</option>-->
                        <!--<option value="+12:00">(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>-->
                        <!--<option value="+12:75">(GMT +12:45) Chatham Islands</option>-->
                        <!--<option value="+13:00">(GMT +13:00) Apia, Nukualofa</option>-->
                        <!--<option value="+14:00">(GMT +14:00) Line Islands, Tokelau</option>-->
                        <!--<option value="-08:00">(GMT -8:00) Pacific Time (Canada)</option>-->
                        <!--<option value="-07:00">(GMT -7:00) Mountain Time (Canada)</option>-->
                        <!--<option value="-06:00">(GMT -6:00) Central Time (Canada)</option>-->
                        <option value="-05:00">(GMT -5:00) Eastern Time (Canada)</option>
                        <!--<option value="-04:00">(GMT -4:00) Atlantic Time (Canada)</option>-->
                        <!--<option value="-03:30">(GMT -3:30) Newfoundland Time (Canada)</option>-->
                        <option value="+05:30">(GMT +5:30) Indian Standard Time (New Delhi)</option>

                    </select>
                </div>

                <div class="mb-3">
                    <label for="usertype">Type<span>*</span></label>
                    <select class="form-control" name="usertype" id="usertype" required>
                        <option selected>Please select type</option>
                        <option value="Player">Player</option>
                        <option value="Host">Host</option>
                        <option value="Trainer">Trainer</option>
                    </select>
                </div>



                <button type="submit" class="btn btn-outline-secondary login-btn w-100 mb-3">Submit</button>

            </form>
        </div>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        $('#registerForm').on('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            // Gather form data
            let formData = {
                name: $('#name').val().trim(),
                email: $('#email').val().trim(),
                email_permission: $('input[name="EmailPermission"]:checked').val(),
                whatsapp_number: $('#number').val().trim(),
                call_permission: $('input[name="CallPermission"]:checked').val(),
                date_of_birth: $('#dateofbirth').val(),
                gender: $('input[name="GenderRadioOptions"]:checked').val(),
                games: $('input[name^="GamesRadio"]:checked').val(),
                address: $('#Address').val().trim(),
                city: $('#City').val().trim(),
                country: $('#Country').val().trim(),
                province: $('#Province').val().trim(),
                currency: $('#currency').val(),
                level: $('#level').val(),
                timezone_offset: $('#timezone-offset').val(),
                usertype: $('#usertype').val()
            };

            // Client-side validation
            let validationErrors = [];
            if (!formData.name) validationErrors.push('Name is required.');
            if (!formData.email || !validateEmail(formData.email)) validationErrors.push('Valid email is required.');
            if (!formData.email_permission) validationErrors.push('Email permission is required.');
            if (!formData.whatsapp_number || isNaN(formData.whatsapp_number)) validationErrors.push('Valid WhatsApp number is required.');
            if (!formData.call_permission) validationErrors.push('Call permission is required.');
            // if (!formData.date_of_birth) validationErrors.push('Date of birth is required.');
            if (!formData.gender) validationErrors.push('Gender is required.');
            if (!formData.games) validationErrors.push('At least one game must be selected.');
            // if (!formData.address) validationErrors.push('Address is required.');
            if (!formData.city) validationErrors.push('City is required.');
            if (!formData.country) validationErrors.push('Country is required.');
            if (!formData.province) validationErrors.push('Province is required.');
            if (!formData.currency || formData.currency === 'Please select your currency') validationErrors.push('Currency is required.');
            if (!formData.level || formData.level === 'Please select your level') validationErrors.push('Level is required.');
            if (!formData.timezone_offset || formData.timezone_offset === 'Please select your time zone') validationErrors.push('Time zone is required.');
            if (!formData.usertype || formData.usertype === 'Please select type') validationErrors.push('User type is required.');

            if (validationErrors.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Errors',
                    html: validationErrors.join('<br>'),
                    confirmButtonText: 'OK'
                });
                return;
            }

            // AJAX call to PHP API
            $.ajax({
                url: '../../api/register.php', // Replace with your PHP API endpoint
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'You are registered successfully. Contact admin on whats app (+1 (437) 981-0512) to recieve the credentials.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Optionally clear the form or close modal
                            // $('form')[0].reset();
                                window.location.href = 'index.php'; // Replace with your desired URL

                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonText: 'Try Again'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'AJAX Error!',
                        text: `${status} - ${error}`,
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        // Function to validate email format
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
    });
</script>

</script>

</script>