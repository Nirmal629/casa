<?php
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedGroup = trim($_GET['group'] ?? '');
$selectedMatchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$dbError = '';
$groups = [];
$groupMatches = [];
$stageMatches = [
    'QUARTER_FINAL' => [],
    'SEMI_FINAL' => [],
    'FINAL' => [],
];
$standings = [];
$matrixTeams = [];
$matrixResults = [];
$rallyLogs = [];

function courtDashboardPlayers(?string $players): array
{
    return array_values(array_filter(explode('||', $players ?? '')));
}

function courtDashboardTeamLabel(array $row, string $prefix): string
{
    return htmlspecialchars($row[$prefix . '_NAME'] ?? '-');
}

function courtDashboardWinner(array $row): string
{
    if (empty($row['WINNER_TEAM_ID'])) {
        return '-';
    }
    if ((int)$row['WINNER_TEAM_ID'] === (int)$row['TEAM_1_ID']) {
        return htmlspecialchars($row['TEAM_1_NAME'] ?? '-');
    }
    if ((int)$row['WINNER_TEAM_ID'] === (int)$row['TEAM_2_ID']) {
        return htmlspecialchars($row['TEAM_2_NAME'] ?? '-');
    }
    return '-';
}

try {
    include_once __DIR__ . '/../dbConnection_PDO.php';
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($tournamentId <= 0) {
        $latestTournament = $pdo->query("SELECT ID FROM to_tournaments ORDER BY ID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $tournamentId = (int)($latestTournament['ID'] ?? 0);
    }

    if ($tournamentId > 0) {
        $groupStmt = $pdo->prepare("
            SELECT DISTINCT GROUP_NAME
            FROM to_standings
            WHERE TOURNAMENT_ID = :tournament_id
              AND STAGE = 'GROUP'
              AND GROUP_NAME IS NOT NULL
              AND GROUP_NAME <> ''
            ORDER BY GROUP_NAME
        ");
        $groupStmt->execute([':tournament_id' => $tournamentId]);
        $groups = $groupStmt->fetchAll(PDO::FETCH_COLUMN);
        if ($selectedGroup === '' && !empty($groups)) {
            $selectedGroup = (string)$groups[0];
        }

        $matchSql = "
            SELECT
                m.*,
                t1.NAME AS TEAM_1_NAME,
                t2.NAME AS TEAM_2_NAME,
                (SELECT GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR ' / ') FROM to_users u WHERE u.TEAM_ID = t1.ID AND u.USERTYPE = 'Player') AS TEAM_1_PLAYERS,
                (SELECT GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR ' / ') FROM to_users u WHERE u.TEAM_ID = t2.ID AND u.USERTYPE = 'Player') AS TEAM_2_PLAYERS
            FROM to_matches m
            INNER JOIN to_teams t1 ON t1.ID = m.TEAM_1_ID
            INNER JOIN to_teams t2 ON t2.ID = m.TEAM_2_ID
            WHERE m.TOURNAMENT_ID = :tournament_id
              AND m.STAGE = 'GROUP'
        ";
        $matchParams = [':tournament_id' => $tournamentId];
        if ($selectedGroup !== '') {
            $matchSql .= " AND m.GROUP_NAME = :group_name";
            $matchParams[':group_name'] = $selectedGroup;
        }
        $matchSql .= " ORDER BY m.GROUP_NAME, m.ROUND_NO, m.MATCH_ORDER, m.ID";
        $matchStmt = $pdo->prepare($matchSql);
        $matchStmt->execute($matchParams);
        $groupMatches = $matchStmt->fetchAll(PDO::FETCH_ASSOC);

        $standingSql = "
            SELECT s.*, t.NAME AS TEAM_NAME
            FROM to_standings s
            INNER JOIN to_teams t ON t.ID = s.TEAM_ID
            WHERE s.TOURNAMENT_ID = :tournament_id
              AND s.STAGE = 'GROUP'
        ";
        $standingParams = [':tournament_id' => $tournamentId];
        if ($selectedGroup !== '') {
            $standingSql .= " AND s.GROUP_NAME = :group_name";
            $standingParams[':group_name'] = $selectedGroup;
        }
        $standingSql .= " ORDER BY COALESCE(s.RANK_NO, 999), s.POINTS DESC, s.SCORE_DIFF DESC, t.NAME";
        $standingStmt = $pdo->prepare($standingSql);
        $standingStmt->execute($standingParams);
        $standings = $standingStmt->fetchAll(PDO::FETCH_ASSOC);
        $matrixTeams = $standings;

        foreach ($groupMatches as $match) {
            $keyA = (int)$match['TEAM_1_ID'] . ':' . (int)$match['TEAM_2_ID'];
            $keyB = (int)$match['TEAM_2_ID'] . ':' . (int)$match['TEAM_1_ID'];
            $value = $match['STATUS'] === 'COMPLETED'
                ? ((int)$match['TEAM_1_SCORE'] . '-' . (int)$match['TEAM_2_SCORE'])
                : '-';
            $matrixResults[$keyA] = $value;
            $matrixResults[$keyB] = $value;
        }

        $stageStmt = $pdo->prepare("
            SELECT
                m.*,
                t1.NAME AS TEAM_1_NAME,
                t2.NAME AS TEAM_2_NAME
            FROM to_matches m
            INNER JOIN to_teams t1 ON t1.ID = m.TEAM_1_ID
            INNER JOIN to_teams t2 ON t2.ID = m.TEAM_2_ID
            WHERE m.TOURNAMENT_ID = :tournament_id
              AND m.STAGE IN ('QUARTER_FINAL', 'SEMI_FINAL', 'FINAL')
            ORDER BY FIELD(m.STAGE, 'QUARTER_FINAL', 'SEMI_FINAL', 'FINAL'), m.MATCH_ORDER, m.ID
        ");
        $stageStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($stageStmt->fetchAll(PDO::FETCH_ASSOC) as $match) {
            $stageMatches[$match['STAGE']][] = $match;
        }

        if ($selectedMatchId > 0) {
            $logStmt = $pdo->prepare("
                SELECT
                    l.*,
                    scoringTeam.NAME AS SCORING_TEAM_NAME,
                    servingTeam.NAME AS SERVING_TEAM_NAME
                FROM to_match_rally_logs l
                LEFT JOIN to_teams scoringTeam ON scoringTeam.ID = l.SCORING_TEAM_ID
                LEFT JOIN to_teams servingTeam ON servingTeam.ID = l.SERVING_TEAM_ID
                WHERE l.MATCH_ID = :match_id
                  AND l.TOURNAMENT_ID = :tournament_id
                ORDER BY l.ID DESC
                LIMIT 100
            ");
            $logStmt->execute([
                ':match_id' => $selectedMatchId,
                ':tournament_id' => $tournamentId
            ]);
            $rallyLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>
<!-----Header------>
<?php include "includes/header.php"; ?>

<section class="tournament_page bottomSide_gap">
    <div class="cust_container">
        <div class="battleTournament_sec">

            <!-- PAGE TITLE -->
            <!-- <h2 class="title">The Player Hub → Casa Cup 2026 → Court Dashboard</h2> -->

            <!-- EVENT DETAILS -->
            <div class="card input-box">
                <div class="grid-4">
                    <div class="detail">Club Name: <span>Casa Badminton Club</span></div>
                    <div class="detail">Event Type: <span>Tournament</span></div>
                    <div class="detail">Tag Line: <span>Smash the Game</span></div>
                    <div class="detail">Date: <span>06/02/2026</span></div>
                    <div class="detail">Gender Category: <span>Men</span></div>
                    <div class="detail">Time: <span>9:00 AM</span></div>
                    <div class="detail">Event Category: <span>Doubles Open</span></div>
                    <div class="detail">Venue: <span>Casa Badminton Club</span></div>
                </div>
            </div>

            <!-- LEAGUE STAGE -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="section-title">League Stage</h4>
                    <select class="form-control w-auto" onchange="window.location.href='court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&group=' + encodeURIComponent(this.value)">
                        <?php if (empty($groups)): ?>
                            <option value="">No groups</option>
                        <?php else: ?>
                            <?php foreach ($groups as $groupName): ?>
                                <option value="<?php echo htmlspecialchars($groupName); ?>" <?php echo $selectedGroup === $groupName ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($groupName); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Round</th>
                                <th>Block</th>
                                <th>Court</th>
                                <th>Match ID</th>
                                <th>Team A</th>
                                <th>Team B</th>
                                <th>P1 & P2</th>
                                <th>Score A</th>
                                <th>Score B</th>
                                <th>Winner</th>
                                <th>Notes</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dbError): ?>
                                <tr><td colspan="14"><?php echo htmlspecialchars($dbError); ?></td></tr>
                            <?php elseif (empty($groupMatches)): ?>
                                <tr><td colspan="14">No league matches generated yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupMatches as $match): ?>
                                    <tr>
                                        <td><?php echo (int)($match['ROUND_NO'] ?? 1); ?></td>
                                        <td><?php echo htmlspecialchars($match['GROUP_NAME'] ?? '-'); ?></td>
                                        <td><?php echo !empty($match['COURT_ID']) ? 'C' . (int)$match['COURT_ID'] : '-'; ?></td>
                                        <td><?php echo (int)$match['ID']; ?></td>
                                        <td><?php echo courtDashboardTeamLabel($match, 'TEAM_1'); ?></td>
                                        <td><?php echo courtDashboardTeamLabel($match, 'TEAM_2'); ?></td>
                                        <td><?php echo htmlspecialchars(trim(($match['TEAM_1_PLAYERS'] ?? '-') . ' vs ' . ($match['TEAM_2_PLAYERS'] ?? '-'))); ?></td>
                                        <td><?php echo (int)($match['TEAM_1_SCORE'] ?? 0); ?></td>
                                        <td><?php echo (int)($match['TEAM_2_SCORE'] ?? 0); ?></td>
                                        <td><?php echo courtDashboardWinner($match); ?></td>
                                        <td><?php echo htmlspecialchars($match['STATUS'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($match['CREATED_AT'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($match['UPDATED_AT'] ?? '-'); ?></td>
                                        <td>
                                            <a href="badminton-scorer.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">Play</a>
                                            <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&group=<?php echo urlencode($match['GROUP_NAME'] ?? ''); ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LIVE RALLY LOG -->
            <div class="card">
                <h4 class="section-title">Live Match Log</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Rally</th>
                                <th>Set</th>
                                <th>Scoring Team</th>
                                <th>Serving Team</th>
                                <th>Score</th>
                                <th>Side</th>
                                <th>Event</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($selectedMatchId <= 0): ?>
                                <tr><td colspan="8">Select View on a match to see rally log.</td></tr>
                            <?php elseif (empty($rallyLogs)): ?>
                                <tr><td colspan="8">No rally log yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rallyLogs as $log): ?>
                                    <tr>
                                        <td><?php echo (int)$log['RALLY_NO']; ?></td>
                                        <td><?php echo (int)$log['SET_NO']; ?></td>
                                        <td><?php echo htmlspecialchars($log['SCORING_TEAM_NAME'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($log['SERVING_TEAM_NAME'] ?? '-'); ?></td>
                                        <td><?php echo (int)$log['TEAM_1_SCORE']; ?> - <?php echo (int)$log['TEAM_2_SCORE']; ?></td>
                                        <td><?php echo htmlspecialchars($log['COURT_SIDE'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($log['EVENT_TYPE'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($log['CREATED_AT'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LEAGUE POINT TABLE -->
            <div class="card">
                <h4 class="section-title">Points Table</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>MP</th>
                                <th>W</th>
                                <th>L</th>
                                <th>Pts</th>
                                <th>PF</th>
                                <th>PA</th>
                                <th>Notes</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($standings)): ?>
                                <tr><td colspan="9">No standings available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($standings as $standing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($standing['TEAM_NAME'] ?? '-'); ?></td>
                                        <td><?php echo (int)($standing['PLAYED'] ?? 0); ?></td>
                                        <td><?php echo (int)($standing['WON'] ?? 0); ?></td>
                                        <td><?php echo (int)($standing['LOST'] ?? 0); ?></td>
                                        <td><?php echo (int)($standing['POINTS'] ?? 0); ?></td>
                                        <td><?php echo (int)($standing['SCORE_FOR'] ?? 0); ?></td>
                                        <td><?php echo (int)($standing['SCORE_AGAINST'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($standing['GROUP_NAME'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars((string)($standing['RANK_NO'] ?? '-')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MATRIX TABLE -->
            <div class="card">
                <h4 class="section-title">Head to Head Matrix</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th></th>
                            <?php foreach ($matrixTeams as $team): ?>
                                <th><?php echo htmlspecialchars($team['TEAM_NAME'] ?? '-'); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <?php if (empty($matrixTeams)): ?>
                            <tr><td>No matrix available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($matrixTeams as $rowTeam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rowTeam['TEAM_NAME'] ?? '-'); ?></td>
                                    <?php foreach ($matrixTeams as $colTeam): ?>
                                        <?php
                                        $rowTeamId = (int)$rowTeam['TEAM_ID'];
                                        $colTeamId = (int)$colTeam['TEAM_ID'];
                                        $matrixValue = $rowTeamId === $colTeamId ? 'X' : ($matrixResults[$rowTeamId . ':' . $colTeamId] ?? '');
                                        ?>
                                        <td><?php echo htmlspecialchars($matrixValue); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- QUARTER FINAL -->
            <div class="card">
                <h4 class="section-title">Quarter Final</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Team A</th>
                                <th>Team B</th>
                                <th>Score A</th>
                                <th>Score B</th>
                                <th>Winner</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stageMatches['QUARTER_FINAL'])): ?>
                                <tr><td colspan="7">Quarter final will appear after all league matches are completed.</td></tr>
                            <?php else: ?>
                                <?php foreach ($stageMatches['QUARTER_FINAL'] as $match): ?>
                                    <tr>
                                        <td>QF<?php echo (int)$match['MATCH_ORDER']; ?></td>
                                        <td><?php echo htmlspecialchars($match['TEAM_1_NAME'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($match['TEAM_2_NAME'] ?? '-'); ?></td>
                                        <td><?php echo (int)($match['TEAM_1_SCORE'] ?? 0); ?></td>
                                        <td><?php echo (int)($match['TEAM_2_SCORE'] ?? 0); ?></td>
                                        <td><?php echo courtDashboardWinner($match); ?></td>
                                        <td>
                                            <a href="badminton-scorer.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">Play</a>
                                            <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SEMI FINAL -->
            <div class="card">
                <h4 class="section-title">Semi Final</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Score A</th>
                            <th>Score B</th>
                            <th>Winner</th>
                            <th>Action</th>
                        </tr>
                        <?php if (empty($stageMatches['SEMI_FINAL'])): ?>
                            <tr><td colspan="7">Semi final will appear after quarter finals are completed.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stageMatches['SEMI_FINAL'] as $match): ?>
                                <tr>
                                    <td>SF<?php echo (int)$match['MATCH_ORDER']; ?></td>
                                    <td><?php echo htmlspecialchars($match['TEAM_1_NAME'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($match['TEAM_2_NAME'] ?? '-'); ?></td>
                                    <td><?php echo (int)($match['TEAM_1_SCORE'] ?? 0); ?></td>
                                    <td><?php echo (int)($match['TEAM_2_SCORE'] ?? 0); ?></td>
                                    <td><?php echo courtDashboardWinner($match); ?></td>
                                    <td>
                                        <a href="badminton-scorer.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">Play</a>
                                        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- FINAL -->
            <div class="card winner">
                <h4 class="section-title">Championship Final</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Score A</th>
                            <th>Score B</th>
                            <th>Winner</th>
                            <th>Action</th>
                        </tr>
                        <?php if (empty($stageMatches['FINAL'])): ?>
                            <tr><td colspan="7">Final will appear after semi finals are completed.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stageMatches['FINAL'] as $match): ?>
                                <tr>
                                    <td>F<?php echo (int)$match['MATCH_ORDER']; ?></td>
                                    <td><?php echo htmlspecialchars($match['TEAM_1_NAME'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($match['TEAM_2_NAME'] ?? '-'); ?></td>
                                    <td><?php echo (int)($match['TEAM_1_SCORE'] ?? 0); ?></td>
                                    <td><?php echo (int)($match['TEAM_2_SCORE'] ?? 0); ?></td>
                                    <td><?php echo courtDashboardWinner($match); ?></td>
                                    <td>
                                        <a href="badminton-scorer.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">Play</a>
                                        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- BRONZE FINAL -->
            <div class="card">
                <h4 class="section-title">Bronze Final</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Winner</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <td>B1</td>
                            <td>L1</td>
                            <td>L2</td>
                            <td>-</td>
                            <td><a href="#" class="btn">Play</a></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>

<?php if ($selectedMatchId > 0): ?>
<script>
    setTimeout(function () {
        window.location.reload();
    }, 10000);
</script>
<?php endif; ?>

<!------footer------>
<?php include "includes/footer.php"; ?>
