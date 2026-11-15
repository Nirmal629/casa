<!-----new-game-host------>
<?php
$conn = \App\Config\Database::getMysqli();
$event_venue = $event_venue ?? '';
$desc = '';
$formToast = $_SESSION['flash_toast'] ?? null;
unset($_SESSION['flash_toast']);

$result = mysqli_query($conn, "SELECT DESCRIPTION FROM ca_description WHERE 1 LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $desc = $row['DESCRIPTION'] ?? '';
}
?>

<?= renderToast($formToast) ?>

<div class="newgame_host">
    <div class="custom_card">
        <h6 class="card_heading">New Event</h6>
        <form id="eventFormm" action="/host/host-creat-game.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Helpers\Session::getCsrfToken()) ?>">
        <div class="row">
            <!-- Host Name -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="host-name" class="form-label">Host Name<span>*</span></label>
                <input type="text" class="form-control" id="host-name" name="host_name" placeholder="Enter Full Name" required value="<?=$_SESSION['name']?>">
            </div>

            <!-- Event Country -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventCountry" class="form-label">Event Country<span>*</span></label>
                <input type="text" class="form-control" id="eventCountry" name="event_country" placeholder="Event Country" required>
            </div>

            <!-- Event Province -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventProvince" class="form-label">Event Province<span>*</span></label>
                <input type="text" class="form-control" id="eventProvince" name="event_province" placeholder="Event Province" required>
            </div>

            <!-- Event City -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventCity" class="form-label">Event City<span>*</span></label>
                <input type="text" class="form-control" id="eventCity" name="event_city" placeholder="Event City" required>
            </div>

            <!-- Event Currency -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventCurrency" class="form-label">Event Currency<span>*</span></label>
                <select class="form-select form-control" id="eventCurrency" name="event_currency" required>
                    <!--<option value="USD">USD</option>-->
                    <option value="INR">INR</option>
                    <!--<option value="EUR">EUR</option>-->
                    <!--<option value="GBP">GBP</option>-->
                    <option value="CAD">CAD</option>
                    <!-- Add more currencies as needed -->
                </select>
            </div>

            <!-- Event Venue -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventVenue" class="form-label">Event Venue<span>*</span></label>
                <!--<input type="text" class="form-control" id="eventVenue" name="event_venue" placeholder="Event Venue" required>-->
                <select class="form-select form-control" id="eventVenue" name="event_venue" required>
                    <!--<option value="Epic Badminton">Epic Badminton</option>-->
                    <!--<option value="Hymus Sports">Hymus Sports</option>-->
                    <!--<option value="KeralaNook">Kerala Nook</option>-->
                    <!--<option value="WillieStout">Willie Stout</option>-->
                    <!--<option value="CornerBank">Corner Bank</option>-->
                    <option value="">-- Select Venue --</option>
                    <?php
                    $sqlVenue = "SELECT NAME FROM ca_venue ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
            
                            // If you want to pre-select based on edit mode:
                            $selected = ($venueName == $event_venue) ? "selected" : "";
            
                            echo "<option value=\"$venueName\" $selected>$venueName</option>";
                        }
                    }
                    ?>

                   
                </select>
            </div>

            <!-- Event Category -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventCategory" class="form-label">Event Category<span>*</span></label>
                <select class="form-select form-control" id="eventCategory" name="event_category" required>
                    <!--<option value="Badminton">Badminton Game</option>-->
                    <!--<option value="Tennis">Tennis Game</option>-->
                    <!--<option value="Cricket">Cricket Game</option>-->
                    <!--<option value="Football">Football Game</option>-->
                     <?php if ($_SESSION['usertype'] === 'Host'): ?>
                    <!--<option value="Badminton Game">Badminton Game</option>-->
                    <!--<option value="Tennis Game">Tennis Game</option>-->
                    <!--<option value="Cricket Game">Cricket Game</option>-->
                    <!--<option value="Football Game">Football Game</option>-->
                    <!--<option value="Snacks at Kerala Knook">Snacks at Kerala Knook</option>-->
                    <!--<option value="Outing">Outing</option>-->
                    <!--<option value="Service">Service</option>-->
                    <?php
                    $sqlVenue = "SELECT NAME FROM ca_event_category ORDER BY NAME ASC";
                    $resVenue = mysqli_query($conn, $sqlVenue);
            
                    if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                        while ($row = mysqli_fetch_assoc($resVenue)) {
                            $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
            
                            // If you want to pre-select based on edit mode:
                            $selected = ($venueName == $event_venue) ? "selected" : "";
            
                            echo "<option value=\"$venueName\" $selected>$venueName</option>";
                        }
                    }
                    ?>
                <?php elseif ($_SESSION['usertype'] === 'Trainer'): ?>
                    <option value="Badminton Training">Badminton Training</option>
                    <option value="Tennis Training">Tennis Training</option>
                    <option value="Cricket Training">Cricket Training</option>
                    <option value="Football Training">Football Training</option>
                    <option value="Badminton Game and Training">Badminton Game + Training</option>
                <?php else: ?>
                    <option disabled selected>Please select a valid user type</option>
                <?php endif; ?>
                </select>
            </div>

            <!-- Gender Category -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="genderCategory" class="form-label">Gender Category<span>*</span></label>
                <select class="form-select form-control" id="genderCategory" name="gender_category" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Mix">Mix</option>
                    <option value="Kid">Kids</option>
                    <!--<option value="Training">Training</option>-->
                    <!--<option value="Kids + Training">Kids + Training</option>-->
                </select>
            </div>

            <!-- Gender Skill Level -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="genderSkillLevel" class="form-label">Gender Skill Level<span>*</span></label>
                <select class="form-select form-control" id="genderSkillLevel" name="gender_skill_level" required>
                    <option value="Beginner">Beginner</option>
                    <option value="Amateur">Amateur</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Intermediate +">Intermediate+</option>
                    <option value="Advanced">Advanced</option>
                    <option value="Mix">Mix</option>
                </select>
            </div>

            <!-- Event Type -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventType" class="form-label">Event Type<span>*</span></label>
                <select class="form-select form-control" id="eventType" name="event_type" required>
                    <option value="Public">Public</option>
                    <option value="Invite">Invite Only</option>
                </select>
            </div>

            <!-- Event Date -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventDate" class="form-label">Event Date<span>*</span></label>
                <input type="date" class="form-control" id="eventDate" name="event_date" required>
            </div>

            <!-- Event Time -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventTime" class="form-label">From Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="eventTime" name="event_time" required step="00:15">-->
        <select class="form-control" id="eventTime" name="event_time" required></select>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventTime" class="form-label">To Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="toTime" name="to_time" required step="1800">-->
                <select class="form-control" id="toTime" name="to_Time" required></select>

            </div>
            <input type="hidden" id="freezeType" name="freeze_type" value="custom">
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="freezeDate" class="form-label">Freeze Date<span>*</span></label>
                <input type="date" class="form-control" id="freezeDate" name="freeze_date" required>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="freezeTime" class="form-label">Freeze Time<span>*</span></label>
                <!--<input type="time" class="form-control" id="freezeTime" name="freeze_time" required step="1800">-->
                <select class="form-control" id="freezeTime" name="freeze_time" required></select>
            </div>

            <!-- Event Cost -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventCost" class="form-label">Event Cost ($)<span>*</span></label>
                <input type="number" class="form-control" id="eventCost" name="event_cost" step="0.01" required>
            </div>

            <!-- Event Discount -->
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <label for="eventDiscount" class="form-label">Event Court<span>*</span></label>
                <input type="text" class="form-control" id="eventDiscount" name="event_discount" required>
            </div>
        </div>

        <div class="row">
            <!-- Event Description -->
            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                <label for="eventDescription" class="form-label">Event Description<span>*</span></label>
                <textarea class="form-control" id="eventDescription" name="event_description" rows="10" placeholder="Event description" required><?=$desc?></textarea>
            </div>

            <!-- Event Message -->
            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                <label for="eventMessage" class="form-label">Event Message<span>*</span></label>
                <textarea class="form-control" id="eventMessage" name="event_message" rows="10" placeholder="Message for participants" required>Feather birdie included. Pay at casaclubpayment1@gmail.com</textarea>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
    </div>
