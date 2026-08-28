<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedGroup = trim($_GET['group'] ?? '');
$selectedStage = strtoupper(trim($_GET['stage'] ?? 'GROUP'));
$selectedMatchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$dbError = '';
$tournamentHostId = 0;
$canManage = false;
// Role-based action buttons. App stores the role in $_SESSION['usertype']
// ('Host' | 'Trainer' | 'Player'); $_SESSION['role'] kept as a fallback.
$sessionRole = strtolower(trim((string)($_SESSION['usertype'] ?? $_SESSION['role'] ?? '')));
$isPlayerRole = ($sessionRole === 'player');
$isHostRole = ($sessionRole === 'host' || $sessionRole === 'trainer');
$groups = [];
$groupMatches = [];
$standings = [];
$matrixTeams = [];
$matrixResults = [];
$rallyLogs = [];
$tournamentSummary = null;
$directSemiFinal = false;

$stageOptions = [
    'GROUP' => 'League Stage',
    'QUARTER_FINAL' => 'Quarter Final',
    'SEMI_FINAL' => 'Semi Final',
    'FINAL' => 'Championship Final',
    'BRONZE_FINAL' => 'Bronze Final',
];
if (!isset($stageOptions[$selectedStage])) {
    $selectedStage = 'GROUP';
}

function courtDashboardPlayers(?string $players): array
{
    return array_values(array_filter(array_map('trim', explode('||', $players ?? ''))));
}

function courtDashboardPlayer(array $match, string $teamPrefix, int $position): string
{
    $players = courtDashboardPlayers($match[$teamPrefix . '_PLAYERS'] ?? '');
    return htmlspecialchars($players[$position] ?? '-');
}

function courtDashboardShortText($value, int $wordLimit = 8): string
{
    $plainText = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8')));
    if ($plainText === '') {
        return 'N/A';
    }

    $words = preg_split('/\s+/', $plainText);
    if ($words === false || count($words) <= $wordLimit) {
        return $plainText;
    }

    return implode(' ', array_slice($words, 0, $wordLimit)) . '...';
}

