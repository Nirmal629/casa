<!----Scheduled-game---->
<?php
$cardCssPath = __DIR__ . '/../css/sports-session-card.css';
$cardCssVer = file_exists($cardCssPath) ? filemtime($cardCssPath) : '1.0';
?>
<link rel="stylesheet" href="/app/Views/css/sports-session-card.css?v=<?= $cardCssVer ?>">
<section class="">
    <?php
    [$hostId, $userType] = [(int) ($_SESSION['user_id'] ?? 0), $_SESSION['usertype'] ?? 'Host'];
    ?>
    <div class="custom_card">
        <div class="d-flex align-items-center flex-wrap gap-3 mb-4">
            <h6 class="card_heading mb-0">Upcoming Game</h6>
            <form id="filterScheduleForm" data-host-id="<?= (int) $hostId ?>" onsubmit="return false;" class="m-0">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select class="form-select" id="year" aria-label="Year" data-casa-dropdown data-size="sm"
                            data-icon="fa-regular fa-calendar"></select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" id="month" aria-label="Month" data-casa-dropdown data-size="sm"
                            data-icon="fa-regular fa-calendar-days"></select>
                    </div>
                </div>
            </form>
        </div>


        <div class="discoverGames_wraper hostWrapper">
            <?php
            if (isset($scheduledEvents) && is_array($scheduledEvents)) {
                $events = $scheduledEvents;
                $hostId = $host_id ?? 0;
                $year = (int) date('Y');
                $month = (int) date('n');
                $eventCategory = '';
                $isPlayer = false;
                include __DIR__ . '/scheduled-events-cards.php';
            } else {
                $hostController = new \App\Controllers\HostController();
                $hostController->filterSchedule();
            }
            ?>
        </div>
    </div>
</section>


