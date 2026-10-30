<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!in_array(trim((string)($_SESSION['usertype'] ?? '')), ['Host', 'Trainer'], true)) {
    header('Location: /index.php');
    exit;
}

$pdo = \App\Config\Database::getPdo();
$tournamentId = (int)($_GET['id'] ?? 0);
$tournament = null;
if ($tournamentId > 0) {
    $stmt = $pdo->prepare('SELECT t.*, b.IMGAE AS banner_image FROM to_tournaments t LEFT JOIN to_tournamet_banners b ON b.EVENTS_ID = t.ID WHERE t.ID = ? AND t.HOST_ID = ? LIMIT 1');
    $stmt->execute([$tournamentId, (int)($_SESSION['user_id'] ?? 0)]);
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$tournament) {
    http_response_code(404);
    exit('Tournament not found or you do not have permission to edit it.');
}

function tournamentValue($key) { global $tournament; return htmlspecialchars((string)($tournament[$key] ?? ''), ENT_QUOTES, 'UTF-8'); }
function tournamentOptions($name, array $options, $selected) {
    echo '<select name="' . htmlspecialchars($name) . '" class="form-select">';
    foreach ($options as $value => $label) {
        echo '<option value="' . htmlspecialchars($value) . '"' . ((string)$selected === (string)$value ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
    }
    echo '</select>';
}
include __DIR__ . '/../layouts/header/inner-header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<section class="py-5">
    <div class="container" style="max-width: 1050px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Edit Tournament</h2>
            <a href="host-dashboard.php#Tournament" class="btn btn-outline-secondary">Back to tournaments</a>
        </div>
        <div id="formAlert" class="alert d-none" role="alert"></div>
        <form id="tournamentForm" enctype="multipart/form-data" class="card shadow-sm">
            <div class="card-body p-4">
                <input type="hidden" name="event_id" value="<?php echo (int)$tournament['ID']; ?>">
                <h5 class="mb-3">Tournament details</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Host name</label><input class="form-control" name="host_name" value="<?php echo tournamentValue('HOST_NAME'); ?>"></div>
                    <div class="col-md-6"><label class="form-label">Cup name</label><input class="form-control" name="cup_name" required value="<?php echo tournamentValue('CUP_NAME'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Country</label><input class="form-control" name="event_country" value="<?php echo tournamentValue('EVENT_COUNTRY'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Province</label><input class="form-control" name="event_province" value="<?php echo tournamentValue('EVENT_PROVINCE'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">City</label><input class="form-control" name="event_city" value="<?php echo tournamentValue('EVENT_CITY'); ?>"></div>
                    <div class="col-md-8"><label class="form-label">Venue</label><input class="form-control" name="event_venue" value="<?php echo tournamentValue('EVENT_VENUE'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Currency</label><?php tournamentOptions('event_currency', ['CAD'=>'CAD', 'USD'=>'USD'], $tournament['EVENT_CURRENCY']); ?></div>
                    <div class="col-md-3"><label class="form-label">Category</label><?php tournamentOptions('event_category', ['Open'=>'Open', '30+'=>'30+', '40+'=>'40+'], $tournament['EVENT_CATEGORY']); ?></div>
                    <div class="col-md-3"><label class="form-label">Gender</label><?php tournamentOptions('gender_category', ['Male'=>'Male', 'Female'=>'Female', 'Mixed'=>'Mixed'], $tournament['GENDER_CATEGORY']); ?></div>
                    <div class="col-md-3"><label class="form-label">Format</label><?php tournamentOptions('event_type', ['Single'=>'Single', 'Doubles'=>'Doubles'], $tournament['EVENT_TYPE']); ?></div>
                    <div class="col-md-3"><label class="form-label">Skill level</label><?php tournamentOptions('skill_level', ['All'=>'All', 'Adv'=>'Advance', 'Int+'=>'Intermediate+', 'Int'=>'Intermediate'], $tournament['GENDER_SKILL_LEVEL'] === 'Advance' ? 'Adv' : ($tournament['GENDER_SKILL_LEVEL'] === 'Intermediate+' ? 'Int+' : ($tournament['GENDER_SKILL_LEVEL'] === 'All' ? 'All' : 'Int'))); ?></div>
                </div>

                <h5 class="mt-4 mb-3">Schedule and match details</h5>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Event date</label><input type="date" class="form-control" name="event_date" value="<?php echo tournamentValue('EVENT_DATE'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Start time</label><input type="time" class="form-control" name="from_time" value="<?php echo tournamentValue('EVENT_TIME'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">End time</label><input type="time" class="form-control" name="to_time" value="<?php echo tournamentValue('TO_TIME'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Registration freeze date</label><input type="date" class="form-control" name="freeze_date" value="<?php echo tournamentValue('CANCEL_DATE'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Registration freeze time</label><input type="time" class="form-control" name="freeze_time" value="<?php echo tournamentValue('CANCEL_TIME'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Reporting time</label><input type="time" class="form-control" name="reporting_time" value="<?php echo tournamentValue('REPORTING_TIME'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Match start time</label><input type="time" class="form-control" name="match_start_time" value="<?php echo tournamentValue('MATCH_START_TIME'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Draw announcement</label><input type="date" class="form-control" name="draw_announcement" value="<?php echo tournamentValue('DRAW_ANNOUNCEMENT'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Shuttle type</label><?php tournamentOptions('shuttle_type', ['Feather'=>'Feather', 'Nylon'=>'Nylon'], $tournament['SHUTTLE_TYPE']); ?></div>
                    <div class="col-md-4"><label class="form-label">Match format</label><input class="form-control" name="match_format" value="<?php echo tournamentValue('MATCH_FORMAT'); ?>"></div>
                </div>

                <h5 class="mt-4 mb-3">Payment, content and banner</h5>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">System cost</label><input class="form-control" name="event_cost" value="<?php echo tournamentValue('EVENT_COST'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Display amount</label><input type="number" step="0.01" class="form-control" name="amount" value="<?php echo tournamentValue('AMOUNT'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Payment email</label><input type="email" class="form-control" name="payment_id" value="<?php echo tournamentValue('PAYMENT_ID'); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Payment deadline</label><input type="date" class="form-control" name="payment_deadline" value="<?php echo tournamentValue('PAYMENT_DEADLINE'); ?>"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="event_description" rows="4"><?php echo tournamentValue('EVENT_DESCRIPTION'); ?></textarea></div>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Edit Tournament (#<?php echo (int)$tournament['ID']; ?>)</h2>
        <a href="host-dashboard.php#Tournament" class="btn btn-outline-secondary">Back to tournaments</a>
    </div>

    <form id="editTournamentForm" class="row g-3">
        <input type="hidden" name="action" value="update_tournament">
        <input type="hidden" name="id" value="<?php echo (int)$tournament['ID']; ?>">
        <div class="col-md-6"><label class="form-label">Tournament title</label><input class="form-control" name="title" required value="<?php echo tournamentValue('TITLE'); ?>"></div>
        <div class="col-md-6"><label class="form-label">Cup name</label><input class="form-control" name="cup_name" required value="<?php echo tournamentValue('CUP_NAME'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Category</label><?php tournamentOptions('category', ['Badminton', 'Tennis', 'Squash', 'Table Tennis', 'Pickleball'], tournamentValue('CATEGORY')); ?></div>
        <div class="col-md-4"><label class="form-label">Gender category</label><?php tournamentOptions('gender_category', ['Men', 'Women', 'Co-Ed', 'Mixed'], tournamentValue('GENDER_CATEGORY')); ?></div>
        <div class="col-md-4"><label class="form-label">Game type</label><?php tournamentOptions('game_type', ['Singles', 'Doubles', 'Team'], tournamentValue('GAME_TYPE')); ?></div>
        <div class="col-md-4"><label class="form-label">Skill level</label><?php tournamentOptions('gender_skill_level', ['Beginner', 'Intermediate', 'Advanced', 'Open'], tournamentValue('GENDER_SKILL_LEVEL')); ?></div>
        <div class="col-md-4"><label class="form-label">Start date</label><input type="date" class="form-control" name="start_date" required value="<?php echo tournamentValue('START_DATE'); ?>"></div>
        <div class="col-md-4"><label class="form-label">End date</label><input type="date" class="form-control" name="end_date" required value="<?php echo tournamentValue('END_DATE'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Venue</label><input class="form-control" name="venue" required value="<?php echo tournamentValue('VENUE'); ?>"></div>
        <div class="col-md-4"><label class="form-label">City</label><input class="form-control" name="city" required value="<?php echo tournamentValue('CITY'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Province</label><input class="form-control" name="province" required value="<?php echo tournamentValue('PROVINCE'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Country</label><input class="form-control" name="country" required value="<?php echo tournamentValue('COUNTRY'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Entry fee</label><input type="number" step="0.01" class="form-control" name="entry_fee" required value="<?php echo tournamentValue('ENTRY_FEE'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Currency</label><?php tournamentOptions('currency', ['CAD', 'USD', 'EUR', 'INR'], tournamentValue('CURRENCY')); ?></div>
        <div class="col-md-4"><label class="form-label">Max teams/players</label><input type="number" class="form-control" name="max_players" required value="<?php echo tournamentValue('MAX_PLAYERS'); ?>"></div>
        <div class="col-md-6"><label class="form-label">Scorer code</label><input class="form-control" name="scorer_code" value="<?php echo tournamentValue('SCORER_CODE'); ?>"></div>
        <div class="col-md-6"><label class="form-label">Scorer password</label><input class="form-control" name="scorer_password" value="<?php echo tournamentValue('SCORER_PASSWORD'); ?>"></div>
        <div class="col-12"><label class="form-label">Rules / format</label><textarea class="form-control" name="rules" rows="3"><?php echo tournamentValue('RULES'); ?></textarea></div>
        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?php echo tournamentValue('DESCRIPTION'); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Status</label><?php tournamentOptions('status', ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'], tournamentValue('STATUS')); ?></div>
        <div class="col-md-6"><label class="form-label">Banner Image (optional)</label><input type="file" class="form-control" name="banner_image" accept="image/*"></div>
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary px-4">Save tournament</button>
        </div>
    </form>
    <div id="tournamentEditMsg" class="mt-3"></div>
</div>

<script>
document.getElementById('editTournamentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('tournamentEditMsg');
    msg.className = 'mt-3 alert alert-info';
    msg.textContent = 'Saving...';
    try {
        const response = await fetch('../api/host_manage_tournament.php', { method: 'POST', body: new FormData(this) });
        const result = await response.json();
        msg.className = `mt-3 alert alert-${result.status === 'success' ? 'success' : 'danger'}`;
        msg.textContent = result.message || 'Updated';
        if (result.status === 'success') setTimeout(() => window.location.href = '/host/host-dashboard.php#Tournament', 1000);
    } catch (err) {
        msg.className = 'mt-3 alert alert-danger';
        msg.textContent = 'Save failed: ' + err.message;
    }
});
</script>
<?php include __DIR__ . '/../layouts/footer/footer.php'; ?>