function courtDashboardDate($value): string
{
    if (empty($value) || $value === '0000-00-00') {
        return 'TBD';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d M Y', $timestamp) : (string)$value;
}

function courtDashboardTime($time): string
{
    if (empty($time)) {
        return 'TBD';
    }

    $timestamp = strtotime((string)$time);
    return $timestamp ? date('h:i A', $timestamp) : (string)$time;
}

function courtDashboardValue($value, string $fallback = 'N/A'): string
{
    $text = trim((string)($value ?? ''));
    return $text !== '' ? $text : $fallback;
}

function courtDashboardShortText($value, int $wordLimit = 8): string
{
    $plainText = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8')));
    if ($plainText === '') {
        return 'N/A';
    }

    $words = preg_split('/\s+/', $plainText);
    if ($words === false || count($words) <= $wordLimit) {
        return $plainText;
    }

    return implode(' ', array_slice($words, 0, $wordLimit)) . '...';
}

function courtDashboardDate($value): string
{
    if (empty($value) || $value === '0000-00-00') {
        return 'TBD';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d M Y', $timestamp) : (string)$value;
}

function courtDashboardTime($time): string
{
    if (empty($time)) {
        return 'TBD';
    }

    $timestamp = strtotime((string)$time);
    return $timestamp ? date('h:i A', $timestamp) : (string)$time;
}

function courtDashboardValue($value, string $fallback = 'N/A'): string
{
    $text = trim((string)($value ?? ''));
    return $text !== '' ? $text : $fallback;
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

function courtDashboardStageRow(array $match): void
{
    echo '<td>' . (int)($match['ROUND_NO'] ?? 1) . '</td>';
    echo '<td>' . htmlspecialchars($match['GROUP_NAME'] ?? '-') . '</td>';
    echo '<td>' . (!empty($match['COURT_ID']) ? (int)$match['COURT_ID'] : '-') . '</td>';
    echo '<td>' . (int)($match['ID'] ?? 0) . '</td>';
    echo '<td>' . courtDashboardTeamLabel($match, 'TEAM_1') . '</td>';
    echo '<td>' . courtDashboardTeamLabel($match, 'TEAM_2') . '</td>';
    echo '<td>' . (int)($match['TEAM_1_SCORE'] ?? 0) . '</td>';
    echo '<td>' . (int)($match['TEAM_2_SCORE'] ?? 0) . '</td>';
    echo '<td>' . courtDashboardWinner($match) . '</td>';
    echo '<td>' . htmlspecialchars($match['STATUS'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($match['CREATED_AT'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($match['UPDATED_AT'] ?? '-') . '</td>';
}

function courtDashboardStageEmptyNote(string $stage): string
{
    global $directSemiFinal;
    if ($stage === 'QUARTER_FINAL' && $directSemiFinal) {
        return 'Quarter Final skipped: four teams qualified from League Stage and advance directly to the Semi-Final draw.';
    }
    $notes = [
        'QUARTER_FINAL' => 'Spin the wheel from Tournament Parameter. Quarter final data will appear after all league matches are completed.',
        'SEMI_FINAL' => 'Spin the wheel from Tournament Parameter. Semi final data will appear after quarter finals are completed.',
        'FINAL' => 'Final data will appear after semi finals are completed.',
        'BRONZE_FINAL' => 'Final data will appear after semi finals are completed.',
    ];

    return $notes[$stage] ?? 'No league matches generated yet.';
}

function courtDashboardBuildStageStandings(array $matches): array
{
    $rows = [];
    foreach ($matches as $match) {
        foreach ([1, 2] as $teamNumber) {
            $teamId = (int)($match['TEAM_' . $teamNumber . '_ID'] ?? 0);
            if ($teamId <= 0) {
                continue;
            }
            if (!isset($rows[$teamId])) {
                $rows[$teamId] = [
                    'TEAM_NAME' => $match['TEAM_' . $teamNumber . '_NAME'] ?? '-',
                    'PLAYED' => 0, 'WON' => 0, 'LOST' => 0, 'POINTS' => 0,
                    'SCORE_FOR' => 0, 'SCORE_AGAINST' => 0, 'GROUP_NAME' => '-', 'RANK_NO' => '-'
                ];
            }
        }

        if (($match['STATUS'] ?? '') !== 'COMPLETED') {
            continue;
        }
        $team1Id = (int)$match['TEAM_1_ID'];
        $team2Id = (int)$match['TEAM_2_ID'];
        $score1 = (int)($match['TEAM_1_SCORE'] ?? 0);
        $score2 = (int)($match['TEAM_2_SCORE'] ?? 0);
        foreach ([[$team1Id, $score1, $score2], [$team2Id, $score2, $score1]] as [$teamId, $scoreFor, $scoreAgainst]) {
            $rows[$teamId]['PLAYED']++;
            $rows[$teamId]['SCORE_FOR'] += $scoreFor;
            $rows[$teamId]['SCORE_AGAINST'] += $scoreAgainst;
        }
        $winnerId = (int)($match['WINNER_TEAM_ID'] ?? 0);
        if (isset($rows[$winnerId])) {
            $rows[$winnerId]['WON']++;
            $rows[$winnerId]['POINTS'] += 2;
            $loserId = $winnerId === $team1Id ? $team2Id : $team1Id;
            if (isset($rows[$loserId])) {
                $rows[$loserId]['LOST']++;
            }
        }
    }

    uasort($rows, static function (array $a, array $b): int {
        return [$b['POINTS'], $b['SCORE_FOR'] - $b['SCORE_AGAINST'], $b['SCORE_FOR'], $a['TEAM_NAME']]
            <=> [$a['POINTS'], $a['SCORE_FOR'] - $a['SCORE_AGAINST'], $a['SCORE_FOR'], $b['TEAM_NAME']];
    });
    $rank = 1;
    foreach ($rows as &$row) {
        if ($row['PLAYED'] > 0) {
            $row['RANK_NO'] = $rank++;
        }
    }
    unset($row);
    return array_values($rows);
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
        $summaryStmt = $pdo->prepare("SELECT * FROM to_tournaments WHERE ID = :tournament_id LIMIT 1");
        $summaryStmt->execute([':tournament_id' => $tournamentId]);
        $tournamentSummary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $parameterStmt = $pdo->prepare("SELECT GROUP_COUNT, ADVANCERS_PER_GROUP FROM to_tournament_parameters WHERE TOURNAMENT_ID = :tournament_id LIMIT 1");
        $parameterStmt->execute([':tournament_id' => $tournamentId]);
        $parameters = $parameterStmt->fetch(PDO::FETCH_ASSOC);
        if ($parameters) {
            $directSemiFinal = ((int)$parameters['GROUP_COUNT'] * (int)$parameters['ADVANCERS_PER_GROUP']) === 4;
        }
    }

    // Only the organizer (to_tournaments.HOST_ID == logged-in ca_users.ID) can play/manage matches.
    $tournamentHostId = (int)($tournamentSummary['HOST_ID'] ?? 0);
    $canManage = !empty($_SESSION['user_id'])
        && $tournamentHostId > 0
        && (int)$_SESSION['user_id'] === $tournamentHostId;

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
        if ($selectedStage === 'GROUP' && $selectedGroup === '' && !empty($groups)) {
            $selectedGroup = (string)$groups[0];
        }

        $matchSql = "
            SELECT
                m.*,
                t1.NAME AS TEAM_1_NAME,
                t2.NAME AS TEAM_2_NAME,
                (SELECT GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR '||') FROM to_users u WHERE u.TEAM_ID = t1.ID AND u.USERTYPE = 'Player') AS TEAM_1_PLAYERS,
                (SELECT GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR '||') FROM to_users u WHERE u.TEAM_ID = t2.ID AND u.USERTYPE = 'Player') AS TEAM_2_PLAYERS
            FROM to_matches m
            INNER JOIN to_teams t1 ON t1.ID = m.TEAM_1_ID
            INNER JOIN to_teams t2 ON t2.ID = m.TEAM_2_ID
            WHERE m.TOURNAMENT_ID = :tournament_id
              AND m.STAGE = :stage
        ";
        $matchParams = [':tournament_id' => $tournamentId, ':stage' => $selectedStage];
        if ($selectedStage === 'GROUP' && $selectedGroup !== '') {
            $matchSql .= " AND m.GROUP_NAME = :group_name";
            $matchParams[':group_name'] = $selectedGroup;
        }
        $matchSql .= " ORDER BY m.GROUP_NAME, m.ROUND_NO, m.MATCH_ORDER, m.ID";
        $matchStmt = $pdo->prepare($matchSql);
        $matchStmt->execute($matchParams);
        $groupMatches = $matchStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($selectedStage === 'GROUP') {
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
        } else {
            $standings = courtDashboardBuildStageStandings($groupMatches);
        }

        foreach ($groupMatches as $match) {
            $keyA = (int)$match['TEAM_1_ID'] . ':' . (int)$match['TEAM_2_ID'];
            $keyB = (int)$match['TEAM_2_ID'] . ':' . (int)$match['TEAM_1_ID'];
            $value = $match['STATUS'] === 'COMPLETED'
                ? ((int)$match['TEAM_1_SCORE'] . '-' . (int)$match['TEAM_2_SCORE'])
                : '-';
            $matrixResults[$keyA] = $value;
            $matrixResults[$keyB] = $value;
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

// Action-column visibility (role-based):
//   Host/Trainer (or the tournament organizer) -> "Play" only.
//   Player / any other viewer                  -> "View" only.
$showPlayAction = !$isPlayerRole && ($isHostRole || $canManage);

$summaryClubName = 'N/A';
$summaryEventType = 'N/A';
$summaryTagline = 'N/A';
$summaryDate = 'TBD';
$summaryGender = 'N/A';
$summaryTime = 'TBD';
$summaryCategory = 'N/A';
$summaryVenue = 'N/A';

if (is_array($tournamentSummary)) {
    $summaryClubName = trim((string)($tournamentSummary['CUP_NAME'] ?? ''));
    if ($summaryClubName === '') {
        $summaryClubName = trim((string)($tournamentSummary['HOST_NAME'] ?? ''));
    }
    if ($summaryClubName === '') {
        $summaryClubName = 'N/A';
    }

    $summaryEventType = courtDashboardValue($tournamentSummary['EVENT_TYPE'] ?? '');
    $summaryTagline = courtDashboardShortText($tournamentSummary['EVENT_DESCRIPTION'] ?? '');
    $summaryDate = courtDashboardDate($tournamentSummary['EVENT_DATE'] ?? '');
    $summaryGender = courtDashboardValue(str_replace("'s", '', (string)($tournamentSummary['GENDER_CATEGORY'] ?? '')));
    $summaryTime = courtDashboardTime($tournamentSummary['EVENT_TIME'] ?? '');
    $summaryCategory = courtDashboardValue($tournamentSummary['EVENT_CATEGORY'] ?? '');

    $summaryVenue = trim((string)($tournamentSummary['EVENT_VENUE'] ?? ''));
    if ($summaryVenue === '') {
        $summaryVenueParts = array_filter([
            trim((string)($tournamentSummary['EVENT_CITY'] ?? '')),
            trim((string)($tournamentSummary['EVENT_COUNTRY'] ?? ''))
        ]);
        $summaryVenue = !empty($summaryVenueParts) ? implode(', ', $summaryVenueParts) : 'N/A';
    }
}
?>
<!-----Header------>
<?php include "includes/header.php"; ?>

<!-- Offset the dashboard tables below the fixed header when reached via a dashboard link -->
<style>
    #league-stage {
        scroll-margin-top: 100px;
    }
</style>

<section class="tournament_page bottomSide_gap">
    <div class="cust_container">
        <div class="battleTournament_sec">

            <!-- PAGE TITLE -->
            <!-- <h2 class="title">The Player Hub → Casa Cup 2026 → Court Dashboard</h2> -->

            <!-- EVENT DETAILS -->
            <div class="card input-box">
                <div class="grid-4">
                    <div class="detail">Club Name: <span><?php echo htmlspecialchars($summaryClubName); ?></span></div>
                    <div class="detail">Event Type: <span><?php echo htmlspecialchars($summaryEventType); ?></span></div>
                    <div class="detail">Tag Line: <span><?php echo htmlspecialchars($summaryTagline); ?></span></div>
                    <div class="detail">Date: <span><?php echo htmlspecialchars($summaryDate); ?></span></div>
                    <div class="detail">Gender Category: <span><?php echo htmlspecialchars($summaryGender); ?></span></div>
                    <div class="detail">Time: <span><?php echo htmlspecialchars($summaryTime); ?></span></div>
                    <div class="detail">Event Category: <span><?php echo htmlspecialchars($summaryCategory); ?></span></div>
                    <div class="detail">Venue: <span><?php echo htmlspecialchars($summaryVenue); ?></span></div>
                </div>
            </div>

            <!-- LEAGUE STAGE -->
            <div class="card" id="league-stage">
                <div class="d-flex justify-content-between align-items-center">
                <h4 class="section-title">The Match Ledger</h4>
                    <select class="form-control w-auto" onchange="window.location.href='court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&stage=' + encodeURIComponent(this.options[this.selectedIndex].dataset.stage) + '&group=' + encodeURIComponent(this.options[this.selectedIndex].dataset.group || '')">
                        <?php if (empty($groups)): ?>
                            <option data-stage="GROUP" data-group="" <?php echo $selectedStage === 'GROUP' ? 'selected' : ''; ?>>No groups</option>
                        <?php endif; ?>
                        <?php foreach ($groups as $groupName): ?>
                            <option data-stage="GROUP" data-group="<?php echo htmlspecialchars($groupName); ?>" <?php echo $selectedStage === 'GROUP' && $selectedGroup === $groupName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($groupName); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php foreach ($stageOptions as $stageKey => $stageLabel): ?>
                            <?php if ($stageKey === 'GROUP') continue; ?>
                            <option data-stage="<?php echo htmlspecialchars($stageKey); ?>" data-group="" <?php echo $selectedStage === $stageKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stageLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Round</th>
                                <th>Block</th>
                                <th>Court</th>
                                <th>Match ID</th>
                                <th>Team A</th>
                                <th>Player 1</th>
                                <th>Player 2</th>
                                <th>Score A</th>
                                <th>Team B</th>
                                <th>Player 1</th>
                                <th>Player 2</th>
                                <th>Score B</th>
                                <th>Winner</th>
                                <th>Notes</th>
                                <th>Start Timestamp</th>
                                <th>End Timestamp</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dbError): ?>
                                <tr><td colspan="17"><?php echo htmlspecialchars($dbError); ?></td></tr>
                            <?php elseif (empty($groupMatches)): ?>
                                <tr><td colspan="17"><?php echo htmlspecialchars(courtDashboardStageEmptyNote($selectedStage)); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($groupMatches as $matchIndex => $match): ?>
                                    <tr>
                                        <td><?php echo $matchIndex + 1; ?></td>
                                        <td><?php echo (int)($match['ROUND_NO'] ?? 1); ?></td>
                                        <td><?php echo htmlspecialchars($match['GROUP_NAME'] ?? '-'); ?></td>
                                        <td><?php echo !empty($match['COURT_ID']) ? (int)$match['COURT_ID'] : '-'; ?></td>
                                        <td><?php echo (int)$match['ID']; ?></td>
                                        <td><?php echo courtDashboardTeamLabel($match, 'TEAM_1'); ?></td>
                                        <td><?php echo courtDashboardPlayer($match, 'TEAM_1', 0); ?></td>
                                        <td><?php echo courtDashboardPlayer($match, 'TEAM_1', 1); ?></td>
                                        <td><?php echo (int)($match['TEAM_1_SCORE'] ?? 0); ?></td>
                                        <td><?php echo courtDashboardTeamLabel($match, 'TEAM_2'); ?></td>
                                        <td><?php echo courtDashboardPlayer($match, 'TEAM_2', 0); ?></td>
                                        <td><?php echo courtDashboardPlayer($match, 'TEAM_2', 1); ?></td>
                                        <td><?php echo (int)($match['TEAM_2_SCORE'] ?? 0); ?></td>
                                        <td><?php echo courtDashboardWinner($match); ?></td>
                                        <td><?php echo htmlspecialchars($match['STATUS'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($match['CREATED_AT'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($match['UPDATED_AT'] ?? '-'); ?></td>
                                        <td><a href="badminton-scorer.php?id=<?php echo (int)$tournamentId; ?>&match_id=<?php echo (int)$match['ID']; ?>" class="btn"><?php echo $showPlayAction ? 'Play' : 'View'; ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LEAGUE POINT TABLE -->
            <div class="card">
                <h4 class="section-title">The Standings</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Team</th>
                                <th>Matches</th>
                                <th>Wins</th>
                                <th>Losses</th>
                                <th>Points</th>
                                <th>Points For</th>
                                <th>Points Against</th>
                                <th>Notes</th>
                                <th>Ranking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($standings)): ?>
                                <tr><td colspan="10"><?php echo htmlspecialchars($selectedStage === 'GROUP' ? 'No standings available.' : courtDashboardStageEmptyNote($selectedStage)); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($standings as $standingIndex => $standing): ?>
                                    <tr>
                                        <td><?php echo $standingIndex + 1; ?></td>
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

            <?php if ($selectedStage === 'GROUP'): ?>
            <!-- MATRIX TABLE -->
            <div class="card">
                <h4 class="section-title">The Rivalry Analytics</h4>
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
            <?php endif; ?>

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