<!-----View-modal------->
<?php
if (!isset($uniqueGenders) || !is_array($uniqueGenders)) {
    $userModel = $userModel ?? new \App\Models\UserModel();
    $uniqueGenders = $userModel->getAllUniqueGenders();
}
if (!in_array('Mix', $uniqueGenders, true)) {
    $uniqueGenders[] = 'Mix';
}
if (!isset($uniqueLevels) || !is_array($uniqueLevels)) {
    $userModel = $userModel ?? new \App\Models\UserModel();
    $uniqueLevels = $userModel->getAllUniqueVerifiedLevels();
}
if (!in_array('Mix', $uniqueLevels, true)) {
    $uniqueLevels[] = 'Mix';
}
?>
<section class="customModal_wrap hostgameview_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Add Players</h6>
        <button type="button" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="row align-items-end g-2">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label" style="visibility:hidden">Search</label>
                <input type="text" name="search" id="search" placeholder="Enter text to search" class="form-control" />
                <input type="hidden" id="playdt" data-game-id="" data-host-id="" />
            </div>

            <div class="col-6 col-md-4">
                <label for="sgenderCategoryy" class="form-label">Gender<span>*</span></label>
                <select class="form-select form-control" id="sgenderCategoryy" data-casa-dropdown data-size="md"
                    data-width="100%">
                    <?php foreach ($uniqueGenders as $gOpt): ?>
                        <option value="<?= htmlspecialchars($gOpt) ?>"><?= htmlspecialchars($gOpt === 'Kid' ? 'Kids' : $gOpt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-4">
                <label for="sgenderSkillLevell" class="form-label">Level<span>*</span></label>
                <select class="form-select form-control" id="sgenderSkillLevell" data-casa-dropdown data-size="md"
                    data-width="100%">
                    <?php foreach ($uniqueLevels as $lvlOpt): ?>
                        <option value="<?= htmlspecialchars($lvlOpt) ?>"><?= htmlspecialchars($lvlOpt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="customModal_content">
            <hr />

            <!--<div class="sectionheading_wrap mb-4">-->
            <!--    <div>-->
            <!--        <h2 class="heading">Badminton play</h2>-->
            <!--        <h6 class="sub_text">Hosted by Anurag</h6>-->
            <!--    </div>-->

            <!--    <div class="hostProfile_big">-->
            <!--        <img src="assets/images/profile.jpg" class="img-fluid" alt="..">-->
            <!--    </div>-->
            <!--</div>-->

            <!--<div class="dateandtime_wrap">-->
            <!--    <i class="fa-regular fa-clock"></i>-->
            <!--    <div class="">-->
            <!--        <h4 class="date">Tuesday, 24 Dec 2024</h4>-->
            <!--        <p class="time">08:00 PM to 09:00 PM</p>-->
            <!--    </div>-->
            <!--</div>-->

            <!--<div class="dateandtime_wrap">-->
            <!--    <i class="fa-solid fa-location-dot"></i>-->
            <!--    <div class="">-->
            <!--        <p class="time">Lorem ipsum dolor sit amet consectetur adipisicing elit</p>-->
            <!--    </div>-->
            <!--</div>-->

            <!--<h4 class="sub_text mt-2" style="text-decoration: underline;">Game Instructions</h4>-->
            <!--<p class="desc" style="display: flex; align-items: center; gap: 10px;">-->
            <!--    <i class="fa-regular fa-hand-point-right"></i>-->
            <!--    Beginner to Professional-->
            <!--</p>-->

            <!--<hr />-->

            <div class="playerList-container" id="playerList">
            </div>

        </div>
    </div>
</section>

<!---view joined --->
<section class="customModal_wrap hostgameviewjoined_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Joined Players</h6>
        <button type="button" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="customModal_content">
            <hr />
            <div class="playerList-container" id="playerList_joined"></div>
        </div>
    </div>
</section>

<!-----Edit-modal--------->
<section class="customModal_wrap hostgameupdate_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Update Game</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form>
                <div class="row g-2">
                    <input type="hidden" id="evnt_id" value="" />

                    <div class="col-6 col-md-4">
                        <label for="eventCityy" class="form-label">Event City<span>*</span></label>
                        <input type="text" class="form-control" id="eventCityy" placeholder="City">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventCurrencyy" class="form-label">Currency<span>*</span></label>
                        <select class="form-select form-control" id="eventCurrencyy" name="event_currency" required>
                            <option value="INR">INR</option>
                            <option value="CAD">CAD</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="eventVenuee" class="form-label">Event Venue<span>*</span></label>
                        <select class="form-select form-control" id="eventVenuee" name="eventVenue" required>
                            <?php
                            $sqlVenue = "SELECT NAME FROM ca_venue ORDER BY NAME ASC";
                            $resVenue = mysqli_query($conn, $sqlVenue);

                            if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                                while ($row = mysqli_fetch_assoc($resVenue)) {
                                    $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
                                    $selected = ($venueName == $event_venue) ? "selected" : "";
                                    echo "<option value=\"$venueName\" $selected>$venueName</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="eventCategoryy" class="form-label">Event Category<span>*</span></label>
                        <select class="form-select form-control" id="eventCategoryy" aria-label="">
                            <?php if ($_SESSION['usertype'] === 'Host'): ?>
                            <?php
                            $sqlVenue = "SELECT NAME FROM ca_event_category ORDER BY NAME ASC";
                            $resVenue = mysqli_query($conn, $sqlVenue);

                            if ($resVenue && mysqli_num_rows($resVenue) > 0) {
                                while ($row = mysqli_fetch_assoc($resVenue)) {
                                    $venueName = htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8');
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

                    <div class="col-6 col-md-4">
                        <label for="genderCategoryy" class="form-label">Gender<span>*</span></label>
                        <select class="form-select form-control" id="genderCategoryy" aria-label="">
                            <option selected value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Mix">Mix</option>
                            <option value="Kid">Kids</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="genderSkillLevell" class="form-label">Skill Level<span>*</span></label>
                        <select class="form-select form-control" id="genderSkillLevell" aria-label="">
                            <option selected value="Beginner">Beginner</option>
                            <option value="Amateur">Amateur</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Intermediate +">Intermediate+</option>
                            <option value="Advance">Advance</option>
                            <option value="Mix">Mix</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventTypee" class="form-label">Event Type<span>*</span></label>
                        <select class="form-select form-control" id="eventTypee" aria-label="">
                            <option selected value="Public">Public</option>
                            <option value="Invite">Invite Only</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="statusevent" class="form-label">Status<span>*</span></label>
                        <select class="form-select form-control" id="statusevent" aria-label="">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventDatee" class="form-label">Event Date<span>*</span></label>
                        <input type="date" class="form-control" id="eventDatee" placeholder="Event Date">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="freezeDatee" class="form-label">Freeze Date<span>*</span></label>
                        <input type="date" class="form-control" id="freezeDatee" placeholder="Freeze Date">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="freezeTimee" class="form-label">Freeze Time<span>*</span></label>
                        <select class="form-select form-control" id="freezeTimee" name="freeze_time" required></select>
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="eventDiscountt" class="form-label">Event Court<span>*</span></label>
                        <input type="text" class="form-control" id="eventDiscountt" placeholder="Event Court">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="eventDescriptionn" class="form-label">Event
                            Description<span>*</span></label>
                        <textarea class="form-control" id="eventDescriptionn" rows="3"
                            placeholder="Bring own bat, shoe, guards....."></textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="eventMessagee" class="form-label">Event Message<span>*</span></label>
                        <textarea class="form-control" id="eventMessagee" rows="3"
                            placeholder="For Participants to put note....."></textarea>
                    </div>

                    <!-- Time & Facility Block -->
                    <div class="col-12 mt-3">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#eacc7f;">
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="eventTimee" class="form-label text-dark">From
                                    Time<span>*</span></label>
                                <select class="form-select form-control" id="eventTimee" name="event_time"
                                    required></select>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="toTimee" class="form-label text-dark">To
                                    Time<span>*</span></label>
                                <select class="form-select form-control" id="toTimee" name="to_Time" required></select>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="hours" class="form-label text-dark">Hours<span></span></label>
                                <input type="text" class="form-control" id="hours" name="hours" readonly>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="facilitycostperhour" class="form-label text-dark">Cost Per
                                    Hour</label>
                                <input type="text" class="form-control" id="facilitycostperhour"
                                    placeholder="Cost Per Hour">
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="courtconfirmed" class="form-label text-dark">Court
                                    Confirmed</label>
                                <input type="text" class="form-control" id="courtconfirmed"
                                    placeholder="Court Confirmed">
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <label for="facilitycost" class="form-label text-dark">Facility Cost
                                    ($)</label>
                                <input type="text" class="form-control" id="facilitycost" placeholder="Facility Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Birdie & Accessories Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#0000001f;">
                            <div class="col-12 col-sm-4">
                                <label for="birdieUsed" class="form-label text-dark">Birdie
                                    Used<span>*</span></label>
                                <input type="number" class="form-control" id="birdieUsed" placeholder="Birdie Used">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="nobirdieUsed" class="form-label text-dark">Birdie
                                    Price<span>*</span></label>
                                <input type="number" class="form-control" id="nobirdieUsed" placeholder="Birdie Price">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="accessoriesCost" class="form-label text-dark">Accessories
                                    Cost</label>
                                <input type="text" class="form-control" id="accessoriesCost"
                                    placeholder="Accessories Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Snacks & Event Cost Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#d5eaae;">
                            <div class="col-12 col-sm-4">
                                <label for="clubClost" class="form-label text-dark">Club
                                    Cost<span>*</span></label>
                                <input type="number" class="form-control" id="clubClost" placeholder="Club Cost">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="snackscost" class="form-label text-dark">Snacks Cost</label>
                                <input type="text" class="form-control" id="snackscost" placeholder="Snacks Cost">
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="eventtotalCostt" class="form-label text-dark">Total Event Cost
                                    ($)</label>
                                <input type="text" class="form-control" id="eventtotalCostt" placeholder="Total Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Player Info Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2" style="background-color:#78e1f7;">
                            <div class="col-6 col-sm-3">
                                <label for="playersJoined" class="form-label text-dark">Players
                                    Joined</label>
                                <input type="text" class="form-control" id="playersJoined" placeholder="Players Joined"
                                    readonly>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="playersConfirmed" class="form-label text-dark">Players
                                    Confirmed</label>
                                <input type="text" class="form-control" id="playersConfirmed" placeholder="Confirmed"
                                    readonly>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="eventCostt" class="form-label text-dark">Player Cost
                                    ($)<span>*</span></label>
                                <input type="text" class="form-control" id="eventCostt" placeholder="Event Cost">
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="eventtotalplayerCostt" class="form-label text-dark">Total Player
                                    Cost ($)</label>
                                <input type="text" class="form-control" id="eventtotalplayerCostt"
                                    placeholder="Total Player Cost">
                            </div>
                        </div>
                    </div>

                    <!-- Automation & Profit Block -->
                    <div class="col-12">
                        <div class="row mx-0 p-2 rounded mb-2 g-2 align-items-end" style="background-color:#bfd6f7;">
                            <!-- Automation -->
                            <div class="col-12 col-sm-4">
                                <div class="form-check d-flex align-items-center gap-2 mb-2">
                                    <input class="form-check-input" type="checkbox" id="autoConfirm">
                                    <label class="form-check-label mb-0 text-dark" for="autoConfirm">
                                        Automation On
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="form-check d-flex align-items-center gap-2 mb-2">
                                    <input class="form-check-input" type="checkbox" id="updatePlayerPrice">
                                    <label class="form-check-label mb-0 text-dark" for="updatePlayerPrice">
                                        Update Player Price
                                    </label>
                                </div>
                            </div>

                            <!-- Profit Loss -->
                            <div class="col-12 col-sm-4">
                                <label for="profitloss" class="form-label mb-1 text-dark">Profit /
                                    Loss</label>
                                <input type="text" class="form-control" id="profitloss" placeholder="Profit Loss">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="EVENT_IDD">

                    <div class="col-12 d-flex flex-wrap justify-content-center gap-2 mt-3 mb-2">
                        <button type="button" class="btn btn-primary px-4" id="save_btn">Submit</button>
                        <button type="button" class="btn btn-primary px-4" id="copy_btn">Copy Game</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<script type="text/javascript">
    document.addEventListener('click', function (event) {
        // Check if the click is inside a .hostgameview_modal
        const modal = event.target.closest('.hostgameview_modal');

        if (modal) {
            // Check if the clicked element is the .customModal_close button or inside it
            const closeBtn = event.target.closest('.customModal_close');

            if (closeBtn) {
                event.preventDefault();
                console.log("Modal close button clicked");

                // Close only this modal
                modal.classList.remove('open');

                // Optional redirect
                // window.location.href = '/host-dashboard.php';

            }
        }
    });

    // Initialize default period from dynamic-event-calendar.js if available
    if (typeof window.initSchedulePeriod === 'function') {
        window.initSchedulePeriod({ year: '#year', month: '#month' });
    }
    document.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.copy_event');
        if (!copyBtn) return;

        const eventId = copyBtn.getAttribute('data-id');
        const dataInput = document.getElementById('event_data_' + eventId);

        if (dataInput) {
            const data = dataInput.value;

            if (navigator.clipboard && window.isSecureContext) {
                // Modern clipboard API
                navigator.clipboard.writeText(data).then(() => {
                    console.log('Event details copied!');
                }).catch(err => console.error('Clipboard error:', err));
            } else {
                // Fallback for older browsers
                const temp = document.createElement('textarea');
                temp.value = data;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                console.log('Event details copied (fallback)!');
            }
        } else {
            console.error('Event data not found for ID:', eventId);
        }
    });



    document.getElementById('autoConfirm').addEventListener('change', function () {
        const automationValue = this.checked ? 'Y' : 'N';
        const eventID = document.getElementById("EVENT_IDD").value;
        console.log("eventID", eventID)

        hitAutomationAPI(automationValue, eventID);
    });

    function hitAutomationAPI(value, eventID) {
        fetch('../api/update-automation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                AUTOMATION: value,
                eventID: eventID
            })
        })
            .then(response => response.json())
            .then(data => {
                location.reload()
                console.log('Automation updated:', data);
            })
            .catch(error => {
                location.reload()
            });
    }

</script>