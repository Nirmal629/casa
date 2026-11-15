<?php
// This standalone entry point renders the shared header directly, so it must
// initialize the application autoloader before the view references Session.
require_once __DIR__ . '/../init.php';
?>

<!-----Header------>
<?php include __DIR__ . "/../app/Views/layouts/header/header.php"; ?>

<!-- Tournament Specific Styles -->
<?php $tournamentAssetPrefix = function_exists('appBasePath') ? appBasePath() : '/'; ?>
<link rel="stylesheet" href="<?= htmlspecialchars($tournamentAssetPrefix, ENT_QUOTES, 'UTF-8') ?>tournament/assets/css/bracket.css" type="text/css">

<section class="tournament_page bottomSide_gap">
    <div class="cust_container">

        <?php include __DIR__ . "/battle-tournament.php"; ?>

    </div>
</section>

<!------footer------>
<?php include __DIR__ . "/../app/Views/layouts/footer/footer.php"; ?>
