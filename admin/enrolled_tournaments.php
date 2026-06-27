<?php
// PHP 8.1 Error Fixes
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('../dbConnection_PDO.php'); 
include('header.php');
include('sidebar.php');

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // SQL includes Payment Status and specific Approval/Decline dates
    $sql = "SELECT u.*, t.ID AS TEAM_ID_VAL, t.NAME AS TEAM_NAME, tr.ID AS TOURN_ID, 
                   tr.PAYMENT_MAIL, p.STATUS AS PAY_STATUS, p.APPROVED_DATE, p.DECLINED_DATE
            FROM to_users u
            INNER JOIN to_teams t ON u.TEAM_ID = t.ID
            INNER JOIN to_tournaments tr ON t.TOURNAMENT_ID = tr.ID
            LEFT JOIN to_payments p ON p.USER_ID = u.ID AND p.GAME_ID = tr.ID
            WHERE u.USERTYPE = 'Player'
            ORDER BY tr.ID DESC, t.ID DESC, u.ID ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>");
}
?>

<style>
    div.dataTables_wrapper { width: 100%; margin: 0 auto; }
    th { white-space: nowrap; font-size: 11px; background: #f8f9fa; text-align: center; text-transform: uppercase; color: #333; border-bottom: 2px solid #ccc !important; }
    td { white-space: nowrap; font-size: 11px; border-bottom: 1px solid #eee !important; vertical-align: middle !important; }
    .id-badge { font-family: 'Courier New', Courier, monospace; font-weight: bold; font-size: 11px; padding: 4px 6px; }
    
    .team-block-a { background-color: #ffffff !important; } 
    .team-block-b { background-color: #f4f9ff !important; } 
    .new-team-border { border-top: 3px solid #0088cc !important; }
    
    .st-paid, .st-unpaid, .st-success, .st-created, .st-pending {
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: 700;
        display: inline-block;
        min-width: 60px;
        text-align: center;
        text-transform: uppercase;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .st-paid { background-color: #47a447; color: white; }
    .st-unpaid { background-color: #d2322d; color: white; }
    .st-success { background-color: #47a447; color: white; }
    .st-created { background-color: #0088cc; color: white; }
    .st-pending { background-color: #777777; color: white; }

    .btn-get-user { background-color: #17a2b8; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; }
    .btn-create-user { background-color: #e67e22; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; }
</style>

<section role="main" class="content-body">
    <header class="page-header"><h2>Manage Tournament Registrations</h2></header>

    <section class="panel">
        <div class="panel-body">
            <table class="table table-bordered mb-none display nowrap" id="registrationTable" style="width:100%">
                <thead>
                    <tr>
                        <th>Tourn. ID</th>
                        <th>Team Name</th>
                        <th>Player ID</th>
                        <th>Payment Info</th>
                        <th>Approval Action</th>
                        <th>Actions</th>
                        <th>CA ID</th>
                        <th>Sync Action</th>
                        <th>Sync Status</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $processed_teams = []; 
                    $color_toggle = false;

                    foreach ($registrations as $row): 
                        $tid = $row['TEAM_ID_VAL'];
                        $is_first = !in_array($tid, $processed_teams);
                        if ($is_first) {
                            $processed_teams[] = $tid;
                            $color_toggle = !$color_toggle;
                            $boundaryClass = 'new-team-border';
                        } else { $boundaryClass = ''; }

                        $rowBgClass = $color_toggle ? 'team-block-a' : 'team-block-b';
                        
                        $isExisting = (($row['EXISTING'] ?? 'N') == 'Y'); 
                        $isSynced = !empty($row['CA_ID']);
                        
                        // Payment Logic
                        $hasPaid = (($row['PAY_STATUS'] ?? 'N') === 'Y');
                        $isApproved = !empty($row['APPROVED_DATE']) && $hasPaid;
                        $isDeclined = !empty($row['DECLINED_DATE']) && !$hasPaid;
                    ?>
                        <tr class="<?php echo $rowBgClass . ' ' . $boundaryClass; ?>">
                            <td class="text-center"><span class="label label-primary id-badge">#<?php echo str_pad($row['TOURN_ID'] ?? '', 4, "0", STR_PAD_LEFT); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['TEAM_NAME'] ?? 'Singles'); ?></strong></td>
                            <td class="text-center text-primary id-badge">#<?php echo str_pad($row['ID'] ?? '', 4, "0", STR_PAD_LEFT); ?></td>
                            
                            <!-- Column: Payment Info Badge -->
                            <td class="text-center">
                                <span class="<?php echo $hasPaid ? 'st-paid' : 'st-unpaid'; ?>">
                                    <?php echo $hasPaid ? 'PAID' : 'UNPAID'; ?>
                                </span>
                            </td>

                            <!-- Column: Approval Action -->
                            <td class="text-center">
                                <div style="margin-bottom: 4px;">
                                    <?php if ($isApproved): ?>
                                        <span class="st-success" style="background-color:#47a447;">APPROVED</span>
                                    <?php elseif ($isDeclined): ?>
                                        <span class="st-unpaid" style="background-color:#d2322d;">DECLINED</span>
                                    <?php elseif ($hasPaid): ?>
                                        <span class="st-pending" style="background-color:#f39c12;">PENDING</span>
                                    <?php else: ?>
                                        <span class="st-pending">NO PAYMENT</span>
                                    <?php endif; ?>
                                </div>
                                <div class="btn-group">
                                    <button title="Approve Payment" class="btn btn-success btn-xs" onclick="processPayment(<?php echo $row['ID']; ?>, <?php echo $row['TOURN_ID']; ?>, 'approve')"><i class="fa fa-check"></i></button>
                                    <button title="Decline Payment" class="btn btn-danger btn-xs" onclick="processPayment(<?php echo $row['ID']; ?>, <?php echo $row['TOURN_ID']; ?>, 'decline')"><i class="fa fa-times"></i></button>
                                </div>
                            </td>

                            <td class="text-center">
                                <div class="btn-group">
    <button title="Copy Payment Template" class="btn btn-warning btn-xs" 
        onclick="copyFullTemplate(this, '<?php echo $row['ID']; ?>')"
        data-template="<?php echo htmlspecialchars($row['PAYMENT_MAIL'] ?? ''); ?>"
        data-pass="<?php echo htmlspecialchars($row['PASSWORD'] ?? 'abcde'); ?>">
    <i class="fa fa-copy"></i>
</button>
                                    <a href="edit_registration.php?id=<?php echo $row['ID']; ?>" class="btn btn-default btn-xs"><i class="fa fa-pencil"></i></a>
                                    <button class="btn btn-danger btn-xs" onclick="deleteTeam(<?php echo $row['ID']; ?>)"><i class="fa fa-trash"></i></button>
                                </div>
                            </td>

                            <td class="text-center id-badge" id="ca-id-<?php echo $row['ID']; ?>"><?php echo $isSynced ? $row['CA_ID'] : 'Pending'; ?></td>
                            
                            <td class="text-center" id="action-cell-<?php echo $row['ID']; ?>">
                                <?php if (!$isSynced): ?>
                                    <button class="<?php echo $isExisting ? 'btn-get-user' : 'btn-create-user'; ?>" onclick="syncUser(<?php echo $row['ID']; ?>, '<?php echo $isExisting ? 'get' : 'create'; ?>')">
                                        <i class="fa <?php echo $isExisting ? 'fa-refresh' : 'fa-plus-circle'; ?>"></i> <?php echo $isExisting ? 'Get User' : 'Create User'; ?>
                                    </button>
                                <?php else: ?><i class="fa fa-check text-success"></i> Synced<?php endif; ?>
                            </td>

                            <td class="text-center" id="status-cell-<?php echo $row['ID']; ?>">
                                <span class="<?php echo $isSynced ? ($isExisting ? 'st-success' : 'st-created') : 'st-pending'; ?>">
                                    <?php echo $isSynced ? ($isExisting ? 'SUCCESSFUL' : 'PLAYER CREATED') : 'PENDING'; ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($row['NAME'] ?? ''); ?></td>
                            <td id="email_<?php echo $row['ID']; ?>"><?php echo htmlspecialchars($row['EMAIL'] ?? ''); ?></td>
                            <td id="whatsapp_<?php echo $row['ID']; ?>"><?php echo htmlspecialchars($row['WHATSAPP_NUMBER'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<script src="assets/vendor/jquery/jquery.js"></script>
<script>
var csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";

$(document).ready(function() {
    $('#registrationTable').DataTable({ "ordering": false, "pageLength": 50, "scrollX": true });
});

function processPayment(userId, gameId, type) {
    var confirmMsg = type === 'approve' ? "Approve this payment and update status to PAID?" : "Decline this payment and update status to UNPAID?";
    if(!confirm(confirmMsg)) return;

    $.ajax({
        url: 'api/sync_player.php',
        type: 'POST',
        dataType: 'json',
        data: { action: type + '_payment', user_id: userId, game_id: gameId, csrf_token: csrfToken },
        success: function(res) {
            if(res.success) location.reload(); else alert(res.message);
        },
        error: function() { alert("Critical error in payment processing."); }
    });
}

// ... rest of your existing JS functions (copyFullTemplate, deleteTeam, syncUser) ...
function copyFullTemplate(btnElement, id) {
    var rawTemplate = $(btnElement).attr('data-template');
    var email = $('#email_' + id).text().trim();
    var password = $(btnElement).attr('data-pass');

    if (!rawTemplate) { 
        alert("Error: No template found."); 
        return; 
    }

    // 1. Replace Placeholders
    var content = rawTemplate.replace(/\[Username\]/g, email)
                             .replace(/\[Password\]/g, password);

    // 2. Format for WhatsApp/Plain Text (Markdown)
    var whatsappText = content
        .replace(/<br\s*\/?>/gi, "\n")               // Convert <br> to newline
        .replace(/<\/p>/gi, "\n\n")                  // Convert </p> to double newline
        .replace(/<p>/gi, "")                        // Remove <p>
        .replace(/<strong>(.*?)<\/strong>/gi, "*$1*") // Convert bold to *bold*
        .replace(/<b>(.*?)<\/b>/gi, "*$1*")           // Convert bold to *bold*
        .replace(/<li>(.*?)<\/li>/gi, "• $1\n")      // Convert list items to bullet points
        .replace(/<ul[^>]*>|<\/ul>/gi, "")           // Remove <ul> tags
        .replace(/<[^>]+>/g, "");                    // Remove any remaining HTML tags

    // Trim extra whitespace
    whatsappText = whatsappText.trim();

    // 3. Create HTML version for Gmail/Word
    var htmlVersion = content;

    // 4. Copy both formats to the clipboard
    if (navigator.clipboard && window.ClipboardItem) {
        const blobHtml = new Blob([htmlVersion], { type: "text/html" });
        const blobPlain = new Blob([whatsappText], { type: "text/plain" });
        
        const data = [new ClipboardItem({
            ["text/html"]: blobHtml,
            ["text/plain"]: blobPlain
        })];

        navigator.clipboard.write(data).then(function() {
            // Show checkmark on button
            const originalHtml = $(btnElement).html();
            $(btnElement).removeClass('btn-warning').addClass('btn-success').html('<i class="fa fa-check"></i>');
            setTimeout(function() {
                $(btnElement).removeClass('btn-success').addClass('btn-warning').html(originalHtml);
            }, 1500);
        }).catch(function(err) {
            // Fallback
            var textArea = document.createElement("textarea");
            textArea.value = whatsappText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            alert("Copied!");
        });
    }
}
function deleteTeam(id) {
    if(confirm("Delete ENTIRE team?")) {
        $.post('api/sync_player.php', { id: id, action: 'delete_team', csrf_token: csrfToken }, function(res) {
            if(res.success) location.reload(); else alert(res.message);
        }, 'json');
    }
}

function syncUser(id, action) {
    var btn = $(event.target).closest('button');
    var originalHtml = btn.html(); 
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

    $.ajax({
        url: 'api/sync_player.php', 
        type: 'POST',
        dataType: 'json',
        data: { id: id, action: action, csrf_token: csrfToken },
        success: function(res) {
            if (res.success) location.reload();
            else { alert("API Error: " + res.message); btn.prop('disabled', false).html(originalHtml); }
        },
        error: function() { alert("Critical Error."); btn.prop('disabled', false).html(originalHtml); }
    });
}
</script>
<?php include('footer.php'); ?>