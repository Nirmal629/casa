<?php
// host-roster.php
// Roster management UI for Hosts to manage club requests and accepted roster.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the host is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Host', 'Trainer'])) {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$host_id = intval($_SESSION['user_id']);

// DSN and PDO connection
$db_host = "localhost";
$db_name = "casa_test";
$db_user = "casa_test";
$db_pass = "casa_test123#";
$db_charset = "utf8mb4";

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $roster_pdo = new PDO($dsn, $db_user, $db_pass, $options);

    // Fetch Pending Requests
    $pending_stmt = $roster_pdo->prepare("
        SELECT pcs.id as request_id, pcs.created_at,
               c.club_name, c.game_type,
               u.ID as player_id, u.NAME as player_name, u.EMAIL as player_email, u.PROFILE_IMAGE as player_img,
               u.COUNTRY as player_country, u.PROVINCE as player_province, u.CITY as player_city,
               u.VERIFIED_LEVEL as player_vlevel, u.LEVEL as player_level
        FROM ca_player_club_status pcs
        JOIN ca_clubs c ON pcs.club_id = c.id
        JOIN ca_users u ON pcs.player_id = u.ID
        WHERE pcs.host_id = ? AND pcs.status = 'pending'
        ORDER BY pcs.created_at DESC
    ");
    $pending_stmt->execute([$host_id]);
    $pending_requests = $pending_stmt->fetchAll();

    // Fetch Accepted Members
    $accepted_stmt = $roster_pdo->prepare("
        SELECT pcs.id as membership_id, pcs.created_at,
               c.club_name, c.game_type,
               u.ID as player_id, u.NAME as player_name, u.EMAIL as player_email, u.PROFILE_IMAGE as player_img,
               u.COUNTRY as player_country, u.PROVINCE as player_province, u.CITY as player_city,
               u.VERIFIED_LEVEL as player_vlevel, u.LEVEL as player_level
        FROM ca_player_club_status pcs
        JOIN ca_clubs c ON pcs.club_id = c.id
        JOIN ca_users u ON pcs.player_id = u.ID
        WHERE pcs.host_id = ? AND pcs.status = 'accepted'
        ORDER BY c.club_name ASC, u.NAME ASC
    ");
    $accepted_stmt->execute([$host_id]);
    $accepted_roster = $accepted_stmt->fetchAll();

} catch (PDOException $e) {
    echo "<p class='text-danger'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    $pending_requests = [];
    $accepted_roster = [];
}
?>

<div class="container-fluid py-3">
    <!-- Section 1: Pending Join Requests -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-warning text-dark d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-fill-exclamation me-2" viewBox="0 0 16 16">
                    <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-9 8c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Zm10 .5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5Zm0-2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5Zm.5-3a.5.5 0 0 0 0 1h1.5a.5.5 0 0 0 0-1h-1.5Z"/>
                </svg>
                Pending Join Requests (<?= count($pending_requests) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($pending_requests) === 0): ?>
                <div class="p-4 text-center text-muted">
                    <p class="mb-0 fs-5">No pending join requests.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Player</th>
                                <th>Contact & Location</th>
                                <th>Club / Sport</th>
                                <th>Level</th>
                                <th>Requested On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $req): 
                                $profile_img = !empty($req['player_img']) ? 'profile_img/' . $req['player_img'] : 'assets/images/profile.jpg';
                                $location = array_filter([$req['player_city'], $req['player_province'], $req['player_country']]);
                                $location_str = !empty($location) ? implode(', ', $location) : 'N/A';
                                $skill = !empty($req['player_vlevel']) ? $req['player_vlevel'] : ($req['player_level'] ?: 'N/A');
                            ?>
                                <tr id="req-row-<?= $req['request_id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= htmlspecialchars($profile_img) ?>" alt="Profile" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #ffc107;">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($req['player_name']) ?></h6>
                                                <small class="text-muted">ID: <?= $req['player_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($req['player_email']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($location_str) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($req['club_name']) ?></div>
                                        <small class="badge bg-secondary"><?= htmlspecialchars($req['game_type']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($skill) ?></span>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['created_at'])) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-success btn-sm me-1 btn-approve-member" data-req-id="<?= $req['request_id'] ?>">
                                            <i class="fa-solid fa-check me-1"></i> Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-reject-member" data-req-id="<?= $req['request_id'] ?>">
                                            <i class="fa-solid fa-xmark me-1"></i> Reject
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section 2: Approved Club Roster -->
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-people-fill me-2" viewBox="0 0 16 16">
                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                </svg>
                Approved Members Roster (<?= count($accepted_roster) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($accepted_roster) === 0): ?>
                <div class="p-4 text-center text-muted">
                    <p class="mb-0 fs-5">No accepted club members found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Player</th>
                                <th>Contact & Location</th>
                                <th>Club / Sport</th>
                                <th>Level</th>
                                <th>Joined On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accepted_roster as $member): 
                                $profile_img = !empty($member['player_img']) ? 'profile_img/' . $member['player_img'] : 'assets/images/profile.jpg';
                                $location = array_filter([$member['player_city'], $member['player_province'], $member['player_country']]);
                                $location_str = !empty($location) ? implode(', ', $location) : 'N/A';
                                $skill = !empty($member['player_vlevel']) ? $member['player_vlevel'] : ($member['player_level'] ?: 'N/A');
                            ?>
                                <tr id="member-row-<?= $member['membership_id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= htmlspecialchars($profile_img) ?>" alt="Profile" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #198754;">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($member['player_name']) ?></h6>
                                                <small class="text-muted">ID: <?= $member['player_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($member['player_email']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($location_str) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($member['club_name']) ?></div>
                                        <small class="badge bg-secondary"><?= htmlspecialchars($member['game_type']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($skill) ?></span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($member['created_at'])) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-danger btn-sm btn-remove-member" data-membership-id="<?= $member['membership_id'] ?>">
                                            <i class="fa-solid fa-trash-can me-1"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AJAX Event Handler Scripts for Roster Operations -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Approve Member Request
    $('.btn-approve-member').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var reqId = btn.data('req-id');
        
        if (confirm("Are you sure you want to approve this player's request to join the club?")) {
            btn.prop('disabled', true).text('Approving...');
            $.ajax({
                url: 'api/approve_member.php',
                type: 'POST',
                data: { request_id: reqId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message || 'Error approving request.');
                        btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Approve');
                    }
                },
                error: function() {
                    alert('Server error while approving request.');
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Approve');
                }
            });
        }
    });

    // Reject Member Request
    $('.btn-reject-member').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var reqId = btn.data('req-id');
        
        if (confirm("Are you sure you want to reject this player's join request?")) {
            btn.prop('disabled', true).text('Rejecting...');
            $.ajax({
                url: 'api/remove_member.php',
                type: 'POST',
                data: { id: reqId, action: 'reject' },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message || 'Error rejecting request.');
                        btn.prop('disabled', false).html('<i class="fa-solid fa-xmark me-1"></i> Reject');
                    }
                },
                error: function() {
                    alert('Server error while rejecting request.');
                    btn.prop('disabled', false).html('<i class="fa-solid fa-xmark me-1"></i> Reject');
                }
            });
        }
    });

    // Remove Member from Roster
    $('.btn-remove-member').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var memId = btn.data('membership-id');
        
        if (confirm("Are you sure you want to remove this player from your club roster? They will lose dashboard access to this club.")) {
            btn.prop('disabled', true).text('Removing...');
            $.ajax({
                url: 'api/remove_member.php',
                type: 'POST',
                data: { id: memId, action: 'remove' },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message || 'Error removing member.');
                        btn.prop('disabled', false).html('<i class="fa-solid fa-trash-can me-1"></i> Remove');
                    }
                },
                error: function() {
                    alert('Server error while removing member.');
                    btn.prop('disabled', false).html('<i class="fa-solid fa-trash-can me-1"></i> Remove');
                }
            });
        }
    });
});
</script>
