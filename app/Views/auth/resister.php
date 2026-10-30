<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$today = date('Y-m-d');
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- Registration Request Modal -->
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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- Hidden DB / API Fields -->
                    <input type="hidden" name="EMAIL_PERMISSION" value="Yes">
                    <input type="hidden" name="CALL_PERMISSION" value="Yes">
                    <input type="hidden" name="GAMES" value="Badminton">
                    <input type="hidden" name="ADDRESS" value="N/A">
                    <input type="hidden" name="CURRENCY" value="CAD">
                    <input type="hidden" name="TIMEZONE_OFFSET" value="GMT -5:00 EST">
                    <input type="hidden" name="USERTYPE" value="Player">

                    <!-- Full Name -->
                    <div class="row form-row align-items-center">
                        <label for="regName" class="col-sm-4 form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" id="regName" name="NAME" class="form-control form-control-custom name-input-capitalized w-100"
                                placeholder="e.g. John Doe" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="row form-row align-items-center">
                        <label for="regEmail" class="col-sm-4 form-label-custom">Email <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="email" id="regEmail" name="EMAIL" class="form-control form-control-custom w-100"
                                placeholder="your@email.com" required autocomplete="email">
                        </div>
                    </div>

                    <!-- WhatsApp Number -->
                    <div class="row form-row align-items-center">
                        <label for="regWhatsapp" class="col-sm-4 form-label-custom">WhatsApp <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <div class="whatsapp-input-wrapper">
                                <i class="fab fa-whatsapp text-success whatsapp-icon"></i>
                                <input type="tel" id="regWhatsapp" name="WHATSAPP_NUMBER" class="form-control form-control-custom whatsapp-input w-100"
                                    placeholder="+1 (555) 000-0000" required autocomplete="tel">
                            </div>
                        </div>
                    </div>

                    <!-- Date of Birth -->
                    <div class="row form-row align-items-center">
                        <label for="regDob" class="col-sm-4 form-label-custom">DOB</label>
                        <div class="col-sm-8">
                            <input type="date" id="regDob" name="DOB" class="form-control form-control-custom w-100"
                                value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <!-- Gender -->
                    <div class="row form-row align-items-center">
                        <span class="col-sm-4 form-label-custom">Gender <span class="text-danger">*</span></span>
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

                    <!-- Level -->
                    <div class="row form-row align-items-center">
                        <label for="regLevel" class="col-sm-4 form-label-custom">Level <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select id="regLevel" name="LEVEL" class="form-select form-select-custom w-100">
                                <option value="Beginner">Beginner</option>
                                <option value="Amateur">Amateur</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Intermediate +" selected>Intermediate +</option>
                                <option value="Advance">Advance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="row form-row align-items-center">
                        <span class="col-sm-4 form-label-custom">Location <span class="text-danger">*</span></span>
                        <div class="col-sm-8">
                            <div class="d-flex gap-2">
                                <select name="COUNTRY" aria-label="Country" class="form-select form-select-custom flex-fill">
                                    <option value="Canada">Canada</option>
                                </select>
                                <select name="PROVINCE" aria-label="Province" class="form-select form-select-custom flex-fill">
                                    <option value="Ontario">Ontario</option>
                                </select>
                                <select name="CITY" aria-label="City" class="form-select form-select-custom flex-fill">
                                    <option value="GTA">GTA</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Area -->
                    <div class="row form-row align-items-center">
                        <label for="regArea" class="col-sm-4 form-label-custom">Area <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select id="regArea" name="AREA" class="form-select form-select-custom w-100" required>
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

                    <!-- Referral -->
                    <div class="row form-row align-items-center">
                        <label for="regReferral" class="col-sm-4 form-label-custom">Referral</label>
                        <div class="col-sm-8">
                            <input type="text" id="regReferral" name="REFERRAL" class="form-control form-control-custom w-100"
                                placeholder="Existing player, online, or club name">
                        </div>
                    </div>

                    <!-- Information Box -->
                    <div class="info-box-premium">
                        <h6><i class="fa-solid fa-circle-info"></i> Important Login Information</h6>
                        <hr class="info-box-divider">
                        <p><strong>For Players:</strong> If your location does not appear in the dropdown menu, there is
                            currently no registered host for your city.</p>
                        <p>Please request your tournament organizer or club administrator to contact us for host access.
                            Once onboarded, players from that location can join.</p>
                        <p><em>Note: After logging in, you must go to <strong>Preferences Settings</strong> and select
                                your club to view and join events.</em></p>
                        <p class="mb-0 mt-2"><strong>For Hosts:</strong> <a href="#"
                                class="text-decoration-none fw-bold info-contact-link">Contact us</a> for access.
                        </p>
                    </div>

                    <!-- Message Area -->
                    <div id="responseMsg" class="mt-3 text-center py-2 px-3 rounded response-msg"></div>

                    <!-- Submit Button -->
                    <div class="mt-4">
                        <button type="submit" id="submitBtn" class="btn-submit-premium w-100">
                            <span id="btnText">Complete Registration <i class="fa-solid fa-arrow-right ms-2"></i></span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assets -->
<link rel="stylesheet" href="/app/Views/css/auth/register.css">
<script src="/app/script/auth/register.js" defer></script>