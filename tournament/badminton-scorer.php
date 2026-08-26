<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$matchData = null;
$tournamentHostId = 0;
$canManage = false;
$team1Players = [];
$team2Players = [];
$team1PlayerRows = [];
$team2PlayerRows = [];
$rallyLogs = [];
$completedSetScores = [];
$matchRuleConfigs = [];

try {
    include_once __DIR__ . '/../dbConnection_PDO.php';
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($matchId > 0) {
        $matchStmt = $pdo->prepare("
            SELECT
                m.*,
                t1.NAME AS TEAM_1_NAME,
                t2.NAME AS TEAM_2_NAME,
                (SELECT GROUP_CONCAT(CONCAT(u.ID, ':', TRIM(u.NAME)) ORDER BY u.ID SEPARATOR '||') FROM to_users u WHERE u.TEAM_ID = t1.ID AND TRIM(COALESCE(u.NAME, '')) <> '') AS TEAM_1_PLAYERS,
                (SELECT GROUP_CONCAT(CONCAT(u.ID, ':', TRIM(u.NAME)) ORDER BY u.ID SEPARATOR '||') FROM to_users u WHERE u.TEAM_ID = t2.ID AND TRIM(COALESCE(u.NAME, '')) <> '') AS TEAM_2_PLAYERS
            FROM to_matches m
            INNER JOIN to_teams t1 ON t1.ID = m.TEAM_1_ID
            INNER JOIN to_teams t2 ON t2.ID = m.TEAM_2_ID
            WHERE m.ID = :match_id
        ");
        $matchStmt->execute([':match_id' => $matchId]);
        $matchData = $matchStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($matchData) {
            $tournamentId = (int)$matchData['TOURNAMENT_ID'];

            // Only the organizer (to_tournaments.HOST_ID == logged-in ca_users.ID) can score this match.
            $hostStmt = $pdo->prepare("SELECT HOST_ID FROM to_tournaments WHERE ID = :tournament_id LIMIT 1");
            $hostStmt->execute([':tournament_id' => $tournamentId]);
            $tournamentHostId = (int)$hostStmt->fetchColumn();
            $canManage = !empty($_SESSION['user_id'])
                && $tournamentHostId > 0
                && (int)$_SESSION['user_id'] === $tournamentHostId;

            // Role-based access: a Player is always read-only, even if id checks pass.
            // App stores the role in $_SESSION['usertype'] ('Host' | 'Trainer' | 'Player').
            $sessionRole = strtolower(trim((string)($_SESSION['usertype'] ?? $_SESSION['role'] ?? '')));
            if ($sessionRole === 'player') {
                $canManage = false;
            }

            foreach (array_values(array_filter(explode('||', $matchData['TEAM_1_PLAYERS'] ?? ''))) as $playerRow) {
                $playerParts = array_pad(explode(':', $playerRow, 2), 2, '');
                $playerId = $playerParts[0];
                $playerName = trim($playerParts[1]);
                if ($playerName === '') {
                    continue;
                }
                $team1PlayerRows[] = ['id' => (int)$playerId, 'name' => $playerName];
                $team1Players[] = $playerName;
            }
            foreach (array_values(array_filter(explode('||', $matchData['TEAM_2_PLAYERS'] ?? ''))) as $playerRow) {
                $playerParts = array_pad(explode(':', $playerRow, 2), 2, '');
                $playerId = $playerParts[0];
                $playerName = trim($playerParts[1]);
                if ($playerName === '') {
                    continue;
                }
                $team2PlayerRows[] = ['id' => (int)$playerId, 'name' => $playerName];
                $team2Players[] = $playerName;
            }

            $logStmt = $pdo->prepare("
                SELECT
                    l.*,
                    scoringTeam.NAME AS SCORING_TEAM_NAME,
                    servingTeam.NAME AS SERVING_TEAM_NAME
                FROM to_match_rally_logs l
                LEFT JOIN to_teams scoringTeam ON scoringTeam.ID = l.SCORING_TEAM_ID
                LEFT JOIN to_teams servingTeam ON servingTeam.ID = l.SERVING_TEAM_ID
                WHERE l.MATCH_ID = :match_id
                ORDER BY l.SET_NO, l.RALLY_NO, l.ID
            ");
            $logStmt->execute([':match_id' => $matchId]);
            $rallyLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

            // Reconstruct final score per set (highest score reached in each set) for completed matches.
            foreach ($rallyLogs as $log) {
                $setNo = (int)$log['SET_NO'];
                $team1 = (int)$log['TEAM_1_SCORE'];
                $team2 = (int)$log['TEAM_2_SCORE'];
                if (!isset($completedSetScores[$setNo])) {
                    $completedSetScores[$setNo] = ['team1' => 0, 'team2' => 0];
                }
                $completedSetScores[$setNo]['team1'] = max($completedSetScores[$setNo]['team1'], $team1);
                $completedSetScores[$setNo]['team2'] = max($completedSetScores[$setNo]['team2'], $team2);
            }
            ksort($completedSetScores);

            try {
                $rulesStmt = $pdo->prepare("SELECT STAGE, GAME_SETS, DEUCE_ENABLED, DEUCE_LIMIT
                    FROM to_tournament_match_rules WHERE TOURNAMENT_ID = :tournament_id");
                $rulesStmt->execute([':tournament_id' => $tournamentId]);
                foreach ($rulesStmt->fetchAll(PDO::FETCH_ASSOC) as $storedRule) {
                    $stage = (string)$storedRule['STAGE'];
                    $deuceEnabled = (int)$storedRule['DEUCE_ENABLED'] === 1;
                    $matchRuleConfigs[$stage] = [
                        'sets' => (int)$storedRule['GAME_SETS'] === 1 ? 1 : 3,
                        'deuce' => $deuceEnabled,
                        'deuceLimit' => $deuceEnabled && in_array((int)$storedRule['DEUCE_LIMIT'], [25, 30], true)
                            ? (int)$storedRule['DEUCE_LIMIT']
                            : null,
                    ];
                }
            } catch (Exception $ignored) {
                // The scorer safely uses its stage defaults until match rules are first saved.
            }
        }
    }
} catch (Exception $e) {
    $matchData = null;
}

$team1Player1 = trim($team1Players[0] ?? '') !== '' ? $team1Players[0] : ($matchData['TEAM_1_NAME'] ?? 'PLAYER NAME');
$team1Player2 = trim($team1Players[1] ?? '') !== '' ? $team1Players[1] : 'PLAYER NAME';
$team2Player1 = trim($team2Players[0] ?? '') !== '' ? $team2Players[0] : ($matchData['TEAM_2_NAME'] ?? 'PLAYER NAME');
$team2Player2 = trim($team2Players[1] ?? '') !== '' ? $team2Players[1] : 'PLAYER NAME';
$team1PlayerRows = array_replace([
    ['id' => 0, 'name' => $team1Player1],
    ['id' => 0, 'name' => $team1Player2],
], array_slice($team1PlayerRows, 0, 2));
$team2PlayerRows = array_replace([
    ['id' => 0, 'name' => $team2Player1],
    ['id' => 0, 'name' => $team2Player2],
], array_slice($team2PlayerRows, 0, 2));
$initialTeam1Score = ($matchData && ($matchData['STATUS'] ?? '') === 'RUNNING') ? (int)$matchData['TEAM_1_SCORE'] : 0;
$initialTeam2Score = ($matchData && ($matchData['STATUS'] ?? '') === 'RUNNING') ? (int)$matchData['TEAM_2_SCORE'] : 0;

// Completed-match result (for the Match Configuration modal).
$matchStatus = $matchData['STATUS'] ?? 'PENDING';
$isMatchCompleted = ($matchStatus === 'COMPLETED');
$team1SetsWon = $isMatchCompleted ? (int)($matchData['TEAM_1_SCORE'] ?? 0) : 0;
$team2SetsWon = $isMatchCompleted ? (int)($matchData['TEAM_2_SCORE'] ?? 0) : 0;
$winnerTeamId = (int)($matchData['WINNER_TEAM_ID'] ?? 0);
$team1IsWinner = $isMatchCompleted && $winnerTeamId > 0 && $winnerTeamId === (int)($matchData['TEAM_1_ID'] ?? 0);
$team2IsWinner = $isMatchCompleted && $winnerTeamId > 0 && $winnerTeamId === (int)($matchData['TEAM_2_ID'] ?? 0);
$winnerTeamName = $team1IsWinner
    ? ($matchData['TEAM_1_NAME'] ?? 'Team 1')
    : ($team2IsWinner ? ($matchData['TEAM_2_NAME'] ?? 'Team 2') : '');
$initialSetScores = [['a' => $initialTeam1Score, 'b' => $initialTeam2Score], ['a' => 0, 'b' => 0], ['a' => 0, 'b' => 0]];
$initialSetNo = 1;
if (!empty($completedSetScores)) {
    $initialSetNo = max(1, min(3, max(array_keys($completedSetScores))));
    foreach ($completedSetScores as $setNo => $setScore) {
        if ($setNo >= 1 && $setNo <= 3) {
            $initialSetScores[$setNo - 1] = ['a' => (int)$setScore['team1'], 'b' => (int)$setScore['team2']];
        }
    }
}
?>
<!-----Header------>
<?php include "includes/scorer-header.php"; ?>


<!-----body-top---->
<section class="container-fluid" style="position: relative; display: flex; align-items: center; justify-content: space-around; padding: 0 15px; gap: 20px;">
    <!----left-action---->
    <div class="left-action-container">
        <a href="court-dashboard.php?id=<?php echo (int)$tournamentId; ?>&group=<?php echo urlencode($matchData['GROUP_NAME'] ?? ''); ?>" class="scorerback_btn btn btn-outline-info"><i class="fa-solid fa-arrow-left mr-1"></i></a>
        <div class="gameTimebox btn btn-outline-info">42 min</div>
    </div>

    <!----score-board---->
    <div class="score-board-container">
        <div class="player-score-board-container">
            <div class="scoreboard-side-score" id="team-a-match-score">0</div>
            <div class="team-a-players">
                <i id="team-a-winner-crown" class="fa-solid fa-crown scorer-winner-crown" title="Match winner" aria-label="Match winner"></i>
                <p id="team-a-names" class="m-0"><?php echo htmlspecialchars($team1Player1); ?><br><?php echo htmlspecialchars($team1Player2); ?></p>
            </div>

            <div class="scoreboard-sets">
                <div class="scoreboard-set-row" data-set-row="1">
                    <span class="scoreboard-shuttle"></span>
                    <span class="scoreboard-set-score">
                        <span id="team-a-set-one"><?php echo (int)$initialTeam1Score; ?></span> - <span id="team-b-set-one"><?php echo (int)$initialTeam2Score; ?></span>
                    </span>
                    <span class="scoreboard-shuttle"></span>
                </div>
                <div class="scoreboard-set-row" data-set-row="2">
                    <span class="scoreboard-shuttle"></span>
                    <span class="scoreboard-set-score">
                        <span id="team-a-set-two">0</span> - <span id="team-b-set-two">0</span>
                    </span>
                    <span class="scoreboard-shuttle"></span>
                </div>
                <div class="scoreboard-set-row" data-set-row="3">
                    <span class="scoreboard-shuttle"></span>
                    <span class="scoreboard-set-score">
                        <span id="team-a-set-three">0</span> - <span id="team-b-set-three">0</span>
                    </span>
                    <span class="scoreboard-shuttle"></span>
                </div>
            </div>

            <div class="team-b-players">
                <i id="team-b-winner-crown" class="fa-solid fa-crown scorer-winner-crown" title="Match winner" aria-label="Match winner"></i>
                <p id="team-b-names" class="m-0"><?php echo htmlspecialchars($team2Player1); ?><br><?php echo htmlspecialchars($team2Player2); ?></p>
            </div>
            <div class="scoreboard-side-score" id="team-b-match-score">0</div>
        </div>
    </div>

    <!----setup-menu--->
    <div class="setup-menu-container">
        <nav class="navbar navbar-expand-sm header-navbar">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link btn btn-outline-info" href="#" id="navSetupMenuDropdownMenuLink" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" id="menu-dropdown-list" aria-labelledby="navSetupMenuDropdownMenuLink">
                        <a class="dropdown-item" data-toggle="modal" data-target="#matchResult"><i class="fa-solid fa-sliders mr-2"></i>Match Setup</a>
                    </div>
                </li>
            </ul>
        </nav>

        <button type="button" class="btn btn-sm btn-outline-info scorer-log-toggle" data-toggle="modal" data-target="#matchLog" title="Match log">
            <i class="fa-solid fa-list-ul"></i>
        </button>

        <button type="button" id="voice-toggle" class="btn btn-sm btn-outline-info scorer-voice-toggle" title="Voice announcements">
            <i class="fa-solid fa-volume-high"></i>
        </button>
    </div>

</section>


<!-----main-body----->

<section class="mainscorer_board">
    <div class="container-fluid master-container">
        <div class="row container-row">
            <div class="col scorer-col">
                <?php if ($canManage): ?>
                <button class="score-style left-scorer" onclick="incrementScore('left')">+1</button>
                <?php endif; ?>
            </div>

            <div class="row court-row">
                <div class="col top-sideline">
                    <span id="left-court-team-name"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1 name'); ?></span>
                    <?php if ($canManage): ?>
                    <button type="button" id="undo-point" class="btn-link fw-bold">Undo</button>
                    <?php endif; ?>
                    <span id="right-court-team-name"><?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? 'Team 2 name'); ?></span>
                </div>
                <div class="col left-court-area">
                    <div class="col left-court-long-service-line"></div>
                    <div class="row left-court-left-service-area">
                        <div class="playerCard_box">
                            <img src="assets/images/Player/man1.png" alt="dropdown image"
                                class="img-responsive team-a-player-1-img">
                            <input type="text" class="form-control control-border team-player left-team-player"
                                name="team-a-player-1" data-form-field="team-a-player-1" id="team-a-player-1"
                                value="<?php echo htmlspecialchars($team1Player1); ?>" />
                        </div>
                        <img id="left-court-left-side-shuttle" class="left-court-shuttles" alt="left-court-shuttles"
                            src="assets/images/left-shuttle.png">
                    </div>

                    <div class="row left-court-right-service-area">
                        <div class="playerCard_box">
                            <img src="assets/images/Player/man2.png" alt="dropdown image"
                                class="img-responsive team-a-player-2-img">
                            <input type="text" class="form-control control-border team-player left-team-player"
                                name="team-a-player-2" data-form-field="team-a-player-2" id="team-a-player-2"
                                value="<?php echo htmlspecialchars($team1Player2); ?>" />
                        </div>
                        <img id="left-court-right-side-shuttle" class="left-court-shuttles"
                            alt="left-court-shuttles" src="assets/images/left-shuttle.png">
                    </div>
                </div>

                <div class="col left-net-area"></div>
                <div class="col right-net-area"></div>

                <div class="col right-court-area">
                    <div class="row right-court-right-service-area">
                        <div class="playerCard_box">
                            <img src="assets/images/Player/man3.png" alt="dropdown image"
                                class="img-responsive team-b-player-2-img">
                            <input type="text" class="form-control control-border team-player right-team-player"
                                name="team-b-player-2" data-form-field="team-b-player-2" id="team-b-player-2"
                                value="<?php echo htmlspecialchars($team2Player2); ?>" />
                        </div>
                        <img id="right-court-right-side-shuttle" class="right-court-shuttles"
                            alt="right-court-shuttles" src="assets/images/right-shuttle.png">
                    </div>

                    <div class="row right-court-left-service-area">
                        <div class="playerCard_box">
                            <img src="assets/images/Player/man4.png" alt="dropdown image"
                                class="img-responsive team-b-player-1-img">
                            <input type="text" class="form-control control-border team-player right-team-player"
                                name="team-b-player-1" data-form-field="team-b-player-1" id="team-b-player-1"
                                value="<?php echo htmlspecialchars($team2Player1); ?>" />
                        </div>
                        <img id="right-court-left-side-shuttle" class="right-court-shuttles"
                            alt="right-court-shuttles" src="assets/images/right-shuttle.png">
                    </div>

                    <div class="col right-court-long-service-line"></div>
                </div>
                <div class="col bottom-sideline">
                    <button type="button" class="Refree_btn">Refree</button>
                </div>
            </div>
            <div class="col scorer-col">
                <?php if ($canManage): ?>
                <button class="score-style right-scorer" onclick="incrementScore('right')">+1</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>


<!-- **** Modal for Match Configuration **** -->

<div class="modal fade" id="matchConfig" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="matchConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-info fw-bold text-uppercase small" id="matchConfigLabel"><i class="fa-solid fa-gears mr-2"></i>Match Configuration</h6>
                <div class="match-config-header-actions">
                    <button type="button" id="config-voice-toggle" class="btn btn-outline-info btn-sm match-config-icon-btn" title="Voice announcements">
                        <i class="fa-solid fa-volume-xmark"></i>
                    </button>
                    <button type="button" class="close match-config-icon-btn" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($isMatchCompleted): ?>
                    <div class="match-config-result" role="status">
                        <div class="match-config-result-head">
                            <i class="fa-solid fa-trophy"></i>
                            <span>Match Completed</span>
                        </div>
                        <div class="match-config-result-teams">
                            <div class="match-config-result-team <?php echo $team1IsWinner ? 'is-winner' : ''; ?>">
                                <?php if ($team1IsWinner): ?><i class="fa-solid fa-crown match-config-champion" title="Champion"></i><?php endif; ?>
                                <span class="match-config-result-name"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1'); ?></span>
                                <span class="match-config-result-sets"><?php echo (int)$team1SetsWon; ?></span>
                            </div>
                            <span class="match-config-result-vs">-</span>
                            <div class="match-config-result-team <?php echo $team2IsWinner ? 'is-winner' : ''; ?>">
                                <span class="match-config-result-sets"><?php echo (int)$team2SetsWon; ?></span>
                                <span class="match-config-result-name"><?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? 'Team 2'); ?></span>
                                <?php if ($team2IsWinner): ?><i class="fa-solid fa-crown match-config-champion" title="Champion"></i><?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($completedSetScores)): ?>
                            <div class="match-config-result-breakdown">
                                <?php foreach ($completedSetScores as $setNo => $setScore): ?>
                                    <div class="match-config-result-set">
                                        <span class="match-config-result-set-label">Set <?php echo (int)$setNo; ?></span>
                                        <span class="match-config-result-set-score">
                                            <span class="<?php echo $setScore['team1'] > $setScore['team2'] ? 'won' : ''; ?>"><?php echo (int)$setScore['team1']; ?></span>
                                            <span class="match-config-result-set-divider">|</span>
                                            <span class="<?php echo $setScore['team2'] > $setScore['team1'] ? 'won' : ''; ?>"><?php echo (int)$setScore['team2']; ?></span>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($winnerTeamName !== ''): ?>
                            <div class="match-config-result-winner">Winner: <strong><?php echo htmlspecialchars($winnerTeamName); ?></strong></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="match-config-panel mx-auto">
                    <div class="match-config-grid mb-4">
                        <input type="text" id="t1_name" class="match-config-input" placeholder="Team 1 Name" value="<?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? ''); ?>">
                        <button type="button" id="config-court-swap" class="btn btn-outline-info btn-sm match-config-icon-btn match-config-swap-btn" title="Swap court">
                            <i class="fa-solid fa-right-left"></i>
                        </button>
                        <input type="text" id="t2_name" class="match-config-input match-config-right" placeholder="Team 2 Name" value="<?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? ''); ?>">
                    </div>
                    <div class="match-config-grid mb-3">
                        <input type="text" id="t1_p1" class="match-config-input" placeholder="Player 1" value="<?php echo htmlspecialchars($team1Player1); ?>">
                        <span></span>
                        <input type="text" id="t2_p1" class="match-config-input match-config-right" placeholder="Player 1" value="<?php echo htmlspecialchars($team2Player1); ?>">

                        <button type="button" class="btn btn-outline-info btn-sm match-config-icon-btn match-config-side-swap match-config-swap-team-a" title="Swap team 1 players">
                            <i class="fa-solid fa-right-left"></i>
                        </button>
                        <span class="match-config-vs">VS</span>
                        <button type="button" class="btn btn-outline-info btn-sm match-config-icon-btn match-config-right-btn match-config-swap-team-b" title="Swap team 2 players">
                            <i class="fa-solid fa-right-left"></i>
                        </button>

                        <input type="text" id="t1_p2" class="match-config-input" placeholder="Player 2" value="<?php echo htmlspecialchars($team1Player2); ?>">
                        <span></span>
                        <input type="text" id="t2_p2" class="match-config-input match-config-right" placeholder="Player 2" value="<?php echo htmlspecialchars($team2Player2); ?>">
                    </div>

                    <select id="match_type" class="sr-only" disabled>
                        <option value="doubles">Doubles</option>
                    </select>
                    <select id="deuce_type" class="sr-only" disabled>
                        <option value="deuce">Deuce On</option>
                    </select>

                    <?php if ($canManage): ?>
                    <button class="match-config-save-btn" type="button" onclick="startPlayableMatch()">
                        SAVE
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- **** Modal for Match Setup **** -->
<div class="modal fade" id="matchResult" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="matchResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="match-result-header-left">
                    <h6 class="modal-title text-info fw-bold text-uppercase small mr-1" id="matchResultLabel"><i class="fa-solid fa-sliders mr-1"></i>Match Setup</h6>
                    <span class="match-result-mini-select">Doubles</span>
                    <span class="match-result-mini-select" id="match-setup-deuce">Deuce On</span>
                    <span class="match-result-set-indicator" id="match-result-set-indicator">Set - 1/3</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="match-result-panel mx-auto">
                    <div class="match-result-teams">
                        <div class="match-result-team">
                            <div class="match-result-team-name">
                                <span class="match-result-status-dot"></span>
                                <span id="match-result-team-a-name">Team 1</span>
                            </div>
                            <div class="match-result-score-line">
                                <span class="match-result-set-count" id="match-result-team-a-sets">0</span>
                                <i id="match-result-team-a-crown" class="fa-solid fa-crown match-result-trophy" title="Match winner"></i>
                            </div>
                            <div class="match-result-winner-name" id="match-result-team-a-winner-name">-</div>
                        </div>
                        <div class="match-result-versus">-</div>
                        <div class="match-result-team">
                            <div class="match-result-team-name">
                                <span class="match-result-status-dot muted"></span>
                                <span id="match-result-team-b-name">Team 2</span>
                            </div>
                            <div class="match-result-score-line">
                                <span class="match-result-set-count" id="match-result-team-b-sets">0</span>
                                <i id="match-result-team-b-crown" class="fa-solid fa-crown match-result-trophy" title="Match winner"></i>
                            </div>
                            <div class="match-result-winner-name" id="match-result-team-b-winner-name">-</div>
                        </div>
                    </div>
                    <div class="match-result-summary sr-only" id="match-result-winner">WINNER: -</div>
                    <div class="match-result-set-breakdown" id="match-result-set-breakdown">
                        <div class="match-result-set-row">
                            <span class="match-result-set-label">SET 1</span>
                            <div class="match-result-set-score">
                                <span>0</span><span class="match-result-divider">|</span><span>0</span>
                            </div>
                            <div class="match-result-set-actions">
                                <?php if ($canManage): ?>
                                <button type="button" onclick="startPlayableMatch()">Start</button>
                                <?php endif; ?>
                            </div>
                            <i class="fa-solid fa-hourglass-half match-result-set-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- **** Modal for Set Score Board **** -->

