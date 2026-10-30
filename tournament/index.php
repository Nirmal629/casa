<!-----Header------>
<?php include __DIR__ . "/../app/Views/layouts/header/header.php"; ?>

<!-- Tournament Specific Styles -->
<link rel="stylesheet" href="/tournament/assets/css/bracket.css" type="text/css">

<section class="tournament_page bottomSide_gap">
    <div class="cust_container">

        <?php include __DIR__ . "/battle-tournament.php"; ?>

    </div>
</section>

<!------footer------>
<?php include __DIR__ . "/../app/Views/layouts/footer/footer.php"; ?>