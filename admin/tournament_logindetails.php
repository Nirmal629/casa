<?php
include('../dbConnection_PDO.php'); 
include('header.php');
include('sidebar.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->query("SELECT t.CUP_NAME, m.* FROM to_tournaments t 
                         INNER JOIN to_tournamnt_message m ON t.ID = m.TOURNAMENT_ID 
                         ORDER BY t.ID DESC");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection Error: " . $e->getMessage());
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Professional Email Generator</h2>
    </header>

    <div class="row">
        <div class="col-md-4">
            <section class="panel">
                <header class="panel-heading"><h2 class="panel-title">Generator Controls</h2></header>
                <div class="panel-body">
                    <div class="form-group mb-3">
                        <label class="fw-bold">1. Select Tournament</label>
                        <select id="tourney_data" class="form-control" onchange="generateEmail()">
                            <option value="">-- Choose Tournament --</option>
                            <?php foreach($settings as $s): 
                                $deadline = date("l, d F, Y", strtotime($s['PAYMENT_DEADLINE']));
                            ?>
                                <option value="<?php echo htmlspecialchars(json_encode([
                                    'amt' => $s['AMOUNT'],
                                    'pid' => $s['PAYMENT_ID'],
                                    'dl'  => $deadline
                                ])); ?>">
                                    <?php echo $s['CUP_NAME']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-bold">2. Payment Status</label>
                        <select id="pay_status" class="form-control" onchange="generateEmail()">
                            <option value="confirmed">Payment Successfully Confirmed</option>
                            <option value="pending">Payment Not Received Yet</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-bold">3. Login Details</label>
                        <input type="text" id="user_val" class="form-control mb-2" placeholder="Username" onkeyup="generateEmail()">
                        <input type="text" id="pass_val" class="form-control" placeholder="Temporary Password" onkeyup="generateEmail()">
                    </div>

                    <button class="btn btn-success btn-block w-100 mt-3" onclick="copyEmail()">
                        <i class="fa fa-copy"></i> Copy Full Email Template
                    </button>
                </div>
            </section>
        </div>

        <div class="col-md-8">
            <section class="panel">
                <header class="panel-heading"><h2 class="panel-title">Professional Preview</h2></header>
                <div class="panel-body">
                    <!-- Note: Removed white-space: pre-wrap to ensure HTML-only rendering -->
                    <div id="email_preview" class="p-4 border rounded shadow-inner" style="background: #fff; min-height: 600px; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">
                        <!-- Generated HTML Loads Here -->
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>

<script>
function generateEmail() {
    const tourneyJson = document.getElementById('tourney_data').value;
    const status = document.getElementById('pay_status').value;
    const user = document.getElementById('user_val').value || "[Username]";
    const pass = document.getElementById('pass_val').value || "[Password]";

    let amt = "[Amount]", pid = "casaclubpayment1@gmail.com", dl = "[Deadline Date]";

    if(tourneyJson) {
        const data = JSON.parse(tourneyJson);
        amt = data.amt;
        pid = data.pid;
        dl = data.dl;
    }

    let statusLine = "";
    let paymentInstructions = "";

    if(status === 'confirmed') {
        statusLine = `<span style="color: red; font-weight: bold;">Your payment has been successfully confirmed.</span> We are pleased to share your login credentials for casa-games.com:`;
    } else {
        statusLine = `<span style="color: red; font-weight: bold;">We have not recieved your payment yet.</span> We are pleased to share your login credentials for casa-games.com:`;
        
        paymentInstructions = `<br><br><span style="color: red;">To confirm your participation, please complete the registration payment as per the details below ignore if already transferred:<br>
        - Amount: $${amt}<br>
        - Payment Method: E-transfer<br>
        - Payment ID: ${pid}<br>
        - Payment Deadline: ${dl}</span>`;
    }

    // Using <br> instead of new lines for Gmail compatibility
    const htmlTemplate = `
        <strong>Tournament Access & Login Details – Casa Games</strong><br><br>
        Dear Participant,<br><br>
        ${statusLine}<br><br>
        Website: <a href="https://casa-games.com">https://casa-games.com</a><br><br>
        Username: ${user}<br>
        Temporary Password: ${pass}<br><br>
        For security reasons, please log in and change your password immediately.
        ${paymentInstructions}<br><br>
        <strong>Further Tournament Instructions</strong><br><br>
        • Please log in to verify your player profile and event category<br>
        • Match schedules, draws, and live scores will be available on the dashboard<br>
        • Ensure you report to the venue at least 30 minutes before the event time<br>
        • Carry valid ID and proper sports attire on match day<br><br>
        For any assistance or queries, please contact the tournament administrator.<br><br>
        We wish you the very best and look forward to your participation.<br><br>
        — Casa Games Admin Team 🏸
    `;

    document.getElementById('email_preview').innerHTML = htmlTemplate;
}

function copyEmail() {
    const emailDiv = document.getElementById('email_preview');
    
    // Create a temporary hidden editable element to capture rich text
    const tempDiv = document.createElement('div');
    tempDiv.contentEditable = true;
    tempDiv.innerHTML = emailDiv.innerHTML;
    document.body.appendChild(tempDiv);
    
    // Select the content
    const range = document.createRange();
    range.selectNodeContents(tempDiv);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    
    try {
        // Execute copy command
        const successful = document.execCommand('copy');
        if (successful) {
            alert("Email Template Copied! You can now paste (Ctrl+V) into Gmail.");
        }
    } catch (err) {
        alert("Unable to copy. Please select the text manually.");
    }
    
    // Cleanup
    document.body.removeChild(tempDiv);
    selection.removeAllRanges();
}

// Initial Run
window.onload = generateEmail;
</script>

<?php include('footer.php'); ?>