<div class="modal fade" id="setScoreBoard" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="setScoreBoardLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-info fw-bold text-uppercase small" id="setScoreBoardLabel"><i class="fa-regular fa-clipboard mr-2"></i>Set Score Board</h6>
                <button type="button" class="close match-config-icon-btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="set-board-panel mx-auto">
                    <div class="set-board-alert">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Saving a manual score will clear the game log.</span>
                    </div>

                    <div class="set-board-score-strip">
                        <div class="set-board-score-box">
                            <span id="set-board-score-a-label"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1'); ?></span>
                            <input type="number" id="set-board-score-a" class="set-board-score-input" min="0" max="99" value="<?php echo (int)$initialTeam1Score; ?>" readonly>
                        </div>
                        <div class="set-board-score-center">
                            <span>Current Score</span>
                            <strong id="set-board-live-score" class="set-board-live-score"><?php echo (int)$initialTeam1Score; ?> - <?php echo (int)$initialTeam2Score; ?></strong>
                        </div>
                        <div class="set-board-score-box">
                            <span id="set-board-score-b-label"><?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? 'Team 2'); ?></span>
                            <input type="number" id="set-board-score-b" class="set-board-score-input" min="0" max="99" value="<?php echo (int)$initialTeam2Score; ?>" readonly>
                        </div>
                    </div>
                    <?php if ($canManage): ?>
                    <div class="mt-2 text-center">
                        <label for="set-board-winner" class="small text-muted mb-1">Set winner (for manual score)</label>
                        <select id="set-board-winner" class="form-control form-control-sm mx-auto" style="max-width: 220px;">
                            <option value="">Choose winner</option>
                            <option value="A"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1'); ?></option>
                            <option value="B"><?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? 'Team 2'); ?></option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="set-board-court">
                        <div class="set-board-net"></div>
                        <div class="set-board-team set-board-team-left">
                            <div class="set-board-team-card">
                                <strong id="set-board-team-a-name"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1'); ?></strong>
                                <span id="set-board-team-a-players"><?php echo htmlspecialchars($team1Player1); ?><br><?php echo htmlspecialchars($team1Player2); ?></span>
                            </div>
                            <?php if ($canManage): ?>
                            <div class="set-board-actions">
                                <button type="button" class="set-board-action-btn" id="set-board-edit-a"><i class="fa-solid fa-pen"></i>Edit</button>
                                <button type="button" class="set-board-plus" id="set-board-plus-a">+1</button>
                                <button type="button" class="set-board-action-btn" id="set-board-save-a"><i class="fa-solid fa-check"></i>Save</button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="set-board-team set-board-team-right">
                            <div class="set-board-team-card">
                                <strong id="set-board-team-b-name"><?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? 'Team 2'); ?></strong>
                                <span id="set-board-team-b-players"><?php echo htmlspecialchars($team2Player1); ?><br><?php echo htmlspecialchars($team2Player2); ?></span>
                            </div>
                            <?php if ($canManage): ?>
                            <div class="set-board-actions">
                                <button type="button" class="set-board-action-btn" id="set-board-edit-b"><i class="fa-solid fa-pen"></i>Edit</button>
                                <button type="button" class="set-board-plus" id="set-board-plus-b">+1</button>
                                <button type="button" class="set-board-action-btn" id="set-board-save-b"><i class="fa-solid fa-check"></i>Save</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($canManage): ?>
                    <div class="set-board-footer-actions">
                        <button type="button" id="set-board-undo-point">Undo Last Point</button>
                        <button type="button" id="set-board-save-all" class="set-board-save-all">Save Score</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- **** Modal for Match Log **** -->
