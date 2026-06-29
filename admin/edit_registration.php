<?php
// 1. Get ID and Fetch Data
$id = $_GET['id'] ?? 0;
// Database Connection for the list view
$host    = 'localhost';
$db      = 'casa_test';
$user    = 'casa_test';
$pass    = 'casa_test123#';
$charset = 'utf8mb4';
$dsn     = "mysql:host=$host;dbname=$db;charset=utf8";
$conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);include "header.php"; // Using your header
include "sidebar.php"; // Using your sidebar

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Get the Player ID from URL
$player_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. Find the Team ID for this player
    $stmt = $pdo->prepare("SELECT TEAM_ID FROM to_users WHERE ID = ? LIMIT 1");
    $stmt->execute([$player_id]);
    $temp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$temp) { die("<div class='alert alert-danger'>Registration not found.</div>"); }
    $team_id = $temp['TEAM_ID'];

    // 3. Fetch Team Details
    $stmtTeam = $pdo->prepare("SELECT * FROM to_teams WHERE ID = ? LIMIT 1");
    $stmtTeam->execute([$team_id]);
    $team = $stmtTeam->fetch(PDO::FETCH_ASSOC);

    // 4. Fetch All Players in this team
    $stmtPlayers = $pdo->prepare("SELECT * FROM to_users WHERE TEAM_ID = ? ORDER BY ID ASC");
    $stmtPlayers->execute([$team_id]);
    $players = $stmtPlayers->fetchAll(PDO::FETCH_ASSOC);

    $isDoubles = (count($players) > 1);
    $today = date('Y-m-d');

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Edit Team Registration</h2>
    </header>

    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">Manage Team: <?php echo htmlspecialchars($team['NAME']); ?></h2>
                </header>
                <div class="panel-body">
                    <form id="editRegForm">
                        <!-- Security and IDs -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                        <input type="hidden" name="action" value="update_team">

                        <!-- Team Name Section (Full Width) -->
                        <div class="row mb-4 align-items-center">
                            <label class="col-sm-2 control-label fw-bold">Team Name</label>
                            <div class="col-sm-6">
                                <input type="text" name="team_name" class="form-control" value="<?php echo htmlspecialchars($team['NAME']); ?>" required>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <?php foreach ($players as $index => $p): 
                                $p_num = $index + 1;
                            ?>
                                <!-- Each Player Column -->
                                <div class="<?php echo $isDoubles ? 'col-md-6 border-end' : 'col-md-12'; ?> p-4">
                                    <h5 class="text-primary fw-bold mb-3">Player <?php echo $p_num; ?> Details (ID: #<?php echo $p['ID']; ?>)</h5>
                                    
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 control-label small fw-bold">Full Name</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="players[<?php echo $p['ID']; ?>][name]" class="form-control input-sm" value="<?php echo htmlspecialchars($p['NAME']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 control-label small fw-bold">Contact</label>
                                        <div class="col-sm-8">
                                            <input type="tel" name="players[<?php echo $p['ID']; ?>][whatsapp]" class="form-control input-sm" value="<?php echo htmlspecialchars($p['WHATSAPP_NUMBER']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 control-label small fw-bold">Email</label>
                                        <div class="col-sm-8">
                                            <input type="email" name="players[<?php echo $p['ID']; ?>][email]" class="form-control input-sm" value="<?php echo htmlspecialchars($p['EMAIL']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 control-label small fw-bold">DOB</label>
                                        <div class="col-sm-8">
                                            <input type="date" name="players[<?php echo $p['ID']; ?>][dob]" class="form-control input-sm" value="<?php echo $p['DOB'] ?: $today; ?>">
                                        </div>
                                    </div>

                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 control-label small fw-bold">Gender</label>
                                        <div class="col-sm-8">
                                            <select name="players[<?php echo $p['ID']; ?>][gender]" class="form-control input-sm">
                                                <option value="Male" <?php echo ($p['GENDER'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($p['GENDER'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 control-label small fw-bold">City</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="players[<?php echo $p['ID']; ?>][city]" class="form-control input-sm" value="<?php echo htmlspecialchars($p['CITY']); ?>">
                                        </div>
                                    </div>

                                    <div class="checkbox mt-3">
                                        <label class="small text-muted">
                                            <input type="checkbox" name="players[<?php echo $p['ID']; ?>][exist]" value="Y" <?php echo ($p['EXISTING'] == 'Y') ? 'checked' : ''; ?>> 
                                            Existing Member
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <footer class="panel-footer mt-4 text-right">
                            <button type="submit" id="btnUpdateTeam" class="btn btn-primary">Update Entire Team</button>
                            <a href="manage_registrations.php" class="btn btn-default">Cancel</a>
                        </footer>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>

<script src="assets/vendor/jquery/jquery.js"></script>
<script>
    $('#editRegForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#btnUpdateTeam');
        
        submitBtn.prop('disabled', true).text("Updating...");

        $.ajax({
            url: 'api/sync_player.php', // Using the Unified API page created earlier
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    alert("Team registration updated successfully!");
                    window.location.href = 'enrolled_tournaments.php';
                } else {
                    alert("Error: " + data.message);
                    submitBtn.prop('disabled', false).text("Update Entire Team");
                }
            },
            error: function() {
                alert("Connection error occurred.");
                submitBtn.prop('disabled', false).text("Update Entire Team");
            }
        });
    });
</script>

<?php include "footer.php"; ?>