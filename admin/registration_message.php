<?php
include('../dbConnection_PDO.php'); 
include('header.php');
include('sidebar.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Connection Error: " . $e->getMessage() . "</div>");
}

// --- SAVE LOGIC ---
if (isset($_POST['save_settings'])) {
    try {
        // Updated table and column names to match your schema
        $sql = "INSERT INTO to_tournamnt_message 
                (TOURNAMENT_ID, AMOUNT, PAYMENT_ID, PAYMENT_DEADLINE, REPORTING_TIME, MATCH_START_TIME, DRAW_ANNOUNCEMENT, SHUTTLE_TYPE) 
                VALUES (:tid, :amt, :pid, :pdl, :rt, :mst, :da, :st)
                ON DUPLICATE KEY UPDATE 
                AMOUNT = :amt, PAYMENT_ID = :pid, PAYMENT_DEADLINE = :pdl, 
                REPORTING_TIME = :rt, MATCH_START_TIME = :mst, 
                DRAW_ANNOUNCEMENT = :da, SHUTTLE_TYPE = :st";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tid' => $_POST['tournament_id'],
            ':amt' => $_POST['amount'],
            ':pid' => $_POST['payment_id'],
            ':pdl' => $_POST['deadline'],      
            ':rt'  => $_POST['reporting_time'], 
            ':mst' => $_POST['match_start_time'], 
            ':da'  => $_POST['draw_announcement'], 
            ':st'  => $_POST['shuttle_type']
        ]);
        echo "<script>alert('Settings Saved Successfully!');</script>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Save Error: " . $e->getMessage() . "</div>";
    }
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Edit Confirmation Modal Text</h2>
    </header>

    <div class="panel">
        <div class="panel-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label small text-muted">Select Tournament</label>
                        <select name="tournament_id" class="form-control" required>
                            <option value="">-- Choose --</option>
                            <?php
                            $query = $pdo->query("SELECT ID, CUP_NAME FROM to_tournaments ORDER BY ID DESC");
                            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='".$row['ID']."'>".$row['CUP_NAME']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Payment Amount (e.g. 80.00)</label>
                        <input type="text" name="amount" class="form-control" placeholder="80.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Payment ID (Email)</label>
                        <input type="email" name="payment_id" class="form-control" value="casaclubpayment1@gmail.com">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Payment Deadline Date</label>
                        <input type="date" name="deadline" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Reporting Time</label>
                        <input type="time" name="reporting_time" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Match Start Time</label>
                        <input type="time" name="match_start_time" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Draw Announcement Date</label>
                        <input type="date" name="draw_announcement" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Shuttle Type</label>
                        <select name="shuttle_type" class="form-control">
                            <option value="Feather">Feather</option>
                            <option value="Nylon">Nylon</option>
                        </select>
                    </div>
                </div>

                <div class="mt-2">
                    <button type="submit" name="save_settings" class="btn btn-primary px-4">Save Tournament Settings</button>
                </div>
            </form>
        </div>
    </div>
</section>
<?php include('footer.php'); ?>