<div class="modal fade" id="matchLog" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="matchLogLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-info fw-bold text-uppercase small" id="matchLogLabel"><i class="fa-solid fa-list-ul mr-2"></i>Match Log</h6>
                <button type="button" class="close match-config-icon-btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="match-log-panel">
                    <div class="table-responsive">
                        <table class="match-log-table">
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
                                <?php if ($matchId <= 0): ?>
                                    <tr><td colspan="8">No match selected.</td></tr>
                                <?php elseif (empty($rallyLogs)): ?>
                                    <tr><td colspan="8">No rally log yet for this match.</td></tr>
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
            </div>
        </div>
    </div>
</div>


<style>
    /* Completed-match result inside Match Configuration modal */
    .match-config-result {
        margin: 0 auto 18px;
        max-width: 460px;
        padding: 14px 16px;
        border: 1px solid #d7e9ec;
        border-radius: 12px;
        background: #f4fbfc;
        text-align: center;
    }
    .match-config-result-head {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #17a2b8;
        margin-bottom: 12px;
    }
    .match-config-result-head i {
        color: #e0a90c;
    }
    .match-config-result-teams {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
    }
    .match-config-result-team {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #4a5568;
    }
    .match-config-result-team.is-winner {
        color: #1f2937;
    }
    .match-config-result-name {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .match-config-result-sets {
        min-width: 30px;
        height: 30px;
        line-height: 30px;
        border-radius: 8px;
        background: #e4eef0;
        font-weight: 700;
    }
    .match-config-result-team.is-winner .match-config-result-sets {
        background: #17a2b8;
        color: #fff;
    }
    .match-config-champion {
        color: #e0a90c;
        font-size: 1rem;
    }
    .match-config-result-vs {
        color: #98a2b3;
        font-weight: 700;
    }
    .match-config-result-breakdown {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
        margin-top: 12px;
    }
    .match-config-result-set {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 4px 10px;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .match-config-result-set-label {
        font-size: 0.6rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .match-config-result-set-score {
        font-weight: 700;
        color: #64748b;
    }
    .match-config-result-set-score .won {
        color: #17a2b8;
    }
    .match-config-result-set-divider {
        color: #cbd5e1;
        margin: 0 3px;
    }
    .match-config-result-winner {
        margin-top: 12px;
        font-size: 0.85rem;
        color: #475467;
    }

    /* Match Log modal table */
    .match-log-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .match-log-table th,
    .match-log-table td {
        padding: 7px 9px;
        border-bottom: 1px solid #edf1f4;
        text-align: left;
        white-space: nowrap;
    }
    .match-log-table thead th {
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #17a2b8;
        background: #f4fbfc;
    }
    .match-log-table tbody tr:hover {
        background: #f9fdfe;
    }
</style>


<!-----Footer------>
<script>
    window.initialMatchData = {
        matchId: <?php echo (int)$matchId; ?>,
        tournamentId: <?php echo (int)$tournamentId; ?>,
        stage: <?php echo json_encode($matchData['STAGE'] ?? ''); ?>,
        groupName: <?php echo json_encode($matchData['GROUP_NAME'] ?? ''); ?>,
        team1Id: <?php echo (int)($matchData['TEAM_1_ID'] ?? 0); ?>,
        team2Id: <?php echo (int)($matchData['TEAM_2_ID'] ?? 0); ?>,
        team1Name: <?php echo json_encode($matchData['TEAM_1_NAME'] ?? 'Team 1'); ?>,
        team2Name: <?php echo json_encode($matchData['TEAM_2_NAME'] ?? 'Team 2'); ?>,
        teamA: <?php echo json_encode([$team1Player1, $team1Player2]); ?>,
        teamB: <?php echo json_encode([$team2Player1, $team2Player2]); ?>,
        teamAPlayers: <?php echo json_encode($team1PlayerRows); ?>,
        teamBPlayers: <?php echo json_encode($team2PlayerRows); ?>,
        matchStatus: <?php echo json_encode($matchData['STATUS'] ?? 'PENDING'); ?>,
        winnerTeamId: <?php echo (int)$winnerTeamId; ?>,
        initialSetsA: <?php echo (int)$team1SetsWon; ?>,
        initialSetsB: <?php echo (int)$team2SetsWon; ?>,
        initialScoreA: <?php echo (int)$initialTeam1Score; ?>,
        initialScoreB: <?php echo (int)$initialTeam2Score; ?>,
        initialSetScores: <?php echo json_encode($initialSetScores); ?>,
        initialSetNo: <?php echo (int)$initialSetNo; ?>,
        canManage: <?php echo $canManage ? 'true' : 'false'; ?>,
        matchRules: <?php echo json_encode($matchRuleConfigs); ?>,
        defaultSetLimit: <?php echo (($matchData['STAGE'] ?? '') === 'GROUP') ? 1 : 3; ?>
    };
</script>
<?php include "includes/scorer-footer.php"; ?>
