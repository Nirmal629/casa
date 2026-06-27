<?php
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$matchData = null;
$team1Players = [];
$team2Players = [];
$team1PlayerRows = [];
$team2PlayerRows = [];

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
                        <a class="dropdown-item" data-toggle="modal" data-target="#matchConfig">Match Configuration</a>
                        <a class="dropdown-item" data-toggle="modal" data-target="#matchResult">Match Result</a>
                        <a class="dropdown-item" data-toggle="modal" data-target="#setScoreBoard">Set Score Board</a>
                    </div>
                </li>
            </ul>
        </nav>

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
                <button class="score-style left-scorer" onclick="incrementScore('left')">+1</button>
            </div>

            <div class="row court-row">
                <div class="col top-sideline">
                    <span id="left-court-team-name"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1 name'); ?></span>
                    <button type="button" id="undo-point" class="btn-link fw-bold">Undo</button>
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
                <button class="score-style right-scorer" onclick="incrementScore('right')">+1</button>
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

                    <button class="match-config-save-btn" type="button" onclick="startPlayableMatch()">
                        SAVE
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- **** Modal for Match Result **** -->
<div class="modal fade" id="matchResult" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="matchResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="match-result-header-left">
                    <h6 class="modal-title text-info fw-bold text-uppercase small mr-1" id="matchResultLabel"><i class="fa-solid fa-trophy mr-1"></i>Match</h6>
                    <select class="match-result-mini-select" aria-label="Match type">
                        <option>Doubles</option>
                        <option>demo 1</option>
                        <option>demo 2</option>
                    </select>
                    <select class="match-result-mini-select" aria-label="Deuce setting">
                        <option>Deuce On</option>
                        <option>demo 1</option>
                        <option>demo 2</option>
                    </select>
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
                                <i class="fa-solid fa-trophy match-result-trophy"></i>
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
                                <i class="fa-solid fa-trophy match-result-trophy"></i>
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
                                <button type="button" onclick="startPlayableMatch()">Start</button>
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
                            <span>Team A</span>
                            <input type="number" id="set-board-score-a" class="set-board-score-input" min="0" max="99" value="<?php echo (int)$initialTeam1Score; ?>" readonly>
                        </div>
                        <div class="set-board-score-center">
                            <span>Current Score</span>
                            <strong id="set-board-live-score" class="set-board-live-score"><?php echo (int)$initialTeam1Score; ?> - <?php echo (int)$initialTeam2Score; ?></strong>
                        </div>
                        <div class="set-board-score-box">
                            <span>Team B</span>
                            <input type="number" id="set-board-score-b" class="set-board-score-input" min="0" max="99" value="<?php echo (int)$initialTeam2Score; ?>" readonly>
                        </div>
                    </div>

                    <div class="set-board-court">
                        <div class="set-board-net"></div>
                        <div class="set-board-team set-board-team-left">
                            <div class="set-board-team-card">
                                <strong id="set-board-team-a-name"><?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? 'Team 1'); ?></strong>
                                <span id="set-board-team-a-players"><?php echo htmlspecialchars($team1Player1); ?><br><?php echo htmlspecialchars($team1Player2); ?></span>
                            </div>
                            <div class="set-board-actions">
                                <button type="button" class="set-board-action-btn" id="set-board-edit-a"><i class="fa-solid fa-pen"></i>Edit</button>
                                <button type="button" class="set-board-plus" id="set-board-plus-a">+1</button>
                                <button type="button" class="set-board-action-btn" id="set-board-save-a"><i class="fa-solid fa-check"></i>Save</button>
                            </div>
                        </div>
                        <div class="set-board-team set-board-team-right">
                            <div class="set-board-team-card">
                                <strong id="set-board-team-b-name"><?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? 'Team 2'); ?></strong>
                                <span id="set-board-team-b-players"><?php echo htmlspecialchars($team2Player1); ?><br><?php echo htmlspecialchars($team2Player2); ?></span>
                            </div>
                            <div class="set-board-actions">
                                <button type="button" class="set-board-action-btn" id="set-board-edit-b"><i class="fa-solid fa-pen"></i>Edit</button>
                                <button type="button" class="set-board-plus" id="set-board-plus-b">+1</button>
                                <button type="button" class="set-board-action-btn" id="set-board-save-b"><i class="fa-solid fa-check"></i>Save</button>
                            </div>
                        </div>
                    </div>
                    <div class="set-board-footer-actions">
                        <button type="button" id="set-board-undo-point">Undo Last Point</button>
                        <button type="button" id="set-board-save-all" class="set-board-save-all">Save Score</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



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
        initialScoreA: <?php echo (int)$initialTeam1Score; ?>,
        initialScoreB: <?php echo (int)$initialTeam2Score; ?>,
        defaultSetLimit: <?php echo (($matchData['STAGE'] ?? '') === 'GROUP') ? 1 : 3; ?>
    };
</script>
<?php include "includes/scorer-footer.php"; ?>
