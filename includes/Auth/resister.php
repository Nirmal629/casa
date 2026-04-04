<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$today = date('Y-m-d');
?>

<style>
    .customModal_wrap {
        display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.7); z-index: 99999; align-items: center; justify-content: center;
    }
    .customModal_body {
        background: #fff; border-radius: 12px; padding: 30px; width: 95%; max-width: 550px;
        position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.4); max-height: 95vh; overflow-y: auto;
    }
    .info-box { background: #f8f9fa; border-radius: 6px; padding: 15px; font-size: 0.82rem; color: #444; }
</style>

<section class="customModal_wrap" id="resisterEvent_add">
    <div class="customModal_body">
        <h6 class="fw-bold mb-4 border-bottom pb-2" style="font-size: 1.3rem;">Register Request Now</h6>
        <button style="position:absolute; top:15px; right:15px; border:none; background:none; cursor:pointer;" onclick="$('#resisterEvent_add').hide()"><i class="fa-solid fa-xmark"></i></button>

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

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">Name*</label>
                <div class="col-sm-8">
                    <input type="text" name="NAME" class="form-control form-control-sm" style="text-transform: capitalize;" oninput="this.value = this.value.replace(/\b\w/g, l => l.toUpperCase())" required>
                </div>
            </div>

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">Email*</label>
                <div class="col-sm-8">
                    <input type="email" name="EMAIL" class="form-control form-control-sm" required>
                </div>
            </div>

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">Contact*</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-success text-white border-success"><i class="fab fa-whatsapp"></i></span>
                        <input type="tel" name="WHATSAPP_NUMBER" class="form-control" placeholder="Whatsapp Number" required>
                    </div>
                </div>
            </div>

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">DOB</label>
                <div class="col-sm-8">
                    <input type="date" name="DOB" class="form-control form-control-sm" value="<?php echo $today; ?>">
                </div>
            </div>

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">Gender*</label>
                <div class="col-sm-8 d-flex gap-4">
                    <div class="form-check"><input class="form-check-input" type="radio" name="GENDER" value="Male" checked><label class="form-check-label small">Male</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="GENDER" value="Female"><label class="form-check-label small">Female</label></div>
                </div>
            </div>

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">Level*</label>
                <div class="col-sm-8">
                    <select name="LEVEL" class="form-select form-select-sm">
                        <option value="Beginner">Beginner</option>
                        <option value="Amateur">Amateur</option>
                        <option value="Intermediate" selected>Intermediate</option>
                        <option value="Intermediate +" selected>Intermediate +</option> <!-- Added and made default -->
                        <option value="Advance">Advance</option>
                    </select>
                </div>
            </div>

            <div class="row mb-2 align-items-center">
                <label class="col-sm-4 small fw-bold">Location</label>
                <div class="col-sm-8 d-flex gap-1">
                    <select name="COUNTRY" class="form-select form-select-sm"><option value="Canada">Canada</option></select>
                    <select name="PROVINCE" class="form-select form-select-sm"><option value="Ontario">Ontario</option></select>
                    <select name="CITY" class="form-select form-select-sm"><option value="GTA">GTA</option></select>
                </div>
                  <div class="row mb-3 align-items-center">
                <label class="col-sm-3 form-label-bold">Area*</label>
                <div class="col-sm-9">
                    <select name="AREA" class="form-select" required>
                        <option value="">-- Select Area --</option>
                        <optgroup label="Toronto Districts">
                            <option>Downtown Toronto</option><option>North York</option><option>Scarborough</option>
                            <option>Etobicoke</option><option>East York</option><option>York</option>
                            <option>Midtown Toronto</option><option>Beaches</option><option>Liberty Village</option><option>Leslieville</option>
                        </optgroup>
                        <optgroup label="Peel Region">
                            <option>Mississauga</option><option>Brampton</option><option>Caledon</option>
                        </optgroup>
                        <optgroup label="York Region">
                            <option>Markham</option><option>Vaughan</option><option>Richmond Hill</option>
                            <option>Aurora</option><option>Newmarket</option><option>Whitchurch-Stouffville</option>
                            <option>East Gwillimbury</option><option>King City</option><option>Georgina</option>
                        </optgroup>
                        <optgroup label="Durham Region">
                            <option>Pickering</option><option>Ajax</option><option>Whitby</option>
                            <option>Oshawa</option><option>Clarington</option><option>Uxbridge</option>
                            <option>Scugog</option><option>Brock</option>
                        </optgroup>
                        <optgroup label="Halton Region">
                            <option>Burlington</option><option>Oakville</option><option>Milton</option><option>Halton Hills</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-4 small fw-bold">Referral</label>
                <div class="col-sm-8">
                    <input type="text" name="REFERRAL" class="form-control form-control-sm" placeholder="Existing player name, Online, Club name">
                </div>
            </div>

            <div class="info-box border-top mt-4 pt-3">
                <p class="fw-bold mb-1">Important Login Information</p>
<div class="info-box border-top mt-4 pt-3">
                <p class="fw-bold mb-2">For Players:</p>
                <p class="mb-2">If your Country → Province → City does not appear in the dropdown menu, it means there is currently no registered host for your city on this platform.</p>
                <p class="mb-2">In such cases, please request your tournament organizer or club administrator to contact us to obtain host access. Casa will onboard the host by setting up the country, province, city, and club. Once this setup is complete, players from that location will be able to join.</p>
                <p class="mb-0">After logging in, players must go to <strong>Preferences Settings</strong> and select their club. This step is required to view and join hosted games and events. For Hosts: <a href="#" style="color:#0d6efd; text-decoration:none;">Contact us</a> for access.</p>
            </div>
                <p class="mb-0"><strong>For Hosts:</strong> <a href="#" style="color:#0d6efd; text-decoration:none;">Contact us</a> for access.</p>
            </div>

            <!-- Message Area -->
            <div id="responseMsg" class="mt-3 text-center py-2 rounded" style="display:none; font-weight:bold;"></div>

            <div class="mt-4">
                <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 fw-bold">Submit Registration</button>
            </div>
        </form>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#industrialRegisterForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#submitBtn');
        const $msg = $('#responseMsg');
        
        // Change text to processing and disable
        $btn.prop('disabled', true).text('Processing...');
        $msg.hide();

        $.ajax({
            url: '/staging/api/add_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    // 1. Show success message
                    $msg.css({'background':'#d1e7dd','color':'#0f5132'}).show().html(res.message);
                    
                    // 2. Clear the form
                    $('#industrialRegisterForm')[0].reset();
                    
                    // 3. REMOVE the processing button and ADD "Go to Home" button
                    $btn.hide(); 
                    $btn.after('<a href="index.php" class="btn btn-success w-100 py-2 fw-bold mt-2">Go to Home</a>');
                    
                } else {
                    // Show error message and reset button so user can try again
                    $msg.css({'background':'#f8d7da','color':'#842029'}).show().html(res.message);
                    $btn.prop('disabled', false).text('Submit Registration');
                }
            },
            error: function() {
                $msg.css({'background':'#f8d7da','color':'#842029'}).show().html('System Error: Connection failed.');
                $btn.prop('disabled', false).text('Submit Registration');
            }
        });
    });
});
</script>