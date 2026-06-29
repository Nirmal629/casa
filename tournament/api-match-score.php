<?php
header('Content-Type: application/json');

function jsonResponse(bool $success, array $payload = []): void
{
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

function fetchMatch(PDO $pdo, int $matchId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM to_matches WHERE ID = :match_id");
    $stmt->execute([':match_id' => $matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    return $match ?: null;
}

function recalculateGroupStandings(PDO $pdo, int $tournamentId, string $groupName): void
{
    $teamsStmt = $pdo->prepare("
        SELECT TEAM_ID
        FROM to_standings
        WHERE TOURNAMENT_ID = :tournament_id
          AND STAGE = 'GROUP'
          AND GROUP_NAME = :group_name
    ");
    $teamsStmt->execute([
        ':tournament_id' => $tournamentId,
        ':group_name' => $groupName
    ]);

    $rows = [];
    foreach ($teamsStmt->fetchAll(PDO::FETCH_COLUMN) as $teamId) {
        $rows[(int)$teamId] = [
            'played' => 0,
            'won' => 0,
            'lost' => 0,
            'points' => 0,
            'score_for' => 0,
            'score_against' => 0,
            'score_diff' => 0,
        ];
    }

    $matchesStmt = $pdo->prepare("
        SELECT TEAM_1_ID, TEAM_2_ID, TEAM_1_SCORE, TEAM_2_SCORE, WINNER_TEAM_ID
        FROM to_matches
        WHERE TOURNAMENT_ID = :tournament_id
          AND STAGE = 'GROUP'
          AND GROUP_NAME = :group_name
          AND STATUS = 'COMPLETED'
    ");
    $matchesStmt->execute([
        ':tournament_id' => $tournamentId,
        ':group_name' => $groupName
    ]);

    foreach ($matchesStmt->fetchAll(PDO::FETCH_ASSOC) as $match) {
        $team1Id = (int)$match['TEAM_1_ID'];
        $team2Id = (int)$match['TEAM_2_ID'];
        if (!isset($rows[$team1Id], $rows[$team2Id])) {
            continue;
        }

        $team1Score = (int)$match['TEAM_1_SCORE'];
        $team2Score = (int)$match['TEAM_2_SCORE'];
        $winnerId = (int)$match['WINNER_TEAM_ID'];

        $rows[$team1Id]['played']++;
        $rows[$team2Id]['played']++;
        $rows[$team1Id]['score_for'] += $team1Score;
        $rows[$team1Id]['score_against'] += $team2Score;
        $rows[$team2Id]['score_for'] += $team2Score;
        $rows[$team2Id]['score_against'] += $team1Score;

        if ($winnerId === $team1Id) {
            $rows[$team1Id]['won']++;
            $rows[$team1Id]['points'] += 2;
            $rows[$team2Id]['lost']++;
        } elseif ($winnerId === $team2Id) {
            $rows[$team2Id]['won']++;
            $rows[$team2Id]['points'] += 2;
            $rows[$team1Id]['lost']++;
        }
    }

    $hasCompletedMatches = false;
    foreach ($rows as $row) {
        if ($row['played'] > 0) {
            $hasCompletedMatches = true;
            break;
        }
    }

    foreach ($rows as $teamId => $row) {
        $rows[$teamId]['score_diff'] = $row['score_for'] - $row['score_against'];
    }

    uasort($rows, function (array $a, array $b): int {
        return [$b['points'], $b['score_diff'], $b['score_for'], $a['score_against']]
            <=> [$a['points'], $a['score_diff'], $a['score_for'], $b['score_against']];
    });

    $rank = 1;
    $update = $pdo->prepare("
        UPDATE to_standings
        SET PLAYED = :played,
            WON = :won,
            LOST = :lost,
            POINTS = :points,
            SCORE_FOR = :score_for,
            SCORE_AGAINST = :score_against,
            SCORE_DIFF = :score_diff,
            RANK_NO = :rank_no
        WHERE TOURNAMENT_ID = :tournament_id
          AND STAGE = 'GROUP'
          AND GROUP_NAME = :group_name
          AND TEAM_ID = :team_id
    ");

    foreach ($rows as $teamId => $row) {
        $update->execute([
            ':played' => $row['played'],
            ':won' => $row['won'],
            ':lost' => $row['lost'],
            ':points' => $row['points'],
            ':score_for' => $row['score_for'],
            ':score_against' => $row['score_against'],
            ':score_diff' => $row['score_diff'],
            ':rank_no' => $hasCompletedMatches ? $rank++ : 0,
            ':tournament_id' => $tournamentId,
            ':group_name' => $groupName,
            ':team_id' => $teamId
        ]);
    }
}

function stageMatchCount(string $stage): int
{
    return $stage === 'QUARTER_FINAL' ? 4 : ($stage === 'SEMI_FINAL' ? 2 : 1);
}

function createStageMatches(PDO $pdo, int $tournamentId, string $stage, array $teamIds): void
{
    if (count($teamIds) < stageMatchCount($stage) * 2) {
        return;
    }

    $exists = $pdo->prepare("SELECT COUNT(*) FROM to_matches WHERE TOURNAMENT_ID = :tournament_id AND STAGE = :stage");
    $exists->execute([
        ':tournament_id' => $tournamentId,
        ':stage' => $stage
    ]);
    if ((int)$exists->fetchColumn() > 0) {
        return;
    }

    $insert = $pdo->prepare("
        INSERT INTO to_matches
            (TOURNAMENT_ID, STAGE, GROUP_NAME, COURT_ID, ROUND_NO, MATCH_ORDER, TEAM_1_ID, TEAM_2_ID, TEAM_1_SCORE, TEAM_2_SCORE, STATUS)
        VALUES
            (:tournament_id, :stage, NULL, NULL, 1, :match_order, :team_1_id, :team_2_id, 0, 0, 'PENDING')
    ");

    for ($i = 0, $matchOrder = 1; $i < count($teamIds); $i += 2, $matchOrder++) {
        $insert->execute([
            ':tournament_id' => $tournamentId,
            ':stage' => $stage,
            ':match_order' => $matchOrder,
            ':team_1_id' => (int)$teamIds[$i],
            ':team_2_id' => (int)$teamIds[$i + 1]
        ]);
    }
}

function maybeCreateNextStage(PDO $pdo, int $tournamentId, string $completedStage): void
{
    if ($completedStage === 'GROUP') {
        $pending = $pdo->prepare("
            SELECT COUNT(*)
            FROM to_matches
            WHERE TOURNAMENT_ID = :tournament_id
              AND STAGE = 'GROUP'
              AND STATUS <> 'COMPLETED'
        ");
        $pending->execute([':tournament_id' => $tournamentId]);
        if ((int)$pending->fetchColumn() > 0) {
            return;
        }

        $topStmt = $pdo->prepare("
            SELECT TEAM_ID
            FROM to_standings
            WHERE TOURNAMENT_ID = :tournament_id
              AND STAGE = 'GROUP'
            ORDER BY POINTS DESC, SCORE_DIFF DESC, SCORE_FOR DESC, SCORE_AGAINST ASC, RANK_NO ASC
            LIMIT 8
        ");
        $topStmt->execute([':tournament_id' => $tournamentId]);
        $seeded = array_map('intval', $topStmt->fetchAll(PDO::FETCH_COLUMN));
        if (count($seeded) < 8) {
            return;
        }

        createStageMatches($pdo, $tournamentId, 'QUARTER_FINAL', [
            $seeded[0], $seeded[7],
            $seeded[3], $seeded[4],
            $seeded[1], $seeded[6],
            $seeded[2], $seeded[5],
        ]);
        return;
    }

    $nextStage = $completedStage === 'QUARTER_FINAL' ? 'SEMI_FINAL' : ($completedStage === 'SEMI_FINAL' ? 'FINAL' : '');
    if ($nextStage === '') {
        return;
    }

    $expected = stageMatchCount($completedStage);
    $winnerStmt = $pdo->prepare("
        SELECT WINNER_TEAM_ID
        FROM to_matches
        WHERE TOURNAMENT_ID = :tournament_id
          AND STAGE = :stage
          AND STATUS = 'COMPLETED'
          AND WINNER_TEAM_ID IS NOT NULL
        ORDER BY MATCH_ORDER, ID
    ");
    $winnerStmt->execute([
        ':tournament_id' => $tournamentId,
        ':stage' => $completedStage
    ]);
    $winners = array_map('intval', $winnerStmt->fetchAll(PDO::FETCH_COLUMN));
    if (count($winners) !== $expected) {
        return;
    }

    createStageMatches($pdo, $tournamentId, $nextStage, $winners);
}

try {
    include_once __DIR__ . '/../dbConnection_PDO.php';
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['start_match', 'record_point', 'undo_point'], true)) {
        jsonResponse(false, ['message' => 'Invalid action.']);
    }

    $matchId = (int)($_POST['match_id'] ?? 0);
    $match = fetchMatch($pdo, $matchId);
    if (!$match) {
        jsonResponse(false, ['message' => 'Match not found.']);
    }
    if ($action === 'record_point' && ($match['STATUS'] ?? '') === 'COMPLETED') {
        jsonResponse(false, ['message' => 'This match is already completed.']);
    }

    if ($action === 'start_match') {
        if (($match['STATUS'] ?? '') === 'COMPLETED') {
            jsonResponse(false, ['message' => 'This match is already completed.']);
        }

        $startStmt = $pdo->prepare("
            UPDATE to_matches
            SET STATUS = 'RUNNING'
            WHERE ID = :match_id
        ");
        $startStmt->execute([':match_id' => $matchId]);

        jsonResponse(true, ['status' => 'RUNNING']);
    }

    if ($action === 'undo_point') {
        $team1Score = max(0, (int)($_POST['team_1_score'] ?? 0));
        $team2Score = max(0, (int)($_POST['team_2_score'] ?? 0));
        $tournamentId = (int)$match['TOURNAMENT_ID'];

        $pdo->beginTransaction();

        $lastRallyStmt = $pdo->prepare("
            SELECT RALLY_NO
            FROM to_match_rally_logs
            WHERE MATCH_ID = :match_id
            ORDER BY RALLY_NO DESC
            LIMIT 1
        ");
        $lastRallyStmt->execute([':match_id' => $matchId]);
        $lastRallyNo = $lastRallyStmt->fetchColumn();
        if ($lastRallyNo === false) {
            $pdo->rollBack();
            jsonResponse(false, ['message' => 'No point to undo.']);
        }

        $deleteLog = $pdo->prepare("
            DELETE FROM to_match_rally_logs
            WHERE MATCH_ID = :match_id
              AND RALLY_NO = :rally_no
            LIMIT 1
        ");
        $deleteLog->execute([
            ':match_id' => $matchId,
            ':rally_no' => (int)$lastRallyNo
        ]);

        $updateMatch = $pdo->prepare("
            UPDATE to_matches
            SET TEAM_1_SCORE = :team_1_score,
                TEAM_2_SCORE = :team_2_score,
                WINNER_TEAM_ID = NULL,
                STATUS = 'RUNNING'
            WHERE ID = :match_id
        ");
        $updateMatch->execute([
            ':team_1_score' => $team1Score,
            ':team_2_score' => $team2Score,
            ':match_id' => $matchId
        ]);

        if (($match['STAGE'] ?? '') === 'GROUP' && !empty($match['GROUP_NAME'])) {
            recalculateGroupStandings($pdo, $tournamentId, (string)$match['GROUP_NAME']);
        }

        $pdo->commit();

        jsonResponse(true, [
            'status' => 'RUNNING',
            'undone_rally_no' => (int)$lastRallyNo
        ]);
    }

    $scoreSide = $_POST['score_side'] === 'right' ? 'right' : 'left';
    $setNo = max(1, (int)($_POST['set_no'] ?? 1));
    $team1Score = max(0, (int)($_POST['team_1_score'] ?? 0));
    $team2Score = max(0, (int)($_POST['team_2_score'] ?? 0));
    $team1Sets = max(0, (int)($_POST['team_1_sets'] ?? 0));
    $team2Sets = max(0, (int)($_POST['team_2_sets'] ?? 0));
    $isCompleted = (int)($_POST['completed'] ?? 0) === 1;
    $tournamentId = (int)$match['TOURNAMENT_ID'];
    $scoringTeamId = $scoreSide === 'left' ? (int)$match['TEAM_1_ID'] : (int)$match['TEAM_2_ID'];
    $postedServingTeamId = (int)($_POST['serving_team_id'] ?? 0);
    $servingTeamId = in_array($postedServingTeamId, [(int)$match['TEAM_1_ID'], (int)$match['TEAM_2_ID']], true)
        ? $postedServingTeamId
        : $scoringTeamId;
    $serverUserId = max(0, (int)($_POST['server_user_id'] ?? 0));
    $serverName = trim((string)($_POST['server_name'] ?? ''));
    $courtSide = strtoupper(trim((string)($_POST['court_side'] ?? '')));
    $courtSide = in_array($courtSide, ['LEFT', 'RIGHT'], true) ? $courtSide : ($scoreSide === 'left' ? 'LEFT' : 'RIGHT');
    $notes = trim((string)($_POST['notes'] ?? ''));
    $winnerTeamId = null;

    if ($isCompleted) {
        $winnerTeamId = $team1Sets > $team2Sets ? (int)$match['TEAM_1_ID'] : (int)$match['TEAM_2_ID'];
    }

    $pdo->beginTransaction();

    $rallyStmt = $pdo->prepare("
        SELECT COALESCE(MAX(RALLY_NO), 0) + 1
        FROM to_match_rally_logs
        WHERE MATCH_ID = :match_id
    ");
    $rallyStmt->execute([':match_id' => $matchId]);
    $rallyNo = (int)$rallyStmt->fetchColumn();

    $logStmt = $pdo->prepare("
        INSERT INTO to_match_rally_logs
            (MATCH_ID, TOURNAMENT_ID, SET_NO, RALLY_NO, SERVING_TEAM_ID, SCORING_TEAM_ID, SERVER_USER_ID, RECEIVER_USER_ID, TEAM_1_SCORE, TEAM_2_SCORE, EVENT_TYPE, COURT_SIDE, NOTES)
        VALUES
            (:match_id, :tournament_id, :set_no, :rally_no, :serving_team_id, :scoring_team_id, :server_user_id, NULL, :team_1_score, :team_2_score, :event_type, :court_side, :notes)
    ");
    $logStmt->execute([
        ':match_id' => $matchId,
        ':tournament_id' => $tournamentId,
        ':set_no' => $setNo,
        ':rally_no' => $rallyNo,
        ':serving_team_id' => $servingTeamId,
        ':scoring_team_id' => $scoringTeamId,
        ':server_user_id' => $serverUserId > 0 ? $serverUserId : null,
        ':team_1_score' => $team1Score,
        ':team_2_score' => $team2Score,
        ':event_type' => 'POINT',
        ':court_side' => $courtSide,
        ':notes' => trim(($serverName !== '' ? 'Server: ' . $serverName . '. ' : '') . $notes . ($isCompleted ? ' Match completed.' : '')) ?: null
    ]);

    $updateMatch = $pdo->prepare("
        UPDATE to_matches
        SET TEAM_1_SCORE = :team_1_score,
            TEAM_2_SCORE = :team_2_score,
            WINNER_TEAM_ID = :winner_team_id,
            STATUS = :status
        WHERE ID = :match_id
    ");
    $updateMatch->execute([
        ':team_1_score' => $isCompleted ? $team1Sets : $team1Score,
        ':team_2_score' => $isCompleted ? $team2Sets : $team2Score,
        ':winner_team_id' => $winnerTeamId,
        ':status' => $isCompleted ? 'COMPLETED' : 'RUNNING',
        ':match_id' => $matchId
    ]);

    if ($isCompleted && $match['STAGE'] === 'GROUP' && !empty($match['GROUP_NAME'])) {
        recalculateGroupStandings($pdo, $tournamentId, (string)$match['GROUP_NAME']);
    }
    if ($isCompleted) {
        maybeCreateNextStage($pdo, $tournamentId, (string)$match['STAGE']);
    }

    $pdo->commit();

    jsonResponse(true, [
        'rally_no' => $rallyNo,
        'status' => $isCompleted ? 'COMPLETED' : 'RUNNING',
        'winner_team_id' => $winnerTeamId,
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, ['message' => $e->getMessage()]);
}
