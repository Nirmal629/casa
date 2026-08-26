<?php
// Ensure the login session is available before any output is sent by this header,
// so tournament pages can read $_SESSION (host detection, etc.).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tournamentBackTarget = 'https://casainfotech.com/staging/player-hub.php';
$tournamentCurrentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$headerTournamentId = isset($tournamentId) ? (int)$tournamentId : (int)($_GET['id'] ?? 0);
$headerTournamentName = 'Tournament';
$headerTournamentDashboardUrl = 'index.php';
$headerPlayoffDashboardUrl = 'court-dashboard.php';

try {
    include_once __DIR__ . '/../../dbConnection_PDO.php';
    $headerPdo = isset($pdo) && $pdo instanceof PDO
        ? $pdo
        : new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

    if ($headerTournamentId <= 0) {
        $latestTournament = $headerPdo->query("SELECT * FROM to_tournaments ORDER BY ID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $headerTournamentId = (int)($latestTournament['ID'] ?? 0);
        $headerTournamentRow = $latestTournament ?: [];
    } else {
        $headerTournamentStmt = $headerPdo->prepare("SELECT * FROM to_tournaments WHERE ID = :id LIMIT 1");
        $headerTournamentStmt->execute([':id' => $headerTournamentId]);
        $headerTournamentRow = $headerTournamentStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    foreach (['CUP_NAME', 'HOST_NAME', 'NAME', 'EVENT_NAME', 'TITLE'] as $headerNameColumn) {
        if (!empty($headerTournamentRow[$headerNameColumn])) {
            $headerTournamentName = (string)$headerTournamentRow[$headerNameColumn];
            break;
        }
    }
} catch (Exception $e) {
    $headerTournamentRow = [];
}

if ($headerTournamentId > 0) {
    $headerTournamentDashboardUrl = 'index.php?id=' . $headerTournamentId;
    $headerPlayoffDashboardUrl = 'court-dashboard.php?id=' . $headerTournamentId;
}

if ($tournamentCurrentPage === 'court-dashboard.php') {
    $backTournamentId = $headerTournamentId;
    $tournamentBackTarget = 'index.php' . ($backTournamentId > 0 ? '?id=' . $backTournamentId : '');
}

$isTournamentDashboard = ($tournamentCurrentPage === 'index.php' || $tournamentCurrentPage === '');
$isPlayoffDashboard = ($tournamentCurrentPage === 'court-dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Tournament Game</title>

    <!----favicon------>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon_io/site.webmanifest">

    <!---font-awesome/6----->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!----glightbox-css---->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />

    <!------AOS------->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-----Google-fonts-------->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

    <!-- Slick slider link-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!----bootstrap link---->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


    <!-----css file link----->
    <link rel="stylesheet" href="assets/css/bracket.css" type="text/css">

    <link rel="stylesheet" href="assets/css/style.css?v=1.23" type="text/css">
</head>

<body>
    <!------Main Header------->
    <section class="main_header" id="main_Header">
        <div class="cust_container">
            <div class="wraper">
                <button type="button" class="tournament-back-btn" data-back-target="<?php echo htmlspecialchars($tournamentBackTarget); ?>" aria-label="Go back to previous page" title="Go back">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>

                <a href="index.php">
                    <figure class="Logo_area m-0">
                        <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                    </figure>
                </a>

                <div class="breadcrumbs">
                    <a class="breadcrumb-link breadcrumb-player" href="https://casainfotech.com/staging/player-hub.php">The Player Hub</a>
                    <a class="breadcrumb-link breadcrumb-tournament <?php echo $isTournamentDashboard ? 'active live' : ''; ?>" href="<?php echo htmlspecialchars($headerTournamentDashboardUrl); ?>" <?php echo $isTournamentDashboard ? 'aria-current="page"' : ''; ?>>
                        <span>The Tournament Dashboard</span>
                        <strong><?php echo htmlspecialchars($headerTournamentName); ?></strong>
                        <?php if ($isTournamentDashboard): ?><em>Live</em><?php endif; ?>
                    </a>
                    <a class="breadcrumb-link breadcrumb-playoff <?php echo $isPlayoffDashboard ? 'active live' : ''; ?>" href="<?php echo htmlspecialchars($headerPlayoffDashboardUrl); ?>" <?php echo $isPlayoffDashboard ? 'aria-current="page"' : ''; ?>>
                        <span>The Playoff Dashboard</span>
                        <?php if ($isPlayoffDashboard): ?><em>Live</em><?php endif; ?>
                    </a>
                </div>

                <!-- <div class="menubar_box">
                    <div class="top_area">
                        <a href="index.php" class="Logo_area btn">
                            <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                        </a>
                    </div>
                    <ul class="navber_wrap">
                        <li class="active"><a href="index.php" class="btn text-white">Home</a></li>
                        <li class=""><a href="#" class="btn text-white">Player Hub</a></li>
                    </ul>
                    <ul class="socialIcon_all">
                        <li><a href="https://facebook.com/" class="link_" target="_blank"><i class="fa-brands fa-facebook-f"></i></a></li>
                        <li><a href="https://linkedin.com/" class="link_" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a></li>
                        <li><a href="https://instagram.com/" class="link_" target="_blank"><i class="fa-brands fa-instagram"></i></a></li>
                        <li><a href="https://twitter.com/" class="link_" target="_blank"><i class="fa-brands fa-x-twitter"></i></a></li>
                    </ul>
                </div>
                <a class="Organized_by" href="battle-page.php">
                    <img src="./assets/images/logo/Final-Logo.png" alt="Organizer" class="org-logo">
                    <div class="org-text">
                        Organized by <span>Casa Tournaments</span>
                    </div>
                </a>
                <button class="responsivemenubar_btn btn" id="open_Sidebar">
                    <span class="menuBar_line"></span>
                </button> -->
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tournamentBackButton = document.querySelector('.tournament-back-btn');
            if (!tournamentBackButton) {
                return;
            }

            tournamentBackButton.addEventListener('click', function () {
                window.location.href = tournamentBackButton.getAttribute('data-back-target') || 'https://casainfotech.com/staging/player-hub.php';
            });
        });
    </script>
