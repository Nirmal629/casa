<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!in_array(trim((string)($_SESSION['usertype'] ?? '')), ['Host', 'Trainer'], true)) { header('Location: index.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

require_once __DIR__ . '/dbConnection_PDO.php';
$hostId = (int)($_SESSION['user_id'] ?? 0);
$tournamentId = filter_input(INPUT_GET, 'tournament_id', FILTER_VALIDATE_INT) ?: 0;
if ($hostId <= 0 || $tournamentId <= 0) { http_response_code(404); exit('Tournament not found.'); }

$tournamentStmt = $pdo->prepare('SELECT ID, CUP_NAME FROM to_tournaments WHERE ID = ? AND HOST_ID = ? LIMIT 1');
$tournamentStmt->execute([$tournamentId, $hostId]);
$tournament = $tournamentStmt->fetch(PDO::FETCH_ASSOC);
if (!$tournament) { http_response_code(403); exit('You do not have permission to manage these registrations.'); }

$registrationsStmt = $pdo->prepare("SELECT u.*, t.ID AS TEAM_ID_VAL, t.NAME AS TEAM_NAME, tr.ID AS TOURN_ID,
        tr.PAYMENT_MAIL, p.STATUS AS PAY_STATUS, p.APPROVED_DATE, p.DECLINED_DATE
    FROM to_users u
    INNER JOIN to_teams t ON u.TEAM_ID = t.ID
    INNER JOIN to_tournaments tr ON t.TOURNAMENT_ID = tr.ID
    LEFT JOIN to_payments p ON p.USER_ID = u.ID AND p.GAME_ID = tr.ID
    WHERE u.USERTYPE = 'Player' AND tr.ID = ? AND tr.HOST_ID = ?
    ORDER BY t.ID DESC, u.ID ASC");
$registrationsStmt->execute([$tournamentId, $hostId]);
$registrations = $registrationsStmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/inner-header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
<style>
    .registration-page { max-width: 1400px; margin: 36px auto; padding: 0 20px; }
    .registration-page th, .registration-page td { white-space: nowrap; font-size: 12px; vertical-align: middle; }
    .team-block-a { background: #fff !important; } .team-block-b { background: #f4f9ff !important; } .new-team-border { border-top: 3px solid #0088cc !important; }
    .status-chip { padding: 3px 9px; border-radius: 12px; color: #fff; font-size: 10px; font-weight: 700; display: inline-block; min-width: 65px; text-align: center; }
    .status-paid, .status-success { background:#47a447; } .status-unpaid { background:#d2322d; } .status-created { background:#0088cc; } .status-pending { background:#777; }
    .inline-actions { display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
</style>
<main class="registration-page">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="mb-1">Manage Tournament Registrations</h2><p class="text-muted mb-0"><?php echo htmlspecialchars($tournament['CUP_NAME'] ?: 'Tournament', ENT_QUOTES, 'UTF-8'); ?></p></div><a class="btn btn-outline-secondary" href="host-dashboard.php#Tournament">Back to tournaments</a></div>
    <div class="card shadow-sm"><div class="card-body">
        <div class="table-responsive"><table class="table table-bordered align-middle" id="registrationTable" style="width:100%"><thead><tr><th>Team Name</th><th>Name</th><th>Email</th><th>Contact</th><th>Payment Status</th><th>Payment Action</th><th>Actions</th><th>CASA Database</th><th>CASA Action</th></tr></thead><tbody>
        <?php $processedTeams = []; $colorToggle = false; foreach ($registrations as $row): $teamId = (int)$row['TEAM_ID_VAL']; $first = !in_array($teamId, $processedTeams, true); if ($first) { $processedTeams[] = $teamId; $colorToggle = !$colorToggle; } $paid = ($row['PAY_STATUS'] ?? 'N') === 'Y'; $approved = !empty($row['APPROVED_DATE']) && $paid; $declined = !empty($row['DECLINED_DATE']) && !$paid; $synced = !empty($row['CA_ID']); $existing = ($row['EXISTING'] ?? 'N') === 'Y'; ?>
            <tr class="<?php echo $colorToggle ? 'team-block-a' : 'team-block-b'; echo $first ? ' new-team-border' : ''; ?>">
                <td><strong><?php echo htmlspecialchars($row['TEAM_NAME'] ?: 'Singles', ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo htmlspecialchars($row['NAME'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td><td id="email_<?php echo (int)$row['ID']; ?>"><?php echo htmlspecialchars($row['EMAIL'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row['WHATSAPP_NUMBER'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="text-center"><span class="status-chip <?php echo $paid ? 'status-paid' : 'status-unpaid'; ?>"><?php echo $paid ? 'PAID' : 'UNPAID'; ?></span></td>
                <td class="text-center"><div class="inline-actions"><button title="Approve Payment" class="btn btn-success btn-sm" onclick="processPayment(<?php echo (int)$row['ID']; ?>, 'approve')"><i class="fa fa-check"></i></button><span class="status-chip <?php echo $approved ? 'status-success' : ($declined ? 'status-unpaid' : 'status-pending'); ?>"><?php echo $approved ? 'APPROVED' : ($declined ? 'DECLINED' : ($paid ? 'PENDING' : 'NO PAYMENT')); ?></span><button title="Decline Payment" class="btn btn-danger btn-sm" onclick="processPayment(<?php echo (int)$row['ID']; ?>, 'decline')"><i class="fa fa-times"></i></button></div></td>
                <td class="text-center"><div class="inline-actions"><button title="Copy Payment Template" class="btn btn-warning btn-sm" onclick="copyFullTemplate(this, <?php echo (int)$row['ID']; ?>)" data-template="<?php echo htmlspecialchars($row['PAYMENT_MAIL'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-pass="<?php echo htmlspecialchars($row['PASSWORD'] ?? 'abcde', ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-copy"></i></button><a href="host-edit-registration.php?id=<?php echo (int)$row['ID']; ?>&tournament_id=<?php echo $tournamentId; ?>" class="btn btn-outline-primary btn-sm" title="Edit registration"><i class="fa fa-pencil"></i></a><button title="Delete entire team" class="btn btn-danger btn-sm" onclick="deleteTeam(<?php echo (int)$row['ID']; ?>)"><i class="fa fa-trash"></i></button></div></td>
                <td class="text-center"><span class="status-chip <?php echo $synced ? ($existing ? 'status-success' : 'status-created') : 'status-pending'; ?>"><?php echo $synced ? ($existing ? 'SUCCESSFUL' : 'PLAYER CREATED') : 'PENDING'; ?></span></td>
                <td class="text-center"><?php if (!$synced): ?><button class="btn <?php echo $existing ? 'btn-info' : 'btn-warning'; ?> btn-sm" onclick="syncUser(this, <?php echo (int)$row['ID']; ?>, '<?php echo $existing ? 'get' : 'create'; ?>')"><i class="fa <?php echo $existing ? 'fa-refresh' : 'fa-plus-circle'; ?>"></i> <?php echo $existing ? 'Get User' : 'Create User'; ?></button><?php else: ?><span class="text-success"><i class="fa fa-check"></i> Synced</span><?php endif; ?></td>
            </tr>
        <?php endforeach; ?></tbody></table></div>
    </div></div>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
const csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;
const tournamentId = <?php echo (int)$tournamentId; ?>;
$(function(){ $('#registrationTable').DataTable({ ordering:false, pageLength:50, scrollX:true }); });
function callAction(data) { return $.ajax({url:'api/host_sync_player.php', type:'POST', dataType:'json', data:Object.assign(data,{csrf_token:csrfToken,tournament_id:tournamentId})}); }
function processPayment(userId, action) { if (!confirm(action === 'approve' ? 'Approve this payment and update status to PAID?' : 'Decline this payment and update status to UNPAID?')) return; callAction({action:action+'_payment',user_id:userId}).done(r=>r.success?location.reload():alert(r.message)).fail(()=>alert('Payment processing failed.')); }
function deleteTeam(id) { if (!confirm('Delete ENTIRE team?')) return; callAction({action:'delete_team',id:id}).done(r=>r.success?location.reload():alert(r.message)).fail(()=>alert('Delete failed.')); }
function syncUser(button,id,action) { const $button=$(button), original=$button.html(); $button.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Processing...'); callAction({action:action,id:id}).done(r=>r.success?location.reload():(alert(r.message),$button.prop('disabled',false).html(original))).fail(()=>{alert('Sync failed.');$button.prop('disabled',false).html(original);}); }
function copyFullTemplate(button,id) { const content=$(button).attr('data-template') || ''; if (!content) return alert('No payment template found.'); const html=content.replace(/\[Username\]/g,$('#email_'+id).text().trim()).replace(/\[Password\]/g,$(button).attr('data-pass') || ''); const text=html.replace(/<br\s*\/?>/gi,'\n').replace(/<\/p>/gi,'\n\n').replace(/<p>/gi,'').replace(/<strong>(.*?)<\/strong>/gi,'*$1*').replace(/<b>(.*?)<\/b>/gi,'*$1*').replace(/<li>(.*?)<\/li>/gi,'• $1\n').replace(/<[^>]+>/g,'').trim(); const done=()=>{const old=$(button).html();$(button).removeClass('btn-warning').addClass('btn-success').html('<i class="fa fa-check"></i>');setTimeout(()=>$(button).removeClass('btn-success').addClass('btn-warning').html(old),1500);}; if (navigator.clipboard && window.ClipboardItem) { navigator.clipboard.write([new ClipboardItem({'text/html':new Blob([html],{type:'text/html'}),'text/plain':new Blob([text],{type:'text/plain'})})]).then(done).catch(()=>navigator.clipboard.writeText(text).then(done).catch(()=>alert('Unable to copy the template.'))); } else { navigator.clipboard.writeText(text).then(done).catch(()=>alert('Unable to copy the template.')); } }
</script>
<?php include 'includes/footer.php'; ?>
