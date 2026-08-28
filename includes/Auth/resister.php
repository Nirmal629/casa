<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$today = date('Y-m-d');
?>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Scoped styling to not break the rest of your site */
    .creative-modal-wrapper {
        font-family: 'Inter', sans-serif;
    }

    .creative-modal-wrapper .modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .creative-modal-wrapper .modal-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border-bottom: none;
        padding: 1.5rem 2rem;
    }

    .creative-modal-wrapper .modal-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1.25rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .creative-modal-wrapper .btn-close {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        opacity: 1;
        transition: all 0.3s ease;
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    .creative-modal-wrapper .btn-close:hover {
        background-color: rgba(255, 255, 255, 0.4);
        transform: rotate(90deg);
    }

    .creative-modal-wrapper .modal-body {
        padding: 2rem;
        background-color: #f8fafc;
    }

    /* Premium Form Inputs */
    .creative-modal-wrapper .form-label-custom {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .creative-modal-wrapper .form-control-custom,
    .creative-modal-wrapper .form-select-custom {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
        color: #1e293b;
        background-color: #ffffff;
        transition: all 0.3s ease;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .creative-modal-wrapper .form-control-custom:focus,
    .creative-modal-wrapper .form-select-custom:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        outline: none;
    }

    /* WhatsApp Input Group */
    .creative-modal-wrapper .input-group-text-wa {
        background: #25D366;
        color: white;
        border: 2px solid #25D366;
        border-right: none;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        font-size: 1.1rem;
    }

    .creative-modal-wrapper .input-wa {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Custom Radio Buttons */
    .creative-modal-wrapper .custom-radio-group {
        display: flex;
        gap: 15px;
    }

    .creative-modal-wrapper .custom-radio {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .creative-modal-wrapper .custom-radio input {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        margin: 0;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .creative-modal-wrapper .custom-radio input:checked {
        border-color: #6366f1;
        border-width: 6px;
    }

    .creative-modal-wrapper .custom-radio label {
        font-size: 0.95rem;
        color: #334155;
        font-weight: 500;
        cursor: pointer;
    }

    /* Info Box */
    .creative-modal-wrapper .info-box-premium {
        background: linear-gradient(to right, #eff6ff, #e0e7ff);
        border-left: 5px solid #4f46e5;
        border-radius: 12px;
        padding: 1.25rem;
        font-size: 0.85rem;
        color: #1e3a8a;
        margin-top: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .creative-modal-wrapper .info-box-premium h6 {
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #3730a3;
        margin-bottom: 0.75rem;
    }

    .creative-modal-wrapper .info-box-premium p {
        margin-bottom: 0.5rem;
        line-height: 1.5;
    }

    /* Submit Button */
    .creative-modal-wrapper .btn-submit-premium {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.8rem 1.5rem;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    .creative-modal-wrapper .btn-submit-premium:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 15px 20px -3px rgba(99, 102, 241, 0.4);
    }

    .creative-modal-wrapper .btn-submit-premium:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    /* Row Spacing */
    .creative-modal-wrapper .form-row {
        margin-bottom: 1.2rem;
    }
</style>

<!------Register Request Modal------->
<div class="modal fade creative-modal-wrapper" id="registerRequestModal" tabindex="-1"
    aria-labelledby="registerRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="registerRequestModalLabel">
                    <i class="fa-solid fa-user-plus"></i> Register Request Now
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="industrialRegisterForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- Hidden DB Fields -->
                    <input type="hidden" name="EMAIL_PERMISSION" value="Yes">
                    <input type="hidden" name="CALL_PERMISSION" value="Yes">
                    <input type="hidden" name="GAMES" value="Badminton">
                    <input type="hidden" name="ADDRESS" value="N/A">
                    <input type="hidden" name="CURRENCY" value="CAD">
                    <input type="hidden" name="TIMEZONE_OFFSET" value="GMT -5:00 EST">
                    <input type="hidden" name="USERTYPE" value="Player">

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="NAME" class="form-control form-control-custom w-100"
                                placeholder="e.g. John Doe" style="text-transform: capitalize;"
                                oninput="this.value = this.value.replace(/\b\w/g, l => l.toUpperCase())" required>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Email <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="email" name="EMAIL" class="form-control form-control-custom w-100"
                                placeholder="your@email.com" required>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">WhatsApp <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <div class="position-relative">
                                <i class="fab fa-whatsapp text-success position-absolute"
                                    style="left: 15px; top: 50%; transform: translateY(-50%); font-size: 1.25rem; z-index: 10;"></i>
                                <input type="tel" name="WHATSAPP_NUMBER" class="form-control form-control-custom w-100"
                                    style="padding-left: 2.8rem;" placeholder="+1 (555) 000-0000" required>
                            </div>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">DOB</label>
                        <div class="col-sm-8">
                            <input type="date" name="DOB" class="form-control form-control-custom w-100"
                                value="<?php echo $today; ?>">
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Gender <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <div class="custom-radio-group">
                                <label class="custom-radio">
                                    <input type="radio" name="GENDER" value="Male" checked>
                                    <span>Male</span>
                                </label>
                                <label class="custom-radio">
                                    <input type="radio" name="GENDER" value="Female">
                                    <span>Female</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Level <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select name="LEVEL" class="form-select form-select-custom w-100">
                                <option value="Beginner">Beginner</option>
                                <option value="Amateur">Amateur</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Intermediate +" selected>Intermediate +</option>
                                <option value="Advance">Advance</option>
                            </select>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Location <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <div class="d-flex gap-2">
                                <select name="COUNTRY" class="form-select form-select-custom flex-fill">
                                    <option value="Canada">Canada</option>
                                </select>
                                <select name="PROVINCE" class="form-select form-select-custom flex-fill">
                                    <option value="Ontario">Ontario</option>
                                </select>
                                <select name="CITY" class="form-select form-select-custom flex-fill">
                                    <option value="GTA">GTA</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Area <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select name="AREA" class="form-select form-select-custom w-100" required>
                                <option value="">-- Select Area --</option>
                                <optgroup label="Toronto Districts">
                                    <option>Downtown Toronto</option>
                                    <option>North York</option>
                                    <option>Scarborough</option>
                                    <option>Etobicoke</option>
                                    <option>East York</option>
                                    <option>York</option>
                                    <option>Midtown Toronto</option>
                                    <option>Beaches</option>
                                    <option>Liberty Village</option>
                                    <option>Leslieville</option>
                                </optgroup>
                                <optgroup label="Peel Region">
                                    <option>Mississauga</option>
                                    <option>Brampton</option>
                                    <option>Caledon</option>
                                </optgroup>
                                <optgroup label="York Region">
                                    <option>Markham</option>
                                    <option>Vaughan</option>
                                    <option>Richmond Hill</option>
                                    <option>Aurora</option>
                                    <option>Newmarket</option>
                                    <option>Whitchurch-Stouffville</option>
                                    <option>East Gwillimbury</option>
                                    <option>King City</option>
                                    <option>Georgina</option>
                                </optgroup>
                                <optgroup label="Durham Region">
                                    <option>Pickering</option>
                                    <option>Ajax</option>
                                    <option>Whitby</option>
                                    <option>Oshawa</option>
                                    <option>Clarington</option>
                                    <option>Uxbridge</option>
                                    <option>Scugog</option>
                                    <option>Brock</option>
                                </optgroup>
                                <optgroup label="Halton Region">
                                    <option>Burlington</option>
                                    <option>Oakville</option>
                                    <option>Milton</option>
                                    <option>Halton Hills</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="row form-row align-items-center">
                        <label class="col-sm-4 form-label-custom">Referral</label>
                        <div class="col-sm-8">
                            <input type="text" name="REFERRAL" class="form-control form-control-custom w-100"
                                placeholder="Existing player, online, or club name">
                        </div>
                    </div>

                    <div class="info-box-premium">
                        <h6><i class="fa-solid fa-circle-info"></i> Important Login Information</h6>
                        <hr style="border-color: #a5b4fc; margin: 0.5rem 0 0.75rem 0; opacity: 0.5;">
                        <p><strong>For Players:</strong> If your location does not appear in the dropdown menu, there is
                            currently no registered host for your city.</p>
                        <p>Please request your tournament organizer or club administrator to contact us for host access.
                            Once onboarded, players from that location can join.</p>
                        <p><em>Note: After logging in, you must go to <strong>Preferences Settings</strong> and select
                                your club to view and join events.</em></p>
                        <p class="mb-0 mt-2"><strong>For Hosts:</strong> <a href="#"
                                class="text-decoration-none fw-bold" style="color:#4f46e5;">Contact us</a> for access.
                        </p>
                    </div>

                    <!-- Message Area -->
                    <div id="responseMsg" class="mt-3 text-center py-2 px-3 rounded"
                        style="display:none; font-weight:600; font-size: 0.95rem;"></div>

                    <div class="mt-4">
                        <button type="submit" id="submitBtn" class="btn-submit-premium w-100">
                            <span id="btnText">Complete Registration <i class="fa-solid fa-arrow-right ms-2"></i></span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true" style="display: none;"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#industrialRegisterForm').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#submitBtn');
            const $btnText = $('#btnText');
            const $btnSpinner = $('#btnSpinner');
            const $msg = $('#responseMsg');

            // Change text to processing and disable
            $btn.prop('disabled', true);
            $btnText.text('Processing...');
            $btnSpinner.show();
            $msg.hide();

            $.ajax({
                url: typeof BASE_URL !== 'undefined' ? BASE_URL + 'api/add_user.php' : 'api/add_user.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        // 1. Show success message (styled nicely)
                        $msg.css({
                            'background': '#d1fae5',
                            'color': '#065f46',
                            'border': '1px solid #a7f3d0'
                        }).show().html('<i class="fa-solid fa-circle-check"></i> ' + res.message);

                        // 2. Clear the form
                        $('#industrialRegisterForm')[0].reset();

                        // 3. REMOVE the processing button and ADD "Go to Home" button
                        $btn.hide();
                        $btn.after('<a href="index.php" class="btn-submit-premium w-100 d-block text-center text-decoration-none mt-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">Go to Home <i class="fa-solid fa-house ms-2"></i></a>');

                    } else {
                        // Show error message and reset button
                        $msg.css({
                            'background': '#fee2e2',
                            'color': '#991b1b',
                            'border': '1px solid #fecaca'
                        }).show().html('<i class="fa-solid fa-triangle-exclamation"></i> ' + res.message);

                        $btn.prop('disabled', false);
                        $btnText.html('Complete Registration <i class="fa-solid fa-arrow-right ms-2"></i>');
                        $btnSpinner.hide();
                    }
                },
                error: function () {
                    $msg.css({
                        'background': '#fee2e2',
                        'color': '#991b1b',
                        'border': '1px solid #fecaca'
                    }).show().html('<i class="fa-solid fa-triangle-exclamation"></i> System Error: Connection failed.');

                    $btn.prop('disabled', false);
                    $btnText.html('Complete Registration <i class="fa-solid fa-arrow-right ms-2"></i>');
                    $btnSpinner.hide();
                }
            });
        });
    });
</script>