</div>

<script>
(function() {
    function initHostCreateGame() {
        var form = document.getElementById("eventFormm");
        if (!form) return;

        // 1. Populate time dropdowns if empty
        var timeSelects = ["eventTime", "toTime", "freezeTime"];
        timeSelects.forEach(function(id) {
            var el = document.getElementById(id);
            if (el && el.options.length === 0) {
                for (var h = 0; h < 24; h++) {
                    for (var m = 0; m < 60; m += 30) {
                        var hh = h < 10 ? "0" + h : String(h);
                        var mm = m < 10 ? "0" + m : String(m);
                        var val = hh + ":" + mm;
                        el.appendChild(new Option(val, val));
                    }
                }
            }
        });

        // 2. Set min date to today
        var today = new Date().toISOString().split('T')[0];
        var eventDateInput = document.getElementById("eventDate");
        var freezeDateInput = document.getElementById("freezeDate");
        if (eventDateInput && !eventDateInput.min) eventDateInput.min = today;
        if (freezeDateInput && !freezeDateInput.min) freezeDateInput.min = today;

        if (eventDateInput && freezeDateInput) {
            eventDateInput.addEventListener("change", function() {
                if (eventDateInput.value && !freezeDateInput.value) {
                    freezeDateInput.value = eventDateInput.value;
                }
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initHostCreateGame);
    } else {
        initHostCreateGame();
    }
})();
</script>

<?= renderDynamicCalendarInit(['date' => '#eventDate', 'freezeDate' => '#freezeDate', 'fromTime' => '#eventTime', 'toTime' => '#toTime', 'freezeTime' => '#freezeTime', 'freezeType' => '#freezeType']) ?>

