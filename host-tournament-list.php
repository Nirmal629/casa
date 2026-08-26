<!----Host Tournament list---->
<section class="">
    <div class="custom_card">
        <h6 class="card_heading">My Tournaments</h6>

        <div class="discoverGames_wraper hostWrapper">
            <?php
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $hostTournaments = [];
            $hostTournamentError = '';

            try {
                include_once __DIR__ . '/dbConnection_PDO.php';

                $hostTournamentStmt = $pdo->prepare("
                    SELECT
                        e.*,
                        (SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = e.ID) AS joined_count
                    FROM to_tournaments e
                    WHERE e.HOST_ID = :host_id
                    ORDER BY e.ID DESC
                ");
                $hostTournamentStmt->execute([':host_id' => (int)($_SESSION['user_id'] ?? 0)]);
                $hostTournaments = $hostTournamentStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $hostTournamentError = $e->getMessage();
            }

            if ($hostTournamentError !== '') {
                echo "<p class='label'>Unable to load tournaments right now.</p>";
            } elseif (empty($hostTournaments)) {
                echo "<p class='label'>You have not organized any tournaments yet.</p>";
            } else {
                foreach ($hostTournaments as $tournament) {
                    $tName = trim((string)($tournament['CUP_NAME'] ?? ''));
                    if ($tName === '') {
                        $tName = trim((string)($tournament['HOST_NAME'] ?? ''));
                    }
                    if ($tName === '') {
                        $tName = 'Tournament';
                    }

                    $tCategory = trim((string)($tournament['EVENT_CATEGORY'] ?? ''));
                    $tGender = trim((string)($tournament['GENDER_CATEGORY'] ?? ''));
                    $tType = trim((string)($tournament['EVENT_TYPE'] ?? ''));
                    $tVenue = trim((string)($tournament['EVENT_VENUE'] ?? ''));
                    $tDate = !empty($tournament['EVENT_DATE']) && $tournament['EVENT_DATE'] !== '0000-00-00'
                        ? date('D, d M Y', strtotime($tournament['EVENT_DATE']))
                        : 'TBD';
                    $tTime = !empty($tournament['EVENT_TIME'])
                        ? date('h:i A', strtotime($tournament['EVENT_TIME']))
                        : 'TBD';
                    $tStatus = trim((string)($tournament['STATUS'] ?? ''));
                    $joinedCount = (int)($tournament['joined_count'] ?? 0);
                    $tId = (int)$tournament['ID'];

                    $headline = trim(implode(' - ', array_filter([$tCategory, $tGender, $tType])));
                    if ($headline === '') {
                        $headline = 'Tournament';
                    }
                    ?>
                    <div class='discoverGames_card'>
                        <div class='d-flex flex-wrap align-items-center justify-content-between gap-2' style='margin-bottom: 8px; background-color: #0d6efda1; padding: 8px 10px; border-radius: 8px 8px 0px 0px;'>
                            <p class='text-white desc fw-bold m-0' style='font-size: 85%;'><?php echo htmlspecialchars($headline); ?></p>
                            <?php if ($tStatus !== ''): ?>
                                <span class='badge btn-success'><?php echo htmlspecialchars($tStatus); ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="padding: 4px 10px 12px;">
                            <p class="desc fw-bold m-0" style="font-size: 95%;"><?php echo htmlspecialchars($tName); ?></p>
                            <p class="desc m-0" style="font-size: 82%;"><i class="fa fa-calendar-alt"></i> <?php echo htmlspecialchars($tDate); ?> &nbsp; <i class="fa fa-clock"></i> <?php echo htmlspecialchars($tTime); ?></p>
                            <?php if ($tVenue !== ''): ?>
                                <p class="desc m-0" style="font-size: 82%;"><i class="fa fa-map-marker-alt"></i> <?php echo htmlspecialchars($tVenue); ?></p>
                            <?php endif; ?>
                            <p class="desc m-0" style="font-size: 82%;"><i class="fa fa-check-circle"></i> <?php echo $joinedCount; ?> teams joined</p>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <a href="tournament/?id=<?php echo $tId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-tower-broadcast"></i> Manage Live
                                </a>
                                <a href="host-edit-tournament.php?id=<?php echo $tId; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>
