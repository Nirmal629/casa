<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$groupLabels = ['Group-A', 'Group-B', 'Group-C', 'Group-D', 'Group-E', 'Group-F'];
$maxGroups = 6;
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
$tournamentStarted = false;
$advancersPerGroup = 2;
$savedPairings = [];
$hasSavedParameters = false;
$quarterFinalWinnerOptions = [];
$semiFinalEligibleOptions = [];
$semiFinalScheduledTeams = [];
$directSemiFinal = false;
$matchRuleDefaults = [
    'GROUP' => ['sets' => 1, 'deuce' => true, 'deuceLimit' => 21],
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

function battleTournamentHasStarted(PDO $pdo, int $tournamentId): bool
{
    if ($tournamentId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STATUS IN ('RUNNING', 'PAUSED', 'COMPLETED')");
    $stmt->execute([':tournament_id' => $tournamentId]);
    return (int)$stmt->fetchColumn() > 0;
}

function battleTournamentStartedResponse(): void
{
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tournament has started. Tournament parameters can no longer be changed.']);
    exit;
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS to_tournament_parameters (
        TOURNAMENT_ID INT NOT NULL,
        GROUP_COUNT TINYINT NOT NULL DEFAULT 4,
        ADVANCERS_PER_GROUP TINYINT NOT NULL DEFAULT 2,
        UPDATED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (TOURNAMENT_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS to_tournament_pairings (
        TOURNAMENT_ID INT NOT NULL,
        STAGE VARCHAR(30) NOT NULL,
        PAIRS_JSON TEXT NOT NULL,
        UPDATED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (TOURNAMENT_ID, STAGE)
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
    $tournamentStarted = battleTournamentHasStarted($pdo, $tournamentId);

    if ($tournamentId > 0) {
        $parameterStmt = $pdo->prepare("SELECT GROUP_COUNT, ADVANCERS_PER_GROUP FROM to_tournament_parameters WHERE TOURNAMENT_ID = :tournament_id");
        $parameterStmt->execute([':tournament_id' => $tournamentId]);
        $parameters = $parameterStmt->fetch(PDO::FETCH_ASSOC);
        if ($parameters) {
            $hasSavedParameters = true;
            $groupsRequired = max(1, min($maxGroups, (int)$parameters['GROUP_COUNT']));
            $advancersPerGroup = max(1, min(6, (int)$parameters['ADVANCERS_PER_GROUP']));
            $allowedGroups = array_slice($groupLabels, 0, $groupsRequired);
        }
        $directSemiFinal = ($groupsRequired * $advancersPerGroup) === 4;
        $pairingStmt = $pdo->prepare("SELECT STAGE, PAIRS_JSON FROM to_tournament_pairings WHERE TOURNAMENT_ID = :tournament_id");
        $pairingStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($pairingStmt->fetchAll(PDO::FETCH_ASSOC) as $pairing) {
            $savedPairings[(string)$pairing['STAGE']] = json_decode((string)$pairing['PAIRS_JSON'], true) ?: [];
        }
    }

    if (($_POST['action'] ?? '') === 'save_tournament_parameters') {
        header('Content-Type: application/json');
        if (!$canManage || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can update tournament parameters.']); exit;
        }
        if ($tournamentStarted) { battleTournamentStartedResponse(); }
        $newGroups = max(1, min($maxGroups, (int)($_POST['groups_required'] ?? 0)));
        $newAdvancers = max(1, min(6, (int)($_POST['advancers_per_group'] ?? 0)));
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = :tournament_id");
        $countStmt->execute([':tournament_id' => $tournamentId]);
        $registered = (int)$countStmt->fetchColumn();
        if ((int)ceil($registered / $newGroups) > 8) {
            echo json_encode(['success' => false, 'message' => 'These groups would contain more than 8 teams. Use at least ' . (int)ceil($registered / 8) . ' groups for ' . $registered . ' registered teams.']); exit;
        }
        $qualifierCount = $newGroups * $newAdvancers;
        if (!in_array($qualifierCount, [4, 8], true)) {
            echo json_encode(['success' => false, 'message' => 'Choose exactly 8 qualifying teams for Quarter-Finals, or exactly 4 qualifying teams to skip Quarter-Finals and draw Semi-Finals directly.']); exit;
        }
        $saveParameters = $pdo->prepare("INSERT INTO to_tournament_parameters (TOURNAMENT_ID, GROUP_COUNT, ADVANCERS_PER_GROUP) VALUES (:tournament_id, :group_count, :advancers) ON DUPLICATE KEY UPDATE GROUP_COUNT = VALUES(GROUP_COUNT), ADVANCERS_PER_GROUP = VALUES(ADVANCERS_PER_GROUP)");
        $saveParameters->execute([':tournament_id' => $tournamentId, ':group_count' => $newGroups, ':advancers' => $newAdvancers]);
        echo json_encode(['success' => true, 'groups_required' => $newGroups, 'teams_per_group' => (int)ceil($registered / $newGroups)]); exit;
    }

    if (($_POST['action'] ?? '') === 'save_knockout_pairings') {
        header('Content-Type: application/json');
        if (!$canManage || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can set knockout pairings.']); exit;
        }
        $stage = strtoupper(trim((string)($_POST['stage'] ?? '')));
        $directToSemiRequest = $stage === 'SEMI_FINAL' && !empty($_POST['direct_semi']);
        $pairs = json_decode((string)($_POST['pairs'] ?? '[]'), true);
        $expectedPairEntries = $stage === 'QUARTER_FINAL' ? $groupsRequired : 4;
        if (!in_array($stage, ['QUARTER_FINAL', 'SEMI_FINAL'], true) || !is_array($pairs) || count($pairs) !== $expectedPairEntries) {
            echo json_encode(['success' => false, 'message' => 'Please complete the required wheel spins before scheduling this stage.']); exit;
        }
        if (count(array_unique($pairs)) !== $expectedPairEntries) {
            echo json_encode(['success' => false, 'message' => 'Each group or team may be selected only once.']); exit;
        }
        $existing = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = :stage");
        $existing->execute([':tournament_id' => $tournamentId, ':stage' => $stage]);
        if ((int)$existing->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'This stage has already been scheduled and cannot be changed.']); exit;
        }
        $teamIds = [];
        if ($stage === 'QUARTER_FINAL') {
            $pending = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = 'GROUP' AND STATUS <> 'COMPLETED'");
            $pending->execute([':tournament_id' => $tournamentId]);
            if ((int)$pending->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Complete all league-stage matches before spinning quarter-final groups.']); exit;
            }
            foreach ($pairs as $group) {
                if (!in_array($group, $allowedGroups, true)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid group selected.']); exit;
                }
            }
            $ranked = $pdo->prepare("SELECT TEAM_ID FROM to_standings WHERE TOURNAMENT_ID = :tournament_id AND STAGE = 'GROUP' AND GROUP_NAME = :group_name ORDER BY RANK_NO ASC, POINTS DESC, SCORE_DIFF DESC LIMIT :limit");
            $qualifiers = [];
            foreach ($pairs as $group) {
                $ranked->bindValue(':tournament_id', $tournamentId, PDO::PARAM_INT);
                $ranked->bindValue(':group_name', $group, PDO::PARAM_STR);
                $ranked->bindValue(':limit', $advancersPerGroup, PDO::PARAM_INT);
                $ranked->execute();
                $qualifiers[$group] = array_map('intval', $ranked->fetchAll(PDO::FETCH_COLUMN));
                if (count($qualifiers[$group]) !== $advancersPerGroup) {
                    echo json_encode(['success' => false, 'message' => 'Every group must have enough ranked teams before quarter-finals can be scheduled.']); exit;
                }
            }
            if ($groupsRequired === 2 && $advancersPerGroup === 4) {
                // Two groups: A1 v B4, A2 v B3, A3 v B2, A4 v B1.
                $left = $qualifiers[$pairs[0]]; $right = $qualifiers[$pairs[1]];
                for ($rank = 0; $rank < 4; $rank++) {
                    $teamIds[] = $left[$rank];
                    $teamIds[] = $right[3 - $rank];
                }
            } else {
                // Four groups: each paired group contributes two cross-group quarter-final matches.
                for ($i = 0; $i < 4; $i += 2) {
                    $left = $qualifiers[$pairs[$i]]; $right = $qualifiers[$pairs[$i + 1]];
                    if (count($left) !== 2 || count($right) !== 2) {
                        echo json_encode(['success' => false, 'message' => 'Quarter-finals support either 4 groups × 2 qualifiers or 2 groups × 4 qualifiers.']); exit;
                    }
                    $teamIds = array_merge($teamIds, [$left[0], $right[1], $left[1], $right[0]]);
                }
            }
        } else {
            $directToSemi = $directToSemiRequest || (($groupsRequired * $advancersPerGroup) === 4);
            $previousStage = $directToSemi ? 'GROUP' : 'QUARTER_FINAL';
            $pending = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = :stage AND STATUS <> 'COMPLETED'");
            $pending->execute([':tournament_id' => $tournamentId, ':stage' => $previousStage]);
            if ((int)$pending->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => $directToSemi ? 'Complete all league-stage matches before spinning semi-final teams.' : 'Complete all quarter-final matches before spinning semi-final teams.']); exit;
            }
            $pairs = array_map('intval', $pairs);
            if ($directToSemi) {
                $qualifierStmt = $pdo->prepare("SELECT TEAM_ID FROM to_standings WHERE TOURNAMENT_ID = :tournament_id AND STAGE = 'GROUP' AND RANK_NO <= :rank_limit ORDER BY GROUP_NAME, RANK_NO, TEAM_ID");
                $qualifierStmt->execute([':tournament_id' => $tournamentId, ':rank_limit' => $advancersPerGroup]);
                $eligibleIds = array_map('intval', $qualifierStmt->fetchAll(PDO::FETCH_COLUMN));
                sort($eligibleIds); $checkPairs = $pairs; sort($checkPairs);
                if (count($eligibleIds) !== 4 || $eligibleIds !== $checkPairs) {
                    echo json_encode(['success' => false, 'message' => 'Spin using the four qualified league-stage teams.']); exit;
                }
            } else {
                $winnerStmt = $pdo->prepare("SELECT WINNER_TEAM_ID FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = 'QUARTER_FINAL' AND STATUS = 'COMPLETED' ORDER BY MATCH_ORDER, ID");
                $winnerStmt->execute([':tournament_id' => $tournamentId]);
                $winnerIds = array_map('intval', $winnerStmt->fetchAll(PDO::FETCH_COLUMN));
                sort($winnerIds); $checkPairs = $pairs; sort($checkPairs);
                if ($winnerIds !== $checkPairs) {
                    echo json_encode(['success' => false, 'message' => 'Spin using the four qualified quarter-final winners.']); exit;
                }
            }
            $teamIds = $pairs;
        }
        $insert = $pdo->prepare("INSERT INTO to_matches (TOURNAMENT_ID, STAGE, GROUP_NAME, COURT_ID, ROUND_NO, MATCH_ORDER, TEAM_1_ID, TEAM_2_ID, TEAM_1_SCORE, TEAM_2_SCORE, STATUS) VALUES (:tournament_id, :stage, NULL, NULL, 1, :match_order, :team_1, :team_2, 0, 0, 'PENDING')");
        for ($i = 0, $order = 1; $i < count($teamIds); $i += 2, $order++) {
            $insert->execute([':tournament_id' => $tournamentId, ':stage' => $stage, ':match_order' => $order, ':team_1' => $teamIds[$i], ':team_2' => $teamIds[$i + 1]]);
        }
        $savePairing = $pdo->prepare("INSERT INTO to_tournament_pairings (TOURNAMENT_ID, STAGE, PAIRS_JSON) VALUES (:tournament_id, :stage, :pairs) ON DUPLICATE KEY UPDATE PAIRS_JSON = VALUES(PAIRS_JSON)");
        $savePairing->execute([':tournament_id' => $tournamentId, ':stage' => $stage, ':pairs' => json_encode($pairs)]);
        echo json_encode(['success' => true]); exit;
    }

    if (($_POST['action'] ?? '') === 'check_pairing_eligibility') {
        header('Content-Type: application/json');
        if (!$canManage || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can set knockout pairings.']); exit;
        }
        $stage = strtoupper(trim((string)($_POST['stage'] ?? '')));
        $directToSemiRequest = $stage === 'SEMI_FINAL' && !empty($_POST['direct_semi']);
        $directToSemi = $directToSemiRequest || (($groupsRequired * $advancersPerGroup) === 4);
        $previousStage = $stage === 'QUARTER_FINAL' ? 'GROUP' : ($stage === 'SEMI_FINAL' ? ($directToSemi ? 'GROUP' : 'QUARTER_FINAL') : '');
        if ($previousStage === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid knockout stage.']); exit;
        }
        if ($stage === 'QUARTER_FINAL') {
            $registeredStmt = $pdo->prepare("SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = :tournament_id");
            $registeredStmt->execute([':tournament_id' => $tournamentId]);
            $registered = (int)$registeredStmt->fetchColumn();
            if ($registered < 8 || ($groupsRequired * $advancersPerGroup) !== 8) {
                echo json_encode(['success' => false, 'message' => 'Quarter-finals need exactly 8 qualifying teams. Set 4 groups with 2 qualifiers each, or 2 groups with 4 qualifiers each, and register at least 8 teams.']); exit;
            }
        }
        $pending = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = :stage AND STATUS <> 'COMPLETED'");
        $pending->execute([':tournament_id' => $tournamentId, ':stage' => $previousStage]);
        if ((int)$pending->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => $stage === 'QUARTER_FINAL' ? 'Complete all league-stage matches before spinning quarter-final groups.' : ($directToSemi ? 'Complete all league-stage matches before spinning semi-final teams.' : 'Complete all quarter-final matches before spinning semi-final teams.')]); exit;
        }
        $completed = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = :stage AND STATUS = 'COMPLETED'");
        $completed->execute([':tournament_id' => $tournamentId, ':stage' => $previousStage]);
        if ((int)$completed->fetchColumn() === 0) {
            echo json_encode(['success' => false, 'message' => $stage === 'QUARTER_FINAL' ? 'Schedule and complete the league-stage matches before spinning quarter-final groups.' : ($directToSemi ? 'Complete all league-stage matches before spinning semi-final teams.' : 'Complete all quarter-final matches before spinning semi-final teams.')]); exit;
        }
        echo json_encode(['success' => true]); exit;
    }

    if (($_POST['action'] ?? '') === 'save_match_rules') {
        header('Content-Type: application/json');

        if (!$canManage || $tournamentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Only the tournament organizer can update match rules.']);
            exit;
        }
        if ($tournamentStarted) { battleTournamentStartedResponse(); }

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
            $deuceLimit = $deuceEnabled && in_array((int)($rules['deuceLimit'] ?? 0), [21, 25, 30], true)
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
        if ($tournamentStarted) { battleTournamentStartedResponse(); }

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
                'deuceLimit' => $deuceEnabled && in_array((int)$storedRule['DEUCE_LIMIT'], [21, 25, 30], true)
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

    if ($requestedGroups === null && !$hasSavedParameters) {
        $groupsRequired = inferGroupsRequiredFromTeams($pdo, $tournamentId, $groupColumn, $groupLabels, $maxGroups);
        $allowedGroups = array_slice($groupLabels, 0, $groupsRequired);
    }
    $directSemiFinal = ($groupsRequired * $advancersPerGroup) === 4;

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
        if ($tournamentStarted) { battleTournamentStartedResponse(); }

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
            if ($directSemiFinal && (int)$row['RANK_NO'] > 0 && (int)$row['RANK_NO'] <= $advancersPerGroup) {
                $semiFinalEligibleOptions[] = ['id' => (int)$row['TEAM_ID'], 'name' => (string)$row['TEAM_NAME']];
            }
        }

        // Show every scheduled knockout team in the Playoff Dashboard as soon as a draw is saved.
        $knockoutRowsStmt = $pdo->prepare("SELECT
                m.STAGE, m.MATCH_ORDER, m.TEAM_1_SCORE, m.TEAM_2_SCORE,
                t.ID AS TEAM_ID, t.NAME AS TEAM_NAME,
                GROUP_CONCAT(u.NAME ORDER BY u.ID SEPARATOR '||') AS PLAYERS
            FROM to_matches m
            INNER JOIN to_teams t ON t.ID IN (m.TEAM_1_ID, m.TEAM_2_ID)
            LEFT JOIN to_users u ON u.TEAM_ID = t.ID AND u.USERTYPE = 'Player'
            WHERE m.TOURNAMENT_ID = :tournament_id
              AND m.STAGE IN ('QUARTER_FINAL', 'SEMI_FINAL', 'FINAL', 'BRONZE_FINAL')
            GROUP BY m.ID, m.STAGE, m.MATCH_ORDER, m.TEAM_1_ID, m.TEAM_2_ID, m.TEAM_1_SCORE, m.TEAM_2_SCORE, t.ID, t.NAME
            ORDER BY m.STAGE, m.MATCH_ORDER, t.ID");
        $knockoutRowsStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($knockoutRowsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['POINTS'] = 0;
            $row['RANK_NO'] = '-';
            if ($row['STAGE'] === 'QUARTER_FINAL') { $quarterFinalRows[] = $row; }
            if ($row['STAGE'] === 'SEMI_FINAL') { $semiFinalRows[] = $row; }
            if ($row['STAGE'] === 'FINAL') { $finalRows[] = $row; }
            if ($row['STAGE'] === 'BRONZE_FINAL') { $bronzeFinalRows[] = $row; }
        }

        // The match ledger is the source of truth for a saved Semi-Final draw.
        // Keep the original TEAM_1 vs TEAM_2 order so it can be shown after reload.
        $semiFinalPairingStmt = $pdo->prepare("SELECT
                m.TEAM_1_ID, t1.NAME AS TEAM_1_NAME,
                m.TEAM_2_ID, t2.NAME AS TEAM_2_NAME
            FROM to_matches m
            INNER JOIN to_teams t1 ON t1.ID = m.TEAM_1_ID
            INNER JOIN to_teams t2 ON t2.ID = m.TEAM_2_ID
            WHERE m.TOURNAMENT_ID = :tournament_id
              AND m.STAGE = 'SEMI_FINAL'
            ORDER BY m.MATCH_ORDER, m.ID");
        $semiFinalPairingStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($semiFinalPairingStmt->fetchAll(PDO::FETCH_ASSOC) as $match) {
            $semiFinalScheduledTeams[] = ['id' => (int)$match['TEAM_1_ID'], 'name' => (string)$match['TEAM_1_NAME']];
            $semiFinalScheduledTeams[] = ['id' => (int)$match['TEAM_2_ID'], 'name' => (string)$match['TEAM_2_NAME']];
        }

        $winnerNameStmt = $pdo->prepare("SELECT m.WINNER_TEAM_ID, t.NAME FROM to_matches m INNER JOIN to_teams t ON t.ID = m.WINNER_TEAM_ID WHERE m.TOURNAMENT_ID = :tournament_id AND m.STAGE = 'QUARTER_FINAL' AND m.STATUS = 'COMPLETED' ORDER BY m.MATCH_ORDER, m.ID");
        $winnerNameStmt->execute([':tournament_id' => $tournamentId]);
        foreach ($winnerNameStmt->fetchAll(PDO::FETCH_ASSOC) as $winner) {
            $quarterFinalWinnerOptions[] = ['id' => (int)$winner['WINNER_TEAM_ID'], 'name' => (string)$winner['NAME']];
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
                            <label>Teams Registered</label>
                            <input type="number" value="<?php echo (int)$teamCount; ?>" readonly>
                        </div>

                        <div class="field small">
                            <label>Number of Groups <span>(Max 6)</span></label>
                            <input type="number" id="groupsRequired" name="groups_required" value="<?php echo (int)$groupsRequired; ?>" min="1" max="6" <?php echo ($canManage && !$tournamentStarted) ? '' : 'readonly'; ?>>
                        </div>

                        <div class="field small">
                            <label>Teams per Group <span>(Max 8)</span></label>
                            <input type="number" value="<?php echo (int)$teamsPerGroup; ?>" readonly>
                        </div>

                        <div class="field small">
                            <label>Teams Advancing from Group League (per group) <span>(Max 6)</span></label>
                            <input type="number" id="advancersPerGroup" value="<?php echo (int)$advancersPerGroup; ?>" min="1" max="6" <?php echo ($canManage && !$tournamentStarted) ? '' : 'readonly'; ?>>
                        </div>

                        <div class="field small">
                            <label>Teams Advancing from Quarter-Finals to Semi-Finals <span>(Groups Required - Max 4)</span></label>
                            <input type="number" value="4" readonly>
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
                                    ['key' => 'GROUP', 'label' => 'League Stage', 'sets' => 1, 'deuce' => 'yes', 'limit' => '21'],
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
                                            <select class="match-rule-select" data-stage="<?php echo $ruleStage['key']; ?>" data-rule="sets" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?>>
                                                <option value="1" <?php echo $selectedMatchRule['sets'] === 1 ? 'selected' : ''; ?>>1</option>
                                                <option value="3" <?php echo $selectedMatchRule['sets'] === 3 ? 'selected' : ''; ?>>3</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="match-rule-select" data-stage="<?php echo $ruleStage['key']; ?>" data-rule="deuce" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?>>
                                                <option value="no" <?php echo !$selectedMatchRule['deuce'] ? 'selected' : ''; ?>>No</option>
                                                <option value="yes" <?php echo $selectedMatchRule['deuce'] ? 'selected' : ''; ?>>Yes</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="match-rule-select" data-stage="<?php echo $ruleStage['key']; ?>" data-rule="deuceLimit" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?>>
                                                <option value="NA" <?php echo $selectedMatchRule['deuceLimit'] === null ? 'selected' : ''; ?>>NA</option>
                                                <option value="21" <?php echo $selectedMatchRule['deuceLimit'] === 21 ? 'selected' : ''; ?>>21</option>
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
                            <select id="courtAssignmentCategory" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?>>
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
                            <input type="text" id="courtAssignmentCourts" inputmode="numeric" placeholder="Example: 11, 12, 13" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?> aria-label="Enter one or more court numbers separated by commas">
                        </div>
                        <button type="button" id="saveCourtAssignment" class="save save-court-btn" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?> title="Save court assignment">Save</button>
                    </div>
                    <div id="courtAssignmentList" class="court-assignment-list" aria-live="polite"></div>

                    <!-- Match Selection -->
                    <h4 class="sub-title">Match Selection Logic</h4>

                    <!-- Quarter Final -->
                    <?php if (!$directSemiFinal): ?>
                    <div class="match-box">
                        <p>Quarter Final - Group Match</p>
                        <div id="quarterFinalGroupPairs" class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <?php for ($pairIndex = 0; $pairIndex < max(1, (int)ceil($groupsRequired / 2)); $pairIndex++): ?>
                                <div class="selector qf-group-pair">
                                    <input type="text" class="qf-group-input" data-slot="<?php echo $pairIndex * 2; ?>" placeholder="Select Group" readonly>
                                    <span>VS</span>
                                    <input type="text" class="qf-group-input" data-slot="<?php echo ($pairIndex * 2) + 1; ?>" placeholder="Select Group" readonly>
                                    <?php if ($pairIndex === 0): ?>
                                        <button type="button" class="small-spin-btn badge" data-spin-mode="quarter-groups" title="Spin" <?php echo $canManage ? '' : 'disabled aria-disabled="true"'; ?>>Spin</button>
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
                    <?php endif; ?>

                    <!-- Semi Final -->
                    <div class="match-box semi-final-spin-pairings">
                        <p>Semi Final - Team Pairings <button type="button" id="spinSemiFinalTeams" class="small-spin-btn badge" <?php echo ($canManage && empty($semiFinalRows)) ? '' : 'disabled aria-disabled="true"'; ?>>Spin</button></p>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="selector"><input class="semi-team-input" data-slot="0" placeholder="Spin a Wheel" readonly><span>VS</span><input class="semi-team-input" data-slot="1" placeholder="Spin a Wheel" readonly></div>
                            <div class="selector"><input class="semi-team-input" data-slot="2" placeholder="Auto populate" readonly><span>VS</span><input class="semi-team-input" data-slot="3" placeholder="Auto populate" readonly></div>
                        </div>
                    </div>
                    <div class="match-box d-none" aria-hidden="true">
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
                        <button type="button" id="saveTournamentParameters" class="save" <?php echo ($canManage && !$tournamentStarted) ? '' : 'disabled'; ?>>Save Parameters</button>
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
                                            <select class="team-group-select" data-team-id="<?php echo (int)$team['ID']; ?>" <?php echo $canManage ? ($tournamentStarted ? 'data-tournament-locked="true"' : '') : 'disabled'; ?>>
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
                        <?php if ($directSemiFinal): ?>
                            <tr>
                                <td colspan="6" class="stage-empty-note stage-empty-note--qf">
                                    <span class="stage-empty-badge">Skipped</span>
                                    <span class="stage-empty-title">Quarter Final skipped</span>
                                    <span class="stage-empty-hint">Four teams qualified from League Stage, so they advance directly to the Semi-Final draw.</span>
                                </td>
                            </tr>
                        <?php elseif (empty($quarterFinalRows)): ?>
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
                                    <span class="stage-empty-hint"><?php echo $directSemiFinal ? 'Unlocks once all league matches are completed.' : 'Unlocks once the Quarter Finals are completed.'; ?></span>
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
    .semi-final-spin-pairings + .match-box { display: none; }
    .save-court-btn { min-width: 58px; padding: 6px 10px; font-size: 12px; }
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
    const tournamentStarted = <?php echo $tournamentStarted ? 'true' : 'false'; ?>;
    const advancersPerGroupInput = document.getElementById('advancersPerGroup');
    const saveTournamentParametersButton = document.getElementById('saveTournamentParameters');
    const spinSemiFinalTeamsButton = document.getElementById('spinSemiFinalTeams');
    const groupSelects = document.querySelectorAll('.team-group-select');
    const allowedQuarterGroups = <?php echo json_encode($allowedGroups); ?>;
    const quarterFinalWinnerOptions = <?php echo json_encode($quarterFinalWinnerOptions); ?>;
    const semiFinalEligibleOptions = <?php echo json_encode($semiFinalEligibleOptions); ?>;
    const semiFinalScheduledTeams = <?php echo json_encode($semiFinalScheduledTeams); ?>;
    const savedSemiFinalPairing = <?php echo json_encode(array_map('intval', $savedPairings['SEMI_FINAL'] ?? [])); ?>;
    const semiFinalIsScheduled = <?php echo empty($semiFinalRows) ? 'false' : 'true'; ?>;
    const directSemiFinal = <?php echo $directSemiFinal ? 'true' : 'false'; ?>;
    const matchRuleSelects = document.querySelectorAll('.match-rule-select');
    const canManageMatchRules = <?php echo ($canManage && !$tournamentStarted) ? 'true' : 'false'; ?>;
    const persistedMatchRules = <?php echo json_encode($matchRuleConfigs); ?>;
    const persistedCourtAssignments = <?php echo json_encode($courtAssignments); ?>;
    const courtAssignmentCategory = document.getElementById('courtAssignmentCategory');
    const courtAssignmentCourts = document.getElementById('courtAssignmentCourts');
    const saveCourtAssignmentButton = document.getElementById('saveCourtAssignment');
    const courtAssignmentList = document.getElementById('courtAssignmentList');
    const defaultMatchRules = {
        GROUP: { sets: 1, deuce: true, deuceLimit: '21' },
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
        const deuceLimit = ['21', '25', '30'].includes(String(rules.deuceLimit))
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
                deuceLimit: deuce && limitSelect && ['21', '25', '30'].includes(limitSelect.value) ? limitSelect.value : 'NA'
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
        checkPairingEligibility('QUARTER_FINAL').then(function () {
            const modalTitle = document.getElementById('spinWheelModalLabel');
            if (modalTitle) { modalTitle.textContent = 'Spin Quarter Final Groups'; }
            if (window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(document.getElementById('spinWheelModal')).show(); }
            updateQuarterGroupPairInputs([], []);
            window.loadSpinItems(allowedQuarterGroups, function (winners, remaining) {
                updateQuarterGroupPairInputs(winners, remaining);
                const spinsNeeded = Math.ceil(allowedQuarterGroups.length / 2);
                if (winners.length === spinsNeeded && winners.length + remaining.length === allowedQuarterGroups.length) {
                    saveKnockoutPairings('QUARTER_FINAL', winners.concat(remaining));
                }
            });
        }).catch(showRequestError);
    }

    function showRequestError(error) {
        alert(error.message || 'Unable to complete this request.');
    }

    function checkPairingEligibility(stage) {
        if (typeof window.loadSpinItems !== 'function') {
            return Promise.reject(new Error('The spin wheel is unavailable.'));
        }
        const formData = new FormData();
        formData.append('action', 'check_pairing_eligibility');
        formData.append('stage', stage);
        if (stage === 'SEMI_FINAL' && directSemiFinal) {
            formData.append('direct_semi', '1');
        }
        return fetch(battleTournamentEndpoint, { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) { throw new Error(data.message || 'This draw is not available yet.'); }
            });
    }

    function saveKnockoutPairings(stage, pairs) {
        const formData = new FormData();
        formData.append('action', 'save_knockout_pairings');
        formData.append('stage', stage);
        formData.append('pairs', JSON.stringify(pairs));
        if (stage === 'SEMI_FINAL' && directSemiFinal) {
            formData.append('direct_semi', '1');
        }
        return fetch(battleTournamentEndpoint, { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) { throw new Error(data.message || 'Unable to schedule this stage.'); }
                alert((stage === 'QUARTER_FINAL' ? 'Quarter-final' : 'Semi-final') + ' matches have been created in the Playoff Match Ledger.');
                window.location.reload();
            }).catch(showRequestError);
    }

    function updateSemiFinalPairInputs(winners, remaining) {
        const selected = (winners || []).concat(remaining || []);
        document.querySelectorAll('.semi-team-input').forEach(function (input) {
            const slot = parseInt(input.dataset.slot || '0', 10);
            const team = selected[slot];
            input.value = team ? team.name : '';
        });
    }

    function semiFinalEligibleTeams() {
        return directSemiFinal ? semiFinalEligibleOptions : quarterFinalWinnerOptions;
    }

    function restoreSemiFinalPairInputs() {
        if (semiFinalScheduledTeams.length === 4) {
            updateSemiFinalPairInputs(semiFinalScheduledTeams, []);
            return;
        }
        if (savedSemiFinalPairing.length !== 4) {
            return;
        }
        const teamById = {};
        semiFinalEligibleTeams().forEach(function (team) {
            teamById[String(team.id)] = team;
        });
        const selected = savedSemiFinalPairing.map(function (id) {
            return teamById[String(id)];
        }).filter(Boolean);
        if (selected.length === 4) {
            updateSemiFinalPairInputs(selected, []);
        }
    }

    function openSemiFinalSpin() {
        if (semiFinalIsScheduled) {
            return;
        }
        checkPairingEligibility('SEMI_FINAL').then(function () {
            const modalTitle = document.getElementById('spinWheelModalLabel');
            if (modalTitle) { modalTitle.textContent = 'Spin Semi Final Teams'; }
            if (window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(document.getElementById('spinWheelModal')).show(); }
            const eligibleTeams = semiFinalEligibleTeams();
            const teams = eligibleTeams.map(function (team) {
                return { value: String(team.id), label: team.name };
            });
            const teamById = {};
            eligibleTeams.forEach(function (team) { teamById[String(team.id)] = team; });
            updateSemiFinalPairInputs([], []);
            window.loadSpinItems(teams, function (winners, remaining) {
                const selected = winners.concat(remaining).map(function (id) { return teamById[String(id)]; }).filter(Boolean);
                updateSemiFinalPairInputs(selected.slice(0, winners.length), selected.slice(winners.length));
                if (winners.length === 2 && winners.length + remaining.length === 4) {
                    saveKnockoutPairings('SEMI_FINAL', winners.concat(remaining).map(Number));
                }
            });
        }).catch(showRequestError);
    }

    applyStoredSetConfig();
    applyCourtAssignmentSelection();
    renderCourtAssignmentList();
    restoreQuarterGroupPairInputs();
    restoreSemiFinalPairInputs();

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

    if (spinSemiFinalTeamsButton) {
        spinSemiFinalTeamsButton.addEventListener('click', openSemiFinalSpin);
    }

    if (saveTournamentParametersButton) {
        saveTournamentParametersButton.addEventListener('click', function () {
            const groups = Math.max(1, Math.min(6, parseInt(groupsRequiredInput.value, 10) || 1));
            const advancers = Math.max(1, Math.min(6, parseInt(advancersPerGroupInput.value, 10) || 1));
            const formData = new FormData();
            formData.append('action', 'save_tournament_parameters');
            formData.append('groups_required', groups);
            formData.append('advancers_per_group', advancers);
            saveTournamentParametersButton.disabled = true;
            fetch(battleTournamentEndpoint, { method: 'POST', body: formData })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) { throw new Error(data.message || 'Unable to save tournament parameters.'); }
                    window.location.reload();
                }).catch(showRequestError)
                .finally(function () { saveTournamentParametersButton.disabled = false; });
        });
    }

    groupSelects.forEach(function (select) {
        function warnTournamentLocked(event) {
            if (!tournamentStarted || !select.dataset.tournamentLocked) {
                return;
            }
            event.preventDefault();
            alert('Tournament has started. You cannot change team groups now.');
        }
        select.addEventListener('pointerdown', warnTournamentLocked);
        select.addEventListener('keydown', warnTournamentLocked);
        select.addEventListener('change', function () {
            if (tournamentStarted || this.dataset.tournamentLocked) {
                alert('Tournament has started. You cannot change team groups now.');
                return;
            }
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
