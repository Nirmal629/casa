<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$groupLabels = ['Group-A', 'Group-B', 'Group-C', 'Group-D'];
$maxGroups = 4;
$requestedGroups = isset($_REQUEST['groups_required']) ? (int)$_REQUEST['groups_required'] : null;
$groupsRequired = $requestedGroups !== null ? max(1, min($maxGroups, $requestedGroups)) : 4;
$allowedGroups = array_slice($groupLabels, 0, $groupsRequired);
$teamRows = [];
$teamCount = 0;
$teamsPerGroup = 0;
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dbError = '';
$groupColumn = 'GROUP_NAME';
$leagueGroups = [];
$tournamentSummary = null;
$quarterFinalRows = [];
$semiFinalRows = [];
$finalRows = [];
$bronzeFinalRows = [];
$canManage = false;
$courtAssignments = [];
$matchRuleDefaults = [
    'GROUP' => ['sets' => 1, 'deuce' => false, 'deuceLimit' => null],
    'QUARTER_FINAL' => ['sets' => 3, 'deuce' => true, 'deuceLimit' => 25],
    'SEMI_FINAL' => ['sets' => 3, 'deuce' => true, 'deuceLimit' => 30],
    'FINAL' => ['sets' => 3, 'deuce' => true, 'deuceLimit' => 30],
    'BRONZE_FINAL' => ['sets' => 3, 'deuce' => true, 'deuceLimit' => 30],
];
$matchRuleConfigs = $matchRuleDefaults;

function battleTournamentShortText($value, int $wordLimit = 8): string
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

function battleTournamentCategory(array $tournament): string
{
    $category = trim((string)($tournament['EVENT_CATEGORY'] ?? ''));
    $gender = trim(str_replace("'s", '', (string)($tournament['GENDER_CATEGORY'] ?? '')));
    $type = trim((string)($tournament['EVENT_TYPE'] ?? ''));
    $parts = array_values(array_filter([$category, $gender, $type], static function ($value) {
        return $value !== '';
    }));

    return !empty($parts) ? implode(' - ', $parts) : 'N/A';
}

