<?php
use App\Config\Database;
$pdo  = Database::getPdo();
$conn = Database::getMysqli();

include __DIR__ . '/../layouts/header/inner-header.php';

$userRole = strtolower(trim((string)($_SESSION['usertype'] ?? '')));
if ($userRole !== 'host' && $userRole !== 'trainer') {
    header('Location: /');
    exit;
}

include __DIR__ . "/../layouts/header/inner-header.php";
?>

<link rel="stylesheet" href="../app/Views/css/host/dashboard.css">

<!----Host Account Section------>
<section class="bothSide_gap">
    <div class="cust_container">
        <h2 class="heading">Host Account</h2>

        <?php include __DIR__ . "/host-club-settings.php"; ?>
    </div>
</section>

<?php include __DIR__ . "/../layouts/footer/footer.php"; ?>
