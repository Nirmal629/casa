<!----Host Tournament list---->
<section class="">
    <div class="custom_card">
        <h6 class="card_heading">My Tournaments</h6>

        <div class="discoverGames_wraper hostWrapper">
            <?php
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $hostTournaments = [];
            $hostTournamentError = '';

            try {
                include_once __DIR__ . '/dbConnection_PDO.php';

                $hostTournamentStmt = $pdo->prepare("
                    SELECT
                        e.*, b.IMGAE AS banner_image,
                        (SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = e.ID) AS joined_count
                    FROM to_tournaments e
                    LEFT JOIN to_tournamet_banners b ON b.EVENTS_ID = e.ID
                    WHERE e.HOST_ID = :host_id
                    ORDER BY e.ID DESC
                ");
                $hostTournamentStmt->execute([':host_id' => (int)($_SESSION['user_id'] ?? 0)]);
                $hostTournaments = $hostTournamentStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $hostTournamentError = $e->getMessage();
            }

            if ($hostTournamentError !== '') {
                echo "<p class='label'>Unable to load tournaments right now.</p>";
            } elseif (empty($hostTournaments)) {
                echo "<p class='label'>You have not organized any tournaments yet.</p>";
            } else {
                foreach ($hostTournaments as $tournament) {
                    $tName = trim((string)($tournament['CUP_NAME'] ?? ''));
                    if ($tName === '') {
                        $tName = trim((string)($tournament['HOST_NAME'] ?? ''));
                    }
                    if ($tName === '') {
                        $tName = 'Tournament';
                    }

                    $tCategory = trim((string)($tournament['EVENT_CATEGORY'] ?? ''));
                    $tGender = trim((string)($tournament['GENDER_CATEGORY'] ?? ''));
                    $tType = trim((string)($tournament['EVENT_TYPE'] ?? ''));
                    $tVenue = trim((string)($tournament['EVENT_VENUE'] ?? ''));
                    $tDate = !empty($tournament['EVENT_DATE']) && $tournament['EVENT_DATE'] !== '0000-00-00'
                        ? date('D, d M Y', strtotime($tournament['EVENT_DATE']))
                        : 'TBD';
                    $tTime = !empty($tournament['EVENT_TIME'])
                        ? date('h:i A', strtotime($tournament['EVENT_TIME']))
                        : 'TBD';
                    $tStatus = trim((string)($tournament['STATUS'] ?? ''));
                    $joinedCount = (int)($tournament['joined_count'] ?? 0);
                    $tId = (int)$tournament['ID'];

                    $headline = trim(implode(' - ', array_filter([$tCategory, $tGender, $tType])));
                    if ($headline === '') {
                        $headline = 'Tournament';
                    }
                    ?>
                    <div class='discoverGames_card'>
                        <div class='d-flex flex-wrap align-items-center justify-content-between gap-2' style='margin-bottom: 8px; background-color: #0d6efda1; padding: 8px 10px; border-radius: 8px 8px 0px 0px;'>
                            <p class='text-white desc fw-bold m-0' style='font-size: 85%;'><?php echo htmlspecialchars($headline); ?></p>
                            <?php if ($tStatus !== ''): ?>
                                <span class='badge btn-success'><?php echo htmlspecialchars($tStatus); ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="padding: 4px 10px 12px;">
                            <p class="desc fw-bold m-0" style="font-size: 95%;"><?php echo htmlspecialchars($tName); ?></p>
                            <p class="desc m-0" style="font-size: 82%;"><i class="fa fa-calendar-alt"></i> <?php echo htmlspecialchars($tDate); ?> &nbsp; <i class="fa fa-clock"></i> <?php echo htmlspecialchars($tTime); ?></p>
                            <?php if ($tVenue !== ''): ?>
                                <p class="desc m-0" style="font-size: 82%;"><i class="fa fa-map-marker-alt"></i> <?php echo htmlspecialchars($tVenue); ?></p>
                            <?php endif; ?>
                            <p class="desc m-0" style="font-size: 82%;"><i class="fa fa-check-circle"></i> <?php echo $joinedCount; ?> teams joined</p>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <a href="tournament/?id=<?php echo $tId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-tower-broadcast"></i> Manage Live
                                </a>
                                <a href="host-enrolled-tournaments.php?tournament_id=<?php echo $tId; ?>" class="btn btn-info btn-sm" title="All Enrolled Users">
                                    <i class="fa fa-users"></i> Enrolled Users
                                </a>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openHostTournamentModal(<?php echo $tId; ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>

<style>
    #hostTournamentModal .modal-body { max-height: 70vh; overflow-y: auto; }
    #hostTournamentModal .tournament-section { border: 1px solid #d8e1eb; border-radius: 6px; padding: 14px; margin-bottom: 16px; }
    #hostTournamentModal .tournament-section-title { color: #ef5350; font-size: 14px; font-weight: 700; margin: 0 0 14px; }
    #hostTournamentModal .form-text { font-size: .78rem; }
    #hostBannerPreview { display: none; max-width: 180px; max-height: 100px; margin-top: 8px; border: 1px solid #ddd; padding: 3px; }
</style>

<div class="modal fade" id="hostTournamentModal" tabindex="-1" aria-labelledby="hostTournamentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="hostTournamentForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="hostTournamentModalTitle">Edit Tournament</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hostTournamentAlert" class="alert d-none" role="alert"></div>
                    <input type="hidden" name="event_id" id="host_event_id">

                    <section class="tournament-section">
                        <h6 class="tournament-section-title">Tournament Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Host Name</label><input class="form-control" name="host_name" id="host_name" readonly></div>
                            <div class="col-md-6"><label class="form-label">Cup Name <span class="text-danger">*</span></label><input class="form-control" name="cup_name" id="cup_name" required></div>
                            <div class="col-md-3"><label class="form-label">Country</label><input class="form-control" name="event_country" id="event_country"></div>
                            <div class="col-md-3"><label class="form-label">Province</label><input class="form-control" name="event_province" id="event_province"></div>
                            <div class="col-md-3"><label class="form-label">City</label><input class="form-control" name="event_city" id="event_city"></div>
                            <div class="col-md-3"><label class="form-label">Currency</label><select class="form-select" name="event_currency" id="event_currency"><option>CAD</option><option>USD</option></select></div>
                            <div class="col-md-6"><label class="form-label">Event Venue</label><input class="form-control" name="event_venue" id="event_venue"></div>
                            <div class="col-md-2"><label class="form-label">Category</label><select class="form-select" name="event_category" id="event_category"><option>Open</option><option>30+</option><option>35+</option><option>40+</option></select></div>
                            <div class="col-md-2"><label class="form-label">Gender</label><select class="form-select" name="gender_category" id="gender_category"><option>Male</option><option>Female</option><option>Mixed</option></select></div>
                            <div class="col-md-2"><label class="form-label">Skill</label><select class="form-select" name="skill_level" id="skill_level"><option value="All">All</option><option value="Adv">Adv</option><option value="Int+">Int+</option><option value="Int">Int</option></select></div>
                            <div class="col-md-2"><label class="form-label">Type</label><select class="form-select" name="event_type" id="event_type"><option>Single</option><option>Doubles</option></select></div>
                            <div class="col-md-4"><label class="form-label">Shuttle Type</label><select class="form-select" name="shuttle_type" id="shuttle_type"><option>Feather</option><option>Nylon</option></select></div>
                            <div class="col-md-6"><label class="form-label">Match Format</label><input class="form-control" name="match_format" id="match_format"></div>
                        </div>
                    </section>

                    <section class="tournament-section">
                        <h6 class="tournament-section-title">Schedule</h6>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Event Date</label><input type="date" class="form-control" name="event_date" id="event_date"></div>
                            <div class="col-md-4"><label class="form-label">Event Start Time</label><input type="time" class="form-control" name="from_time" id="from_time"></div>
                            <div class="col-md-4"><label class="form-label">Event End Time</label><input type="time" class="form-control" name="to_time" id="to_time"></div>
                            <div class="col-md-4"><label class="form-label">Registration Freeze Date</label><input type="date" class="form-control" name="freeze_date" id="freeze_date"></div>
                            <div class="col-md-4"><label class="form-label">Registration Freeze Time</label><input type="time" class="form-control" name="freeze_time" id="freeze_time"></div>
                            <div class="col-md-4"><label class="form-label">Reporting Time</label><input type="time" class="form-control" name="reporting_time" id="reporting_time"></div>
                            <div class="col-md-6"><label class="form-label">Match Start Time</label><input type="time" class="form-control" name="match_start_time" id="match_start_time"></div>
                            <div class="col-md-6"><label class="form-label">Draw Date</label><input type="date" class="form-control" name="draw_announcement" id="draw_announcement"></div>
                        </div>
                    </section>

                    <section class="tournament-section">
                        <h6 class="tournament-section-title">Account</h6>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Facility Cost</label><input type="number" min="0" step="0.01" class="form-control" name="facility_cost" id="facility_cost"></div>
                            <div class="col-md-4"><label class="form-label">Accessories Cost</label><input type="number" min="0" step="0.01" class="form-control" name="accessories_cost" id="accessories_cost"></div>
                            <div class="col-md-4"><label class="form-label">Snacks Cost</label><input type="number" min="0" step="0.01" class="form-control" name="snacks_cost" id="snacks_cost"></div>
                            <div class="col-md-3"><label class="form-label">Club Cost</label><input type="number" min="0" step="0.01" class="form-control" name="club_cost" id="club_cost"></div>
                            <div class="col-md-3"><label class="form-label">Birdies Used</label><input type="number" min="0" step="0.01" class="form-control" name="birdie_used" id="birdie_used"></div>
                            <div class="col-md-3"><label class="form-label">Birdie Cost</label><input type="number" min="0" step="0.01" class="form-control" name="birdie_cost" id="birdie_cost"></div>
                            <div class="col-md-3"><label class="form-label">Birdie Price</label><input type="number" min="0" step="0.01" class="form-control" name="birdie_price" id="birdie_price"></div>
                            <div class="col-md-4"><label class="form-label">Total Event Cost</label><input type="number" step="0.01" class="form-control" name="total_event_cost" id="total_event_cost"></div>
                            <div class="col-md-4"><label class="form-label">Total Player Cost</label><input type="number" step="0.01" class="form-control" name="total_player_cost" id="total_player_cost"></div>
                            <div class="col-md-4"><label class="form-label">Profit / Loss</label><input type="number" step="0.01" class="form-control" name="profit_loss" id="profit_loss"></div>
                        </div>
                    </section>

                    <section class="tournament-section">
                        <h6 class="tournament-section-title">Message</h6>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Event Description</label><textarea class="form-control" rows="3" name="event_description" id="event_description"></textarea></div>
                            <div class="col-12"><label class="form-label">Event Message</label><textarea class="form-control" rows="3" name="event_message" id="event_message"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Registration Popup Message</label><textarea class="form-control" rows="4" name="popup_message" id="popup_message"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Payment/Login Email Template</label><textarea class="form-control" rows="4" name="payment_mail" id="payment_mail"></textarea></div>
                            <div class="col-12"><label class="form-label">Banner Image</label><input type="file" class="form-control" name="banner" id="host_banner" accept="image/*"><div class="form-text">Upload only to replace the current banner.</div><img id="hostBannerPreview" alt="Banner preview"></div>
                        </div>
                    </section>

                    <section class="tournament-section">
                        <h6 class="tournament-section-title">Payment</h6>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Team Amount</label><input type="number" min="0" step="0.01" class="form-control" name="event_cost" id="event_cost"></div>
                            <div class="col-md-3"><label class="form-label">Per Player Amount</label><input type="number" min="0" step="0.01" class="form-control" name="amount" id="amount"></div>
                            <div class="col-md-3"><label class="form-label">Payment ID</label><input class="form-control" name="payment_id" id="payment_id"></div>
                            <div class="col-md-3"><label class="form-label">Payment Deadline</label><input type="date" class="form-control" name="payment_deadline" id="payment_deadline"></div>
                        </div>
                    </section>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" id="hostTournamentSubmit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>
<script>
const hostTournaments = <?php echo json_encode(array_column($hostTournaments, null, 'ID'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const hostTournamentModal = new bootstrap.Modal(document.getElementById('hostTournamentModal'));
const hostEditors = {};
const hostFormFields = ['host_name','cup_name','event_country','event_province','event_city','event_currency','event_venue','event_category','gender_category','event_type','event_date','from_time','to_time','freeze_date','freeze_time','event_cost','event_description','event_message','facility_cost','accessories_cost','snacks_cost','total_event_cost','total_player_cost','profit_loss','club_cost','birdie_used','birdie_cost','birdie_price','amount','payment_id','payment_deadline','reporting_time','match_start_time','draw_announcement','shuttle_type','match_format','popup_message','payment_mail'];
const hostColumnMap = { host_name:'HOST_NAME', cup_name:'CUP_NAME', event_country:'EVENT_COUNTRY', event_province:'EVENT_PROVINCE', event_city:'EVENT_CITY', event_currency:'EVENT_CURRENCY', event_venue:'EVENT_VENUE', event_category:'EVENT_CATEGORY', gender_category:'GENDER_CATEGORY', event_type:'EVENT_TYPE', event_date:'EVENT_DATE', from_time:'EVENT_TIME', to_time:'TO_TIME', freeze_date:'CANCEL_DATE', freeze_time:'CANCEL_TIME', event_cost:'EVENT_COST', event_description:'EVENT_DESCRIPTION', event_message:'EVENT_MESSAGE', facility_cost:'FACILITY_COST', accessories_cost:'ACCESSORIES_COST', snacks_cost:'SNACKS_COST', total_event_cost:'TOTAL_EVENT_COST', total_player_cost:'TOTAL_PLAYER_COST', profit_loss:'PROFIT_LOSS', club_cost:'CLUB_COST', birdie_used:'BIRDIE_USED', birdie_cost:'BIRDIE_COST', birdie_price:'BIRDIE_PRICE', amount:'AMOUNT', payment_id:'PAYMENT_ID', payment_deadline:'PAYMENT_DEADLINE', reporting_time:'REPORTING_TIME', match_start_time:'MATCH_START_TIME', draw_announcement:'DRAW_ANNOUNCEMENT', shuttle_type:'SHUTTLE_TYPE', match_format:'MATCH_FORMAT', popup_message:'POPUP_MESSAGE', payment_mail:'PAYMENT_MAIL' };
function hostSkill(value) { return value === 'Advance' ? 'Adv' : value === 'Intermediate+' ? 'Int+' : value === 'Intermediate' ? 'Int' : (value || 'All'); }
function setHostEditor(field, value) { if (hostEditors[field]) hostEditors[field].setData(value || ''); else document.getElementById(field).value = value || ''; }
function openHostTournamentModal(id) {
    const tournament = hostTournaments[id]; if (!tournament) return;
    document.getElementById('hostTournamentForm').reset(); document.getElementById('host_event_id').value = id;
    hostFormFields.forEach(field => { const value = tournament[hostColumnMap[field]] || ''; if (['event_description', 'event_message', 'popup_message', 'payment_mail'].includes(field)) setHostEditor(field, value); else document.getElementById(field).value = value; });
    document.getElementById('skill_level').value = hostSkill(tournament.GENDER_SKILL_LEVEL);
    const preview = document.getElementById('hostBannerPreview');
    if (tournament.banner_image) { preview.src = 'assets/images/tournaments_banner/' + encodeURIComponent(tournament.banner_image); preview.style.display = 'block'; } else { preview.removeAttribute('src'); preview.style.display = 'none'; }
    document.getElementById('hostTournamentAlert').className = 'alert d-none'; hostTournamentModal.show();
}
document.getElementById('host_banner').addEventListener('change', function () { const preview = document.getElementById('hostBannerPreview'); if (this.files[0]) { preview.src = URL.createObjectURL(this.files[0]); preview.style.display = 'block'; } });
document.getElementById('hostTournamentForm').addEventListener('submit', async function (event) {
    event.preventDefault(); Object.keys(hostEditors).forEach(field => { document.getElementById(field).value = hostEditors[field].getData(); }); const submit = document.getElementById('hostTournamentSubmit'), alertBox = document.getElementById('hostTournamentAlert'); submit.disabled = true;
    try { const response = await fetch('api/host_manage_tournament.php', { method: 'POST', body: new FormData(this) }); const result = await response.json(); alertBox.className = 'alert ' + (result.status === 'success' ? 'alert-success' : 'alert-danger'); alertBox.textContent = result.message; if (result.status === 'success') setTimeout(() => window.location.reload(), 700); }
    catch (error) { alertBox.className = 'alert alert-danger'; alertBox.textContent = 'Unable to save the tournament. Please try again.'; }
    finally { submit.disabled = false; }
});
['event_description', 'event_message', 'popup_message', 'payment_mail'].forEach(field => {
    ClassicEditor.create(document.querySelector('#' + field), { toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'] }).then(editor => { hostEditors[field] = editor; });
});
</script>