function battleTournamentDate($value): string
{
    if (empty($value) || $value === '0000-00-00') {
        return 'TBD';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d M Y', $timestamp) : (string)$value;
}

function battleTournamentTime($time): string
{
    if (empty($time)) {
        return 'TBD';
    }

    $timestamp = strtotime((string)$time);
    return $timestamp ? date('h:i A', $timestamp) : (string)$time;
}

function battleTournamentEsc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function battleTournamentCanManage(?array $tournament): bool
{
    if (empty($_SESSION['user_id']) || !is_array($tournament)) {
        return false;
    }

    // Only the organizer (to_tournaments.HOST_ID == logged-in ca_users.ID) can manage this tournament.
    $tournamentHostId = (int)($tournament['HOST_ID'] ?? 0);
    return $tournamentHostId > 0
        && (int)$_SESSION['user_id'] === $tournamentHostId;
}

function applyTournamentCourtAssignment(PDO $pdo, int $tournamentId, string $stage, ?string $groupName, array $courts): void
{
    $courts = array_values(array_unique(array_filter(array_map('intval', $courts), static function ($court) {
        return $court > 0;
    })));
    if ($tournamentId <= 0 || empty($courts)) {
        return;
    }

    $matchSql = "SELECT ID FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = :stage";
    $params = [':tournament_id' => $tournamentId, ':stage' => $stage];
    if ($groupName !== null) {
        $matchSql .= " AND GROUP_NAME = :group_name";
        $params[':group_name'] = $groupName;
    }
    $matchSql .= " ORDER BY ROUND_NO, MATCH_ORDER, ID";
    $matchStmt = $pdo->prepare($matchSql);
    $matchStmt->execute($params);
    $matchIds = $matchStmt->fetchAll(PDO::FETCH_COLUMN);

    $updateStmt = $pdo->prepare("UPDATE to_matches SET COURT_ID = :court_id WHERE ID = :match_id AND TOURNAMENT_ID = :tournament_id");
    foreach ($matchIds as $index => $matchId) {
        $updateStmt->execute([
            ':court_id' => $courts[$index % count($courts)],
            ':match_id' => (int)$matchId,
            ':tournament_id' => $tournamentId,
        ]);
    }
}

function syncGroupFixturesAndStandings(PDO $pdo, int $tournamentId, string $groupColumn, array $allowedGroups): void
{
    if ($tournamentId <= 0) {
        return;
    }

    $teamStmt = $pdo->prepare("
        SELECT ID, `$groupColumn` AS GROUP_NAME
        FROM to_teams
        WHERE TOURNAMENT_ID = :tournament_id
          AND `$groupColumn` IS NOT NULL
          AND `$groupColumn` <> ''
        ORDER BY `$groupColumn`, ID
    ");
    $teamStmt->execute([':tournament_id' => $tournamentId]);
    $teamsByGroup = [];
    foreach ($teamStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $groupName = (string)$row['GROUP_NAME'];
        if (!in_array($groupName, $allowedGroups, true)) {
            continue;
        }
        $teamId = (int)$row['ID'];
        $teamsByGroup[$groupName][] = $teamId;
    }

    $pdo->prepare("DELETE FROM to_matches WHERE TOURNAMENT_ID = ? AND STAGE = 'GROUP' AND STATUS = 'PENDING'")
        ->execute([$tournamentId]);
    $pdo->prepare("DELETE FROM to_standings WHERE TOURNAMENT_ID = ? AND STAGE = 'GROUP'")
        ->execute([$tournamentId]);

    $standingInsert = $pdo->prepare("
        INSERT INTO to_standings
            (TOURNAMENT_ID, STAGE, GROUP_NAME, TEAM_ID, PLAYED, WON, LOST, POINTS, SCORE_FOR, SCORE_AGAINST, SCORE_DIFF, RANK_NO)
        VALUES
            (:tournament_id, 'GROUP', :group_name, :team_id, 0, 0, 0, 0, 0, 0, 0, 0)
    ");
    $matchInsert = $pdo->prepare("
        INSERT INTO to_matches
            (TOURNAMENT_ID, STAGE, GROUP_NAME, COURT_ID, ROUND_NO, MATCH_ORDER, TEAM_1_ID, TEAM_2_ID, TEAM_1_SCORE, TEAM_2_SCORE, STATUS)
        VALUES
            (:tournament_id, 'GROUP', :group_name, NULL, :round_no, :match_order, :team_1_id, :team_2_id, 0, 0, 'PENDING')
    ");

    foreach ($teamsByGroup as $groupName => $teamIds) {
        foreach ($teamIds as $rankIndex => $teamId) {
            $standingInsert->execute([
                ':tournament_id' => $tournamentId,
                ':group_name' => $groupName,
                ':team_id' => $teamId
            ]);
        }
    }

    foreach ($teamsByGroup as $groupName => $teamIds) {
        $matchOrder = 1;
        $teamTotal = count($teamIds);
        for ($i = 0; $i < $teamTotal; $i++) {
            for ($j = $i + 1; $j < $teamTotal; $j++) {
                $matchInsert->execute([
                    ':tournament_id' => $tournamentId,
                    ':group_name' => $groupName,
                    ':round_no' => $matchOrder,
                    ':match_order' => $matchOrder,
                    ':team_1_id' => $teamIds[$i],
                    ':team_2_id' => $teamIds[$j]
                ]);
                $matchOrder++;
            }
        }
    }

    $courtAssignmentStmt = $pdo->prepare("SELECT COURTS FROM to_tournament_court_assignments
        WHERE TOURNAMENT_ID = :tournament_id AND CATEGORY_KEY = :category_key LIMIT 1");
    foreach (array_keys($teamsByGroup) as $groupName) {
        $courtAssignmentStmt->execute([
            ':tournament_id' => $tournamentId,
            ':category_key' => 'GROUP:' . $groupName,
        ]);
        $courts = $courtAssignmentStmt->fetchColumn();
        if ($courts !== false) {
            applyTournamentCourtAssignment($pdo, $tournamentId, 'GROUP', $groupName, explode(',', (string)$courts));
        }
    }
}

function inferGroupsRequiredFromTeams(PDO $pdo, int $tournamentId, string $groupColumn, array $groupLabels, int $maxGroups): int
{
    if ($tournamentId <= 0) {
        return $maxGroups;
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT `$groupColumn` AS GROUP_NAME
        FROM to_teams
        WHERE TOURNAMENT_ID = :tournament_id
          AND `$groupColumn` IS NOT NULL
          AND `$groupColumn` <> ''
    ");
    $stmt->execute([':tournament_id' => $tournamentId]);

    $highestGroupIndex = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $groupName) {
        $index = array_search((string)$groupName, $groupLabels, true);
        if ($index !== false) {
            $highestGroupIndex = max($highestGroupIndex, $index + 1);
        }
    }

    return $highestGroupIndex > 0 ? min($maxGroups, $highestGroupIndex) : $maxGroups;
}

try {
    include_once __DIR__ . '/../dbConnection_PDO.php';
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $columns = $pdo->query("SHOW COLUMNS FROM to_teams")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['GROUP_NAME', 'GROUPS', 'GROUP'] as $candidateColumn) {
        if (in_array($candidateColumn, $columns, true)) {
            $groupColumn = $candidateColumn;
            break;
        }
    }

    if (!in_array($groupColumn, $columns, true)) {
        $pdo->exec("ALTER TABLE to_teams ADD COLUMN GROUP_NAME VARCHAR(20) NULL DEFAULT NULL");
        $columns[] = 'GROUP_NAME';
        $groupColumn = 'GROUP_NAME';
    }

    if ($tournamentId <= 0) {
        $latestTournament = $pdo->query("SELECT ID FROM to_tournaments ORDER BY ID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $tournamentId = (int)($latestTournament['ID'] ?? 0);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS to_tournament_match_rules (
        ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
        TOURNAMENT_ID INT NOT NULL,
        STAGE VARCHAR(30) NOT NULL,
        GAME_SETS TINYINT NOT NULL DEFAULT 3,
        DEUCE_ENABLED TINYINT(1) NOT NULL DEFAULT 1,
        DEUCE_LIMIT TINYINT NULL,
        UPDATED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        UNIQUE KEY tournament_stage_rule (TOURNAMENT_ID, STAGE)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS to_tournament_court_assignments (
        ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
        TOURNAMENT_ID INT NOT NULL,
        CATEGORY_KEY VARCHAR(80) NOT NULL,
        STAGE VARCHAR(30) NOT NULL,
        GROUP_NAME VARCHAR(30) NULL,
        COURTS VARCHAR(120) NOT NULL,
        UPDATED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        UNIQUE KEY tournament_court_category (TOURNAMENT_ID, CATEGORY_KEY)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");




    if ($tournamentId > 0) {
        $summaryStmt = $pdo->prepare("
            SELECT
                e.*,
                (SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = e.ID) AS joined_count
            FROM to_tournaments e
            WHERE e.ID = :tournament_id
            LIMIT 1
        ");
        $summaryStmt->execute([':tournament_id' => $tournamentId]);
        $tournamentSummary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $canManage = battleTournamentCanManage($tournamentSummary);

    if (($_POST['action'] ?? '') === 'save_match_rules') {
        header('Content-Type: application/json');

        if (!$canManage || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can update match rules.']);
            exit;
        }

        $postedRules = json_decode((string)($_POST['rules'] ?? ''), true);
        if (!is_array($postedRules)) {
            echo json_encode(['success' => false, 'message' => 'Invalid match rules.']);
            exit;
        }

        $saveRulesStmt = $pdo->prepare("INSERT INTO to_tournament_match_rules
            (TOURNAMENT_ID, STAGE, GAME_SETS, DEUCE_ENABLED, DEUCE_LIMIT)
            VALUES (:tournament_id, :stage, :game_sets, :deuce_enabled, :deuce_limit)
            ON DUPLICATE KEY UPDATE
                GAME_SETS = VALUES(GAME_SETS),
                DEUCE_ENABLED = VALUES(DEUCE_ENABLED),
                DEUCE_LIMIT = VALUES(DEUCE_LIMIT)");

        foreach ($matchRuleDefaults as $stage => $defaults) {
            $rules = is_array($postedRules[$stage] ?? null) ? $postedRules[$stage] : [];
            $gameSets = (int)($rules['sets'] ?? $defaults['sets']) === 1 ? 1 : 3;
            $deuceEnabled = !empty($rules['deuce']);
            $deuceLimit = $deuceEnabled && in_array((int)($rules['deuceLimit'] ?? 0), [25, 30], true)
                ? (int)$rules['deuceLimit']
                : null;

            $saveRulesStmt->execute([
                ':tournament_id' => $tournamentId,
                ':stage' => $stage,
                ':game_sets' => $gameSets,
                ':deuce_enabled' => $deuceEnabled ? 1 : 0,
                ':deuce_limit' => $deuceLimit,
            ]);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if (($_POST['action'] ?? '') === 'save_court_assignment') {
        header('Content-Type: application/json');

        if (!$canManage || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can assign courts.']);
            exit;
        }

        $stage = strtoupper(trim((string)($_POST['stage'] ?? '')));
        $groupName = trim((string)($_POST['group_name'] ?? ''));
        $allowedStages = ['GROUP', 'QUARTER_FINAL', 'SEMI_FINAL', 'FINAL', 'BRONZE_FINAL'];
        $courts = json_decode((string)($_POST['courts'] ?? '[]'), true);
        $courts = is_array($courts) ? array_values(array_unique(array_filter(array_map('intval', $courts), static function ($court) {
            return $court > 0;
        }))) : [];

        if (!in_array($stage, $allowedStages, true) || ($stage === 'GROUP' && !in_array($groupName, $allowedGroups, true)) || ($stage !== 'GROUP' && $groupName !== '') || empty($courts)) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid category and at least one court.']);
            exit;
        }

        $categoryKey = $stage === 'GROUP' ? 'GROUP:' . $groupName : $stage;
        $saveCourtStmt = $pdo->prepare("INSERT INTO to_tournament_court_assignments
            (TOURNAMENT_ID, CATEGORY_KEY, STAGE, GROUP_NAME, COURTS)
            VALUES (:tournament_id, :category_key, :stage, :group_name, :courts)
            ON DUPLICATE KEY UPDATE COURTS = VALUES(COURTS), STAGE = VALUES(STAGE), GROUP_NAME = VALUES(GROUP_NAME)");
        $saveCourtStmt->execute([
            ':tournament_id' => $tournamentId,
            ':category_key' => $categoryKey,
            ':stage' => $stage,
            ':group_name' => $stage === 'GROUP' ? $groupName : null,
            ':courts' => implode(',', $courts),
        ]);
        applyTournamentCourtAssignment($pdo, $tournamentId, $stage, $stage === 'GROUP' ? $groupName : null, $courts);

        echo json_encode(['success' => true, 'category_key' => $categoryKey, 'courts' => $courts]);
        exit;
    }

    if ($tournamentId > 0) {
        $rulesStmt = $pdo->prepare("SELECT STAGE, GAME_SETS, DEUCE_ENABLED, DEUCE_LIMIT
            FROM to_tournament_match_rules WHERE TOURNAMENT_ID = :tournament_id");
        $rulesStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($rulesStmt->fetchAll(PDO::FETCH_ASSOC) as $storedRule) {
            $stage = (string)$storedRule['STAGE'];
            if (!isset($matchRuleDefaults[$stage])) {
                continue;
            }
            $deuceEnabled = (int)$storedRule['DEUCE_ENABLED'] === 1;
            $matchRuleConfigs[$stage] = [
                'sets' => (int)$storedRule['GAME_SETS'] === 1 ? 1 : 3,
                'deuce' => $deuceEnabled,
                'deuceLimit' => $deuceEnabled && in_array((int)$storedRule['DEUCE_LIMIT'], [25, 30], true)
                    ? (int)$storedRule['DEUCE_LIMIT']
                    : null,
            ];
        }

        $courtAssignmentStmt = $pdo->prepare("SELECT CATEGORY_KEY, STAGE, GROUP_NAME, COURTS
            FROM to_tournament_court_assignments WHERE TOURNAMENT_ID = :tournament_id ORDER BY CATEGORY_KEY");
        $courtAssignmentStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($courtAssignmentStmt->fetchAll(PDO::FETCH_ASSOC) as $courtAssignment) {
            $courtAssignments[(string)$courtAssignment['CATEGORY_KEY']] = [
                'stage' => (string)$courtAssignment['STAGE'],
                'groupName' => (string)($courtAssignment['GROUP_NAME'] ?? ''),
                'courts' => array_values(array_filter(array_map('intval', explode(',', (string)$courtAssignment['COURTS'])))),
            ];
        }
    }

    if ($requestedGroups === null) {
        $groupsRequired = inferGroupsRequiredFromTeams($pdo, $tournamentId, $groupColumn, $groupLabels, $maxGroups);
        $allowedGroups = array_slice($groupLabels, 0, $groupsRequired);
    }

    if ($tournamentId > 0) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = :tournament_id");
        $countStmt->execute([':tournament_id' => $tournamentId]);
        $teamCount = (int)$countStmt->fetchColumn();
        $teamsPerGroup = $groupsRequired > 0 ? (int)ceil($teamCount / $groupsRequired) : 0;
    }

    if (($_POST['action'] ?? '') === 'update_team_group') {
        header('Content-Type: application/json');

        if (!$canManage) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can assign groups.']);
            exit;
        }

        $teamId = (int)($_POST['team_id'] ?? 0);
        $selectedGroup = trim($_POST['group_name'] ?? '');

        if ($teamId <= 0 || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid team or tournament.']);
            exit;
        }

        if ($selectedGroup !== '' && !in_array($selectedGroup, $allowedGroups, true)) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid group.']);
            exit;
        }

        if ($selectedGroup !== '' && $teamsPerGroup > 0) {
            $capacityStmt = $pdo->prepare("SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = :tournament_id AND `$groupColumn` = :group_name AND ID <> :team_id");
            $capacityStmt->execute([
                ':tournament_id' => $tournamentId,
                ':group_name' => $selectedGroup,
                ':team_id' => $teamId
            ]);

            if ((int)$capacityStmt->fetchColumn() >= $teamsPerGroup) {
                echo json_encode(['success' => false, 'message' => $selectedGroup . ' already has the maximum ' . $teamsPerGroup . ' teams.']);
                exit;
            }
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE to_teams SET `$groupColumn` = :group_name WHERE ID = :team_id AND TOURNAMENT_ID = :tournament_id");
            $stmt->execute([
                ':group_name' => $selectedGroup !== '' ? $selectedGroup : null,
                ':team_id' => $teamId,
                ':tournament_id' => $tournamentId
            ]);
            syncGroupFixturesAndStandings($pdo, $tournamentId, $groupColumn, $allowedGroups);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => true, 'group_name' => $selectedGroup]);
        exit;
    }

    if ($tournamentId > 0) {
        $stmt = $pdo->prepare("
            SELECT
                t.ID,
                t.NAME AS TEAM_NAME,
                t.`$groupColumn` AS GROUP_NAME,
                GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR '||') AS PLAYERS
            FROM to_teams t
            LEFT JOIN to_users u ON u.TEAM_ID = t.ID AND u.USERTYPE = 'Player'
            WHERE t.TOURNAMENT_ID = :tournament_id
            GROUP BY t.ID, t.NAME, t.`$groupColumn`
            ORDER BY t.ID ASC
        ");
        $stmt->execute([':tournament_id' => $tournamentId]);
        $teamRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $leagueStmt = $pdo->prepare("
            SELECT
                s.GROUP_NAME,
                s.TEAM_ID,
                s.PLAYED,
                s.WON,
                s.LOST,
                s.POINTS,
                s.SCORE_FOR,
                s.SCORE_AGAINST,
                s.SCORE_DIFF,
                s.RANK_NO,
                t.NAME AS TEAM_NAME,
                GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR '||') AS PLAYERS
            FROM to_standings s
            INNER JOIN to_teams t ON t.ID = s.TEAM_ID
            LEFT JOIN to_users u ON u.TEAM_ID = t.ID AND u.USERTYPE = 'Player'
            WHERE s.TOURNAMENT_ID = :tournament_id
              AND s.STAGE = 'GROUP'
            GROUP BY s.ID, s.GROUP_NAME, s.TEAM_ID, s.PLAYED, s.WON, s.LOST, s.POINTS, s.SCORE_FOR, s.SCORE_AGAINST, s.SCORE_DIFF, s.RANK_NO, t.NAME
            ORDER BY s.GROUP_NAME, COALESCE(s.RANK_NO, 999), s.POINTS DESC, s.SCORE_DIFF DESC, t.NAME
        ");
        $leagueStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($leagueStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $leagueGroups[$row['GROUP_NAME']][] = $row;
        }

    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$summaryClubName = 'N/A';
$summaryTagline = 'N/A';
$summaryCategory = 'N/A';
$summaryDate = 'TBD';
$summaryTime = 'TBD';
$summaryVenue = 'N/A';

if (is_array($tournamentSummary)) {
    $summaryClubName = trim((string)($tournamentSummary['CUP_NAME'] ?? ''));
    if ($summaryClubName === '') {
        $summaryClubName = trim((string)($tournamentSummary['HOST_NAME'] ?? ''));
    }
    if ($summaryClubName === '') {
        $summaryClubName = 'N/A';
    }

    $summaryTagline = battleTournamentShortText($tournamentSummary['EVENT_DESCRIPTION'] ?? '');
    $summaryCategory = battleTournamentCategory($tournamentSummary);
    $summaryDate = battleTournamentDate($tournamentSummary['EVENT_DATE'] ?? '');
    $summaryTime = battleTournamentTime($tournamentSummary['EVENT_TIME'] ?? '');

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
<div class="battleTournament_sec">
    <!-- <h2 class="title">Winter - Mini Casa Tournament 2025</h2> -->

    <!-- INPUT SUMMARY -->
    <div class="input-box">
        <div class="detail">Club Name: <span><?php echo battleTournamentEsc($summaryClubName); ?></span></div>
        <div class="detail">Tag Line: <span><?php echo battleTournamentEsc($summaryTagline); ?></span></div>
        <div class="detail">Category: <span><?php echo battleTournamentEsc($summaryCategory); ?></span></div>
        <div class="detail">Venue: <span><?php echo battleTournamentEsc($summaryVenue); ?></span></div>
        <div class="detail">Date: <span><?php echo battleTournamentEsc($summaryDate); ?></span></div>
        <div class="detail">Time: <span><?php echo battleTournamentEsc($summaryTime); ?></span></div>
    </div>


    <div class="grid-3">
        <div class="">
            <div class="card parameters-box">
                <div class="collapse-header" onclick="toggleCollapse()">
                    <h4 class="section-title">Tournament Parameter</h4>
                    <span>▼</span>
                </div>
                <div class="collapse-body" id="collapseContent">
                    <!-- Numeric Inputs -->
                    <div class="grid">
                        <div class="field small">
                            <label>Number of teams registered</label>
                            <input type="number" value="<?php echo (int)$teamCount; ?>" readonly>
                        </div>

                        <div class="field small">
                            <label>Number of Groups Required <span>(Max 4)</span></label>
                            <input type="number" id="groupsRequired" name="groups_required" value="<?php echo (int)$groupsRequired; ?>" min="1" max="4" <?php echo $canManage ? '' : 'readonly'; ?>>
                        </div>

                        <div class="field small">
                            <label>Teams in each group</label>
                            <input type="number" value="<?php echo (int)$teamsPerGroup; ?>" readonly>
                        </div>

                        <div class="field small">
                            <label>Teams from GL to Q (per group) <span>(Max 6)</span></label>
                            <input type="number" value="2" <?php echo $canManage ? '' : 'readonly'; ?>>
                        </div>

                        <div class="field small">
                            <label>Teams from Q to Semi <span>(Max 4)</span></label>
                            <input type="number" value="4" <?php echo $canManage ? '' : 'readonly'; ?>>
                        </div>
                    </div>

                    <!-- Match rules -->
                    <h4 class="sub-title">Match Rules</h4>
                    <div class="table-responsive">
                        <table class="match-rules-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Game Sets</th>
                                    <th>Deuce</th>
                                    <th>Deuce Limit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $matchRuleStages = [
                                    ['key' => 'GROUP', 'label' => 'League Stage', 'sets' => 1, 'deuce' => 'no', 'limit' => 'NA'],
                                    ['key' => 'QUARTER_FINAL', 'label' => 'Quarter Final', 'sets' => 3, 'deuce' => 'yes', 'limit' => '25'],
                                    ['key' => 'SEMI_FINAL', 'label' => 'Semi Final', 'sets' => 3, 'deuce' => 'yes', 'limit' => '30'],
                                    ['key' => 'FINAL', 'label' => 'Championship Final', 'sets' => 3, 'deuce' => 'yes', 'limit' => '30'],
                                    ['key' => 'BRONZE_FINAL', 'label' => 'Bronze Final', 'sets' => 3, 'deuce' => 'yes', 'limit' => '30'],
                                ];
                                ?>
                                <?php foreach ($matchRuleStages as $ruleStage): ?>
                                    <?php $selectedMatchRule = $matchRuleConfigs[$ruleStage['key']] ?? $matchRuleDefaults[$ruleStage['key']]; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ruleStage['label']); ?></td>
                                        <td>
                                            <select class="match-rule-select" data-stage="<?php echo $ruleStage['key']; ?>" data-rule="sets" <?php echo $canManage ? '' : 'disabled'; ?>>
                                                <option value="1" <?php echo $selectedMatchRule['sets'] === 1 ? 'selected' : ''; ?>>1</option>
                                                <option value="3" <?php echo $selectedMatchRule['sets'] === 3 ? 'selected' : ''; ?>>3</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="match-rule-select" data-stage="<?php echo $ruleStage['key']; ?>" data-rule="deuce" <?php echo $canManage ? '' : 'disabled'; ?>>
                                                <option value="no" <?php echo !$selectedMatchRule['deuce'] ? 'selected' : ''; ?>>No</option>
                                                <option value="yes" <?php echo $selectedMatchRule['deuce'] ? 'selected' : ''; ?>>Yes</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="match-rule-select" data-stage="<?php echo $ruleStage['key']; ?>" data-rule="deuceLimit" <?php echo $canManage ? '' : 'disabled'; ?>>
                                                <option value="NA" <?php echo $selectedMatchRule['deuceLimit'] === null ? 'selected' : ''; ?>>NA</option>
                                                <option value="25" <?php echo $selectedMatchRule['deuceLimit'] === 25 ? 'selected' : ''; ?>>25</option>
                                                <option value="30" <?php echo $selectedMatchRule['deuceLimit'] === 30 ? 'selected' : ''; ?>>30</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="sub-title">Set Court</h4>
                    <div class="court-assignment-controls">
                        <div class="field small">
                            <label for="courtAssignmentCategory">Category</label>
                            <select id="courtAssignmentCategory" <?php echo $canManage ? '' : 'disabled'; ?>>
                                <?php foreach ($allowedGroups as $groupName): ?>
                                    <option value="GROUP|<?php echo htmlspecialchars($groupName); ?>">League Stage - <?php echo htmlspecialchars(str_replace('Group-', '', $groupName)); ?></option>
                                <?php endforeach; ?>
                                <option value="QUARTER_FINAL|">Quarter Final</option>
                                <option value="SEMI_FINAL|">Semi Final</option>
                                <option value="FINAL|">Championship Final</option>
                                <option value="BRONZE_FINAL|">Bronze Final</option>
                            </select>
                        </div>
                        <div class="field small">
                            <label for="courtAssignmentCourts">Courts</label>
                            <input type="text" id="courtAssignmentCourts" inputmode="numeric" placeholder="Example: 11, 12, 13" <?php echo $canManage ? '' : 'disabled'; ?> aria-label="Enter one or more court numbers separated by commas">
                        </div>
                        <button type="button" id="saveCourtAssignment" class="save" <?php echo $canManage ? '' : 'disabled'; ?>>Assign Courts</button>
                    </div>
                    <div id="courtAssignmentList" class="court-assignment-list" aria-live="polite"></div>

                    <!-- Match Selection -->
                    <h4 class="sub-title">Match Selection Logic</h4>

                    <!-- Quarter Final -->
                    <div class="match-box">
                        <p>Quarter Final - Group Match</p>
                        <div id="quarterFinalGroupPairs" class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <?php for ($pairIndex = 0; $pairIndex < max(1, (int)ceil($groupsRequired / 2)); $pairIndex++): ?>
                                <div class="selector qf-group-pair">
                                    <input type="text" class="qf-group-input" data-slot="<?php echo $pairIndex * 2; ?>" placeholder="Select Group" readonly>
                                    <span>VS</span>
                                    <input type="text" class="qf-group-input" data-slot="<?php echo ($pairIndex * 2) + 1; ?>" placeholder="Select Group" readonly>
                                    <?php if ($pairIndex === 0): ?>
                                        <button type="button" class="small-spin-btn badge" data-bs-toggle="modal"
                                            data-bs-target="#spinWheelModal" data-spin-mode="quarter-groups" title="Spin" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>>Spin</button>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="d-none">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="selector">
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Semi Final -->
                    <div class="match-box">
                        <p>Semi Final – Team Match</p>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="selector">
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <span>VS</span>
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <span>VS</span>
                                <select <?php echo $canManage ? '' : 'disabled'; ?>>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                        </div>
                    </div>

                    <div class="action-area">
                        <!-- <button type="button" class="spin-btn" data-bs-toggle="modal" data-bs-target="#spinWheelModal">🎯 Spin a Wheel</button> -->
                        <button class="save" <?php echo $canManage ? '' : 'disabled'; ?>>Save Parameters</button>
                    </div>
                </div>
            </div>
            <div class="registertable">
                <!----Teams Registered--->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Teams Registered</h4>
                        <a href="#" class="btn">View All Group</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>Seq</th>
                                <th>Team</th>
                                <th>Player-1</th>
                                <th>Player-2</th>
                                <th>Group</th>
                                <th>Action</th>
                            </tr>
                            <?php $teamSeq = 0; ?>
                            <?php if ($dbError): ?>
                                <tr>
                                    <td colspan="6"><?php echo htmlspecialchars($dbError); ?></td>
                                </tr>
                            <?php elseif (empty($teamRows)): ?>
                                <tr>
                                    <td colspan="6">No teams registered.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teamRows as $team): ?>
                                    <?php
                                    $players = array_values(array_filter(explode('||', $team['PLAYERS'] ?? '')));
                                    $selectedGroup = $team['GROUP_NAME'] ?? '';
                                    ?>
                                    <tr>
                                        <td><?php echo ++$teamSeq; ?></td>
                                        <td><?php echo htmlspecialchars($team['TEAM_NAME'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                        <td class="team-group-display" id="team-group-<?php echo (int)$team['ID']; ?>">
                                            <?php echo htmlspecialchars($selectedGroup ?: ''); ?>
                                        </td>
                                        <td>
                                            <select class="team-group-select" data-team-id="<?php echo (int)$team['ID']; ?>" <?php echo $canManage ? '' : 'disabled'; ?>>
                                                <option value="">Select Group</option>
                                                <?php foreach ($allowedGroups as $groupName): ?>
                                                    <option value="<?php echo htmlspecialchars($groupName); ?>" <?php echo $selectedGroup === $groupName ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($groupName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="grouptable">

            <!-- All GROUP -->
            <div class="card">
                <!-- GROUP STAGE -->
                <div class="d-flex align-items-center justify-content-between gap-1">
                    <h4 class="section-title">League Stage</h4>
                </div>

                <div class="grid-4">
                    <?php foreach ($allowedGroups as $groupName): ?>
                        <?php $groupRows = $leagueGroups[$groupName] ?? []; ?>
                        <div class="card">
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <h6><?php echo htmlspecialchars($groupName); ?></h6>
                                <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&stage=GROUP&group=<?php echo urlencode($groupName); ?>" class="btn">View</a>
                            </div>
                            <table>
                                <tr>
                                    <th>Seq</th>
                                    <th>Team</th>
                                    <th>Player-1</th>
                                    <th>Player-2</th>
                                    <th>Point</th>
                                    <th>Rank</th>
                                </tr>
                                <?php $leagueSeq = 0; ?>
                                <?php if (empty($groupRows)): ?>
                                    <tr>
                                        <td colspan="6">No teams allocated.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($groupRows as $groupTeam): ?>
                                        <?php $players = array_values(array_filter(explode('||', $groupTeam['PLAYERS'] ?? ''))); ?>
                                        <tr>
                                            <td><?php echo ++$leagueSeq; ?></td>
                                            <td><?php echo htmlspecialchars($groupTeam['TEAM_NAME'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                            <td><?php echo (int)($groupTeam['POINTS'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars((string)($groupTeam['RANK_NO'] ?? '-')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid-4">
                <!-- QUARTER FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Quarter Final</h4>
                        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&stage=QUARTER_FINAL" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Seq</th>
                            <th>Team</th>
                            <th>Player-1</th>
                            <th>Player-2</th>
                            <th>Point</th>
                            <th>Rank</th>
                        </tr>
                        <?php $quarterFinalSeq = 0; ?>
                        <?php if (empty($quarterFinalRows)): ?>
                            <tr>
                                <td colspan="6" class="stage-empty-note stage-empty-note--qf">
                                    <span class="stage-empty-badge">Awaiting draw</span>
                                    <span class="stage-empty-title">Quarter Finals not available yet</span>
                                    <span class="stage-empty-hint stage-empty-hint--action">Spin the wheel from Tournament Parameter to set the matchups.</span>
                                    <span class="stage-empty-hint">Unlocks once all league matches are completed.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quarterFinalRows as $qfTeam): ?>
                                <?php $players = array_values(array_filter(explode('||', $qfTeam['PLAYERS'] ?? ''))); ?>
                                <tr>
                                    <td><?php echo ++$quarterFinalSeq; ?></td>
                                    <td><?php echo htmlspecialchars($qfTeam['TEAM_NAME'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                    <td><?php echo (int)($qfTeam['POINTS'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars((string)($qfTeam['RANK_NO'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- SEMI FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Semi Final</h4>
                        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&stage=SEMI_FINAL" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Seq</th>
                            <th>Team</th>
                            <th>Player-1</th>
                            <th>Player-2</th>
                            <th>Point</th>
                            <th>Rank</th>
                        </tr>
                        <?php $semiFinalSeq = 0; ?>
                        <?php if (empty($semiFinalRows)): ?>
                            <tr>
                                <td colspan="6" class="stage-empty-note stage-empty-note--sf">
                                    <span class="stage-empty-badge">Awaiting draw</span>
                                    <span class="stage-empty-title">Semi Finals not available yet</span>
                                    <span class="stage-empty-hint stage-empty-hint--action">Spin the wheel from Tournament Parameter to set the matchups.</span>
                                    <span class="stage-empty-hint">Unlocks once the Quarter Finals are completed.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($semiFinalRows as $sfTeam): ?>
                                <?php $players = array_values(array_filter(explode('||', $sfTeam['PLAYERS'] ?? ''))); ?>
                                <tr>
                                    <td><?php echo ++$semiFinalSeq; ?></td>
                                    <td><?php echo htmlspecialchars($sfTeam['TEAM_NAME'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                    <td><?php echo (int)($sfTeam['POINTS'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars((string)($sfTeam['RANK_NO'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="grid-4">
                <!-- FINAL -->
                <div class="card winner">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Championship Final</h4>
                        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&stage=FINAL" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Seq</th>
                            <th>Team</th>
                            <th>Player-1</th>
                            <th>Player-2</th>
                            <th>Point</th>
                            <th>Rank</th>
                        </tr>
                        <?php $championshipFinalSeq = 0; ?>
                        <?php if (empty($finalRows)): ?>
                            <tr>
                                <td colspan="6" class="stage-empty-note stage-empty-note--final">
                                    <span class="stage-empty-badge">Awaiting result</span>
                                    <span class="stage-empty-title">Championship Final not available yet</span>
                                    <span class="stage-empty-hint">Unlocks once the Semi Finals are completed.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($finalRows as $finalTeam): ?>
                                <?php $players = array_values(array_filter(explode('||', $finalTeam['PLAYERS'] ?? ''))); ?>
                                <tr>
                                    <td><?php echo ++$championshipFinalSeq; ?></td>
                                    <td><?php echo htmlspecialchars($finalTeam['TEAM_NAME'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                    <td><?php echo (int)($finalTeam['POINTS'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars((string)($finalTeam['RANK_NO'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- LOOSER FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Bronze Final</h4>
                        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&stage=BRONZE_FINAL" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Seq</th>
                            <th>Team</th>
                            <th>Player-1</th>
                            <th>Player-2</th>
                            <th>Point</th>
                            <th>Rank</th>
                        </tr>
                        <?php $bronzeFinalSeq = 0; ?>
                        <?php if (empty($bronzeFinalRows)): ?>
                            <tr>
                                <td colspan="6" class="stage-empty-note stage-empty-note--bronze">
                                    <span class="stage-empty-badge">Awaiting result</span>
                                    <span class="stage-empty-title">Bronze Final not available yet</span>
                                    <span class="stage-empty-hint">Unlocks once the Semi Finals are completed.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bronzeFinalRows as $bronzeTeam): ?>
                                <?php $players = array_values(array_filter(explode('||', $bronzeTeam['PLAYERS'] ?? ''))); ?>
                                <tr>
                                    <td><?php echo ++$bronzeFinalSeq; ?></td>
                                    <td><?php echo htmlspecialchars($bronzeTeam['TEAM_NAME'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                    <td><?php echo (int)($bronzeTeam['POINTS'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars((string)($bronzeTeam['RANK_NO'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<style>
    .match-rules-table {
        width: 100%;
        margin: 0;
    }
    .match-rules-table th,
    .match-rules-table td {
        vertical-align: middle;
        white-space: nowrap;
    }
    .match-rules-table select {
        width: 100%;
        min-width: 84px;
        padding: 6px 28px 6px 8px;
        color: #d7f3ff;
        background-color: #081827;
        border: 1px solid #31506a;
        border-radius: 5px;
    }
    .match-rules-table select:disabled {
        opacity: .65;
        cursor: not-allowed;
    }
    .court-assignment-controls {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) minmax(150px, 1fr) auto;
        gap: 12px;
        align-items: end;
    }
    .court-assignment-controls select {
        width: 100%;
        color: #d7f3ff;
        background-color: #081827;
        border: 1px solid #31506a;
        border-radius: 5px;
        padding: 7px;
    }
    .court-assignment-controls select[multiple] {
        min-height: 108px;
    }
    .court-assignment-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 14px;
        margin-top: 12px;
        color: #9cc9ee;
        font-size: .85rem;
    }
    .court-assignment-list strong { color: #d7f3ff; }
    @media (max-width: 650px) {
        .court-assignment-controls { grid-template-columns: 1fr; }
    }
    .stage-empty-note {
        display: block;
        text-align: center;
        padding: 18px 16px;
        background: transparent;
    }
    .stage-empty-note .stage-empty-badge {
        display: inline-block;
        margin-bottom: 8px;
        padding: 3px 0;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        background: transparent;
    }
    .stage-empty-note .stage-empty-title {
        display: block;
        font-size: 0.92rem;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .stage-empty-note .stage-empty-hint {
        display: block;
        font-size: 0.8rem;
        line-height: 1.5;
        opacity: 0.85;
    }
    .stage-empty-note .stage-empty-hint--action {
        font-weight: 500;
        opacity: 1;
        margin-bottom: 2px;
    }

    /* Quarter Final — sober blue */
    .stage-empty-note--qf .stage-empty-badge { color: #2f6fb0; }
    .stage-empty-note--qf .stage-empty-title,
    .stage-empty-note--qf .stage-empty-hint { color: #3a6a99; }

    /* Semi Final — sober teal */
    .stage-empty-note--sf .stage-empty-badge { color: #2b8577; }
    .stage-empty-note--sf .stage-empty-title,
    .stage-empty-note--sf .stage-empty-hint { color: #37796e; }

    /* Championship Final — sober amber/gold */
    .stage-empty-note--final .stage-empty-badge { color: #9a7b2e; }
    .stage-empty-note--final .stage-empty-title,
    .stage-empty-note--final .stage-empty-hint { color: #8a7134; }

    /* Bronze Final — sober warm grey/bronze */
    .stage-empty-note--bronze .stage-empty-badge { color: #8a6d55; }
    .stage-empty-note--bronze .stage-empty-title,
    .stage-empty-note--bronze .stage-empty-hint { color: #7d6553; }
</style>

<!-- Spin a Wheel Modal start-->
<div class="modal fade" id="spinWheelModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="spinWheelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="spinWheelModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <?php include "spin-wheel.php"; ?>
            </div>
        </div>
    </div>
</div>
<!-- Spin a Wheel Modal End-->

<!---toggleCollapse js---->
<script>
    function toggleCollapse() {
        const header = document.querySelector(".collapse-header");
        const body = document.getElementById("collapseContent");

        header.classList.toggle("active");
        body.classList.toggle("active");
    }

    const battleTournamentEndpoint = 'battle-tournament.php?id=<?php echo (int)$tournamentId; ?>';
    const groupsRequiredInput = document.getElementById('groupsRequired');
    const groupSelects = document.querySelectorAll('.team-group-select');
    const allowedQuarterGroups = <?php echo json_encode($allowedGroups); ?>;
    const matchRuleSelects = document.querySelectorAll('.match-rule-select');
    const canManageMatchRules = <?php echo $canManage ? 'true' : 'false'; ?>;
    const persistedMatchRules = <?php echo json_encode($matchRuleConfigs); ?>;
    const persistedCourtAssignments = <?php echo json_encode($courtAssignments); ?>;
    const courtAssignmentCategory = document.getElementById('courtAssignmentCategory');
    const courtAssignmentCourts = document.getElementById('courtAssignmentCourts');
    const saveCourtAssignmentButton = document.getElementById('saveCourtAssignment');
    const courtAssignmentList = document.getElementById('courtAssignmentList');
    const defaultMatchRules = {
        GROUP: { sets: 1, deuce: false, deuceLimit: 'NA' },
        QUARTER_FINAL: { sets: 3, deuce: true, deuceLimit: '25' },
        SEMI_FINAL: { sets: 3, deuce: true, deuceLimit: '30' },
        FINAL: { sets: 3, deuce: true, deuceLimit: '30' },
        BRONZE_FINAL: { sets: 3, deuce: true, deuceLimit: '30' }
    };

    function readTournamentSetConfig() {
        return persistedMatchRules || {};
    }

    function saveTournamentSetConfig(config) {
        if (!canManageMatchRules) {
            return Promise.resolve();
        }

        const formData = new FormData();
        formData.append('action', 'save_match_rules');
        formData.append('rules', JSON.stringify(config));

        return fetch(battleTournamentEndpoint, {
            method: 'POST',
            body: formData
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (!data.success) {
                throw new Error(data.message || 'Unable to save match rules.');
            }
            Object.keys(config).forEach(function (stage) {
                persistedMatchRules[stage] = config[stage];
            });
        }).catch(function (error) {
            alert(error.message || 'Unable to save match rules.');
        });
    }

    function getMatchRules(stage, storedRules) {
        const defaults = defaultMatchRules[stage] || defaultMatchRules.GROUP;
        const rules = storedRules && typeof storedRules === 'object' ? storedRules : {};
        const storedSets = typeof storedRules === 'number' ? storedRules : rules.sets;
        const deuce = typeof rules.deuce === 'boolean' ? rules.deuce : defaults.deuce;
        const deuceLimit = ['25', '30'].includes(String(rules.deuceLimit))
            ? String(rules.deuceLimit)
            : defaults.deuceLimit;

        return {
            sets: parseInt(storedSets || defaults.sets, 10) === 1 ? 1 : 3,
            deuce: deuce,
            deuceLimit: deuce ? deuceLimit : 'NA'
        };
    }

    function syncDeuceLimitControl(stage) {
        const deuceSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="deuce"]');
        const limitSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="deuceLimit"]');
        if (!deuceSelect || !limitSelect) {
            return;
        }

        const deuceEnabled = deuceSelect.value === 'yes';
        if (!deuceEnabled) {
            limitSelect.value = 'NA';
        } else if (limitSelect.value === 'NA') {
            limitSelect.value = defaultMatchRules[stage].deuceLimit === 'NA' ? '25' : defaultMatchRules[stage].deuceLimit;
        }
        limitSelect.disabled = !canManageMatchRules || !deuceEnabled;
    }

    function getRulesFromControls() {
        const config = {};
        Object.keys(defaultMatchRules).forEach(function (stage) {
            const setsSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="sets"]');
            const deuceSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="deuce"]');
            const limitSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="deuceLimit"]');
            const defaults = defaultMatchRules[stage];
            const deuce = deuceSelect ? deuceSelect.value === 'yes' : defaults.deuce;
            config[stage] = {
                sets: setsSelect && setsSelect.value === '1' ? 1 : defaults.sets,
                deuce: deuce,
                deuceLimit: deuce && limitSelect && ['25', '30'].includes(limitSelect.value) ? limitSelect.value : 'NA'
            };
        });
        return config;
    }

    function applyStoredSetConfig() {
        const storedConfig = readTournamentSetConfig();
        Object.keys(defaultMatchRules).forEach(function (stage) {
            const rules = getMatchRules(stage, storedConfig[stage]);
            const setsSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="sets"]');
            const deuceSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="deuce"]');
            const limitSelect = document.querySelector('.match-rule-select[data-stage="' + stage + '"][data-rule="deuceLimit"]');
            if (setsSelect) { setsSelect.value = String(rules.sets); }
            if (deuceSelect) { deuceSelect.value = rules.deuce ? 'yes' : 'no'; }
            if (limitSelect) { limitSelect.value = rules.deuceLimit; }
            syncDeuceLimitControl(stage);
        });
    }

    function courtAssignmentKey(stage, groupName) {
        return stage === 'GROUP' ? 'GROUP:' + groupName : stage;
    }

    function courtAssignmentLabel(stage, groupName) {
        if (stage === 'GROUP') {
            return 'League Stage - ' + String(groupName || '').replace('Group-', '');
        }
        return {
            QUARTER_FINAL: 'Quarter Final',
            SEMI_FINAL: 'Semi Final',
            FINAL: 'Championship Final',
            BRONZE_FINAL: 'Bronze Final'
        }[stage] || stage;
    }

    function applyCourtAssignmentSelection() {
        if (!courtAssignmentCategory || !courtAssignmentCourts) {
            return;
        }
        const parts = courtAssignmentCategory.value.split('|');
        const assignment = persistedCourtAssignments[courtAssignmentKey(parts[0], parts[1] || '')];
        const selectedCourts = assignment && Array.isArray(assignment.courts) ? assignment.courts : [];
        courtAssignmentCourts.value = selectedCourts.join(', ');
    }

    function renderCourtAssignmentList() {
        if (!courtAssignmentList) {
            return;
        }
        const rows = Object.keys(persistedCourtAssignments).sort().map(function (key) {
            const assignment = persistedCourtAssignments[key];
            const courts = Array.isArray(assignment.courts) ? assignment.courts.join(', ') : '';
            return '<span><strong>' + courtAssignmentLabel(assignment.stage, assignment.groupName) + '</strong>: ' + (courts || '-') + '</span>';
        });
        courtAssignmentList.innerHTML = rows.length ? rows.join('') : '<span>No courts assigned yet.</span>';
    }

    function updateQuarterGroupPairInputs(winners, remaining) {
        const spunGroups = winners || [];
        const leftGroups = remaining || [];
        const selected = allowedQuarterGroups.length <= 2 || spunGroups.length >= 2
            ? spunGroups.concat(leftGroups)
            : spunGroups;
        document.querySelectorAll('.qf-group-input').forEach(function (input) {
            const slot = parseInt(input.dataset.slot || '0', 10);
            input.value = selected[slot] || '';
        });
        localStorage.setItem('quarterFinalGroupPairs_<?php echo (int)$tournamentId; ?>', JSON.stringify(selected));
    }

    function restoreQuarterGroupPairInputs() {
        try {
            const selected = JSON.parse(localStorage.getItem('quarterFinalGroupPairs_<?php echo (int)$tournamentId; ?>') || '[]') || [];
            document.querySelectorAll('.qf-group-input').forEach(function (input) {
                const slot = parseInt(input.dataset.slot || '0', 10);
                input.value = selected[slot] || '';
            });
        } catch (error) {
            updateQuarterGroupPairInputs([], []);
        }
    }

    function openQuarterGroupSpin() {
        if (typeof window.loadSpinItems !== 'function') {
            return;
        }
        const modalTitle = document.getElementById('spinWheelModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Spin Quarter Final Groups';
        }
        updateQuarterGroupPairInputs([], []);
        window.loadSpinItems(allowedQuarterGroups, function (winners, remaining) {
            updateQuarterGroupPairInputs(winners, remaining);
        });
    }

    applyStoredSetConfig();
    applyCourtAssignmentSelection();
    renderCourtAssignmentList();
    restoreQuarterGroupPairInputs();

    matchRuleSelects.forEach(function (select) {
        select.addEventListener('change', function () {
            if (this.dataset.rule === 'deuce') {
                syncDeuceLimitControl(this.dataset.stage);
            }
            saveTournamentSetConfig(getRulesFromControls());
        });
    });

    if (courtAssignmentCategory) {
        courtAssignmentCategory.addEventListener('change', applyCourtAssignmentSelection);
    }

    if (saveCourtAssignmentButton) {
        saveCourtAssignmentButton.addEventListener('click', function () {
            if (!courtAssignmentCategory || !courtAssignmentCourts) {
                return;
            }
            const parts = courtAssignmentCategory.value.split('|');
            const stage = parts[0];
            const groupName = parts[1] || '';
            const courts = courtAssignmentCourts.value.split(',').map(function (value) {
                return parseInt(value.trim(), 10);
            }).filter(function (court) { return court > 0; });
            if (!courts.length) {
                alert('Please select at least one court.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_court_assignment');
            formData.append('stage', stage);
            formData.append('group_name', groupName);
            formData.append('courts', JSON.stringify(courts));
            saveCourtAssignmentButton.disabled = true;

            fetch(battleTournamentEndpoint, { method: 'POST', body: formData })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Unable to assign courts.');
                    }
                    persistedCourtAssignments[data.category_key] = {
                        stage: stage,
                        groupName: groupName,
                        courts: data.courts
                    };
                    renderCourtAssignmentList();
                })
                .catch(function (error) {
                    alert(error.message || 'Unable to assign courts.');
                })
                .finally(function () {
                    saveCourtAssignmentButton.disabled = !canManageMatchRules;
                });
        });
    }

    document.querySelectorAll('[data-spin-mode="quarter-groups"]').forEach(function (button) {
        button.addEventListener('click', openQuarterGroupSpin);
    });

    if (groupsRequiredInput) {
        groupsRequiredInput.addEventListener('change', function () {
            let groupsRequired = parseInt(this.value, 10) || 1;
            groupsRequired = Math.max(1, Math.min(4, groupsRequired));
            window.location.href = new URL(window.location.href).pathname + '?id=<?php echo (int)$tournamentId; ?>&groups_required=' + groupsRequired;
        });
    }

    groupSelects.forEach(function (select) {
        select.addEventListener('change', function () {
            const teamId = this.dataset.teamId;
            const groupName = this.value;
            const displayCell = document.getElementById('team-group-' + teamId);
            const previousText = displayCell ? displayCell.textContent : '';

            this.disabled = true;

            const formData = new FormData();
            formData.append('action', 'update_team_group');
            formData.append('team_id', teamId);
            formData.append('group_name', groupName);
            formData.append('groups_required', groupsRequiredInput ? groupsRequiredInput.value : '<?php echo (int)$groupsRequired; ?>');

            fetch(battleTournamentEndpoint, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Group update failed.');
                    }

                    if (displayCell) {
                        displayCell.textContent = data.group_name || '';
                    }

                    window.location.reload();
                })
                .catch(function (error) {
                    if (displayCell) {
                        displayCell.textContent = previousText;
                    }

                    alert(error.message);
                })
                .finally(() => {
                    this.disabled = false;
                });
        });
    });
</script>
