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
                <div class="scoreboard-set-row">
                    <span class="scoreboard-shuttle"></span>
                    <span class="scoreboard-set-score">
                        <span id="team-a-set-one"><?php echo (int)$initialTeam1Score; ?></span> - <span id="team-b-set-one"><?php echo (int)$initialTeam2Score; ?></span>
                    </span>
                    <span class="scoreboard-shuttle"></span>
                </div>
                <div class="scoreboard-set-row">
                    <span class="scoreboard-shuttle"></span>
                    <span class="scoreboard-set-score">
                        <span id="team-a-set-two">0</span> - <span id="team-b-set-two">0</span>
                    </span>
                    <span class="scoreboard-shuttle"></span>
                </div>
                <div class="scoreboard-set-row">
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

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <div class="court-box mx-auto p-3 shadow-lg" style="border-radius: 20px; border: 1px solid rgba(13, 202, 240, 0.2); color: white;">



                    <div class="p-3 rounded-3 mb-2" style="background: rgba(255,255,255,0.05);">

                        <input type="text" id="t1_name" class="form-control form-control-sm bg-dark text-white border-0 mb-2" placeholder="Team 1 Name" value="<?php echo htmlspecialchars($matchData['TEAM_1_NAME'] ?? ''); ?>">
                        <div class="row gap-2">
                            <div class="col-6"><input type="text" id="t1_p1" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 1" value="<?php echo htmlspecialchars($team1Player1); ?>"></div>
                            <div class="col-6"><input type="text" id="t1_p2" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 2" value="<?php echo htmlspecialchars($team1Player2); ?>"></div>
                        </div>

                    </div>



                    <div class="p-3 rounded-3 mb-3" style="background: rgba(255,255,255,0.05);">

                        <input type="text" id="t2_name" class="form-control form-control-sm bg-dark text-white border-0 mb-2" placeholder="Team 2 Name" value="<?php echo htmlspecialchars($matchData['TEAM_2_NAME'] ?? ''); ?>">
                        <div class="row g-2">
                            <div class="col-6"><input type="text" id="t2_p1" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 1" value="<?php echo htmlspecialchars($team2Player1); ?>"></div>
                            <div class="col-6"><input type="text" id="t2_p2" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 2" value="<?php echo htmlspecialchars($team2Player2); ?>"></div>
                        </div>

                    </div>



                    <div class="d-flex g-2 mb-4">
                        <select id="match_type" class="form-select form-select-sm bg-dark text-white border-secondary" disabled>
                            <option value="doubles">Doubles</option>
                        </select>
                        <select id="deuce_type" class="form-select form-select-sm bg-dark text-white border-secondary" disabled>
                            <option value="deuce">Deuce On</option>
                        </select>
                    </div>

                    <div class="d-flex g-2 mb-3">
                        <button type="button" id="config-court-swap" class="btn btn-outline-info btn-sm w-50 fw-bold mr-2">
                            <i class="fa-solid fa-right-left mr-1"></i> SWAP COURT
                        </button>
                        <button type="button" id="config-voice-toggle" class="btn btn-outline-info btn-sm w-50 fw-bold">
                            <i class="fa-solid fa-volume-xmark mr-1"></i> VOICE OFF
                        </button>
                    </div>

                    <button class="btn btn-info w-100 fw-bold py-2 mb-2 rounded-pill shadow" type="button" onclick="startPlayableMatch()">
                        START MATCH <i class="fa-solid fa-play ms-2"></i>
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

                <h6 class="modal-title text-info fw-bold text-uppercase small" id="matchResultLabel"><i class="fa-solid fa-trophy mr-2"></i>Match Result</h6>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <div class="court-box mx-auto p-3 shadow-lg" style="border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1); color: white;">



                    <div class="text-center mb-3 pb-3 border-bottom border-secondary border-opacity-25">

                        <!-- <div class="small text-uppercase opacity-50 fw-bold mb-1" style="letter-spacing: 2px;">Match Result</div> -->

                        <h5 class="fw-bold text-success mb-0" id="match-result-winner">
                            <i class="fa-solid fa-trophy mr-2 text-warning"></i> WINNER: -
                        </h5>
                    </div>



                    <div class="row g-0 align-items-center mb-4 bg-black rounded-4 p-3 shadow-inner">

                        <div class="col-5 text-center">

                            <div class="p-1 rounded-circle bg-success d-inline-block mb-2" style="width: 8px; height: 8px;"></div>

                            <h6 class="fw-bold text-white mb-0" id="match-result-team-a-name">Team 1</h6>
                            <div class="display-4 fw-bold text-info" id="match-result-team-a-sets">0</div>
                        </div>



                        <div class="col-2 text-center opacity-25">

                            <div class="h4 mb-0">-</div>

                        </div>



                        <div class="col-5 text-center opacity-75">

                            <div class="p-1 rounded-circle bg-secondary d-inline-block mb-2" style="width: 8px; height: 8px;"></div>

                            <h6 class="fw-bold mb-0" id="match-result-team-b-name">Team 2</h6>
                            <div class="display-4 fw-bold" id="match-result-team-b-sets">0</div>
                        </div>

                    </div>



                    <div class="set-breakdown vstack gap-2" id="match-result-set-breakdown">
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-dark border border-secondary" style="font-size: 0.85rem;">
                            <span class="opacity-50 fw-bold">SET 1</span>
                            <div class="fw-bold">
                                <span class="text-info">0</span> <span class="mx-2 opacity-25">|</span> 0
                            </div>
                            <i class="fa-solid fa-hourglass-half text-secondary"></i>
                        </div>
                    </div>


                    <div class="mt-4 row g-2">

                        <div class="col-6">

                            <button class="btn btn-outline-light w-100 btn-sm rounded-pill fw-bold py-2">

                                <i class="fa-solid fa-share-nodes mr-2"></i>SHARE

                            </button>

                        </div>

                        <div class="col-6">

                            <button class="btn btn-info w-100 btn-sm rounded-pill fw-bold py-2 shadow" data-dismiss="modal">

                                NEW MATCH

                            </button>

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

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <div class="court-container mx-auto p-3 shadow-lg" style="border-radius: 24px; border: 2px solid #0ea5e9; color: white;">



                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <button type="button" id="set-board-undo-point" class="btn btn-sm btn-outline-warning border-secondary text-warning fw-bold px-3">
                            <i class="fa-solid fa-rotate-left me-1"></i> UNDO
                        </button>
                        <div class="text-center">

                            <h6 class="mb-0 fw-bold" style="font-size: 0.8rem; letter-spacing: 1px;">SET 1 LIVE</h6>

                            <span class="text-info small fw-bold">21 - 18</span>

                        </div>

                        <button class="btn btn-sm btn-outline-danger border-secondary text-danger fw-bold">FINISH</button>

                    </div>



                    <div class="court-grid position-relative rounded-3 mb-4" style="background: #1e40af; border: 4px solid #fff; height: 320px; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; gap: 2px;">



                        <div class="position-absolute start-50 top-0 bottom-0 border-start border-white border-3 opacity-75" style="z-index: 10; transform: translateX(-50%);"></div>

                        <div class="position-absolute start-0 end-0 top-50 border-top border-white border-1 opacity-25" style="z-index: 5;"></div>



                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2">

                            <div class="player-circle bg-white text-dark fw-bold rounded-circle shadow-sm mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">AZ</div>

                            <span class="small opacity-75" style="font-size: 0.65rem;">Azhar</span>

                            <div class="birdie-marker mt-2 text-warning animate-pulse"><i class="fa-solid fa-circle fa-xs"></i></div>

                        </div>



                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2" style="background: rgba(0,0,0,0.15);">

                            <div class="player-circle border border-white text-white fw-bold rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">SM</div>

                            <span class="small opacity-75" style="font-size: 0.65rem;">Sam</span>

                            <div class="birdie-marker mt-2 text-warning opacity-0"><i class="fa-solid fa-circle fa-xs"></i></div>

                        </div>



                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2">

                            <div class="player-circle border border-white text-white opacity-50 rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">ZD</div>

                            <span class="small opacity-50" style="font-size: 0.65rem;">Zaid</span>

                            <div class="birdie-marker mt-2 text-warning opacity-0"><i class="fa-solid fa-circle fa-xs"></i></div>

                        </div>



                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2" style="background: rgba(0,0,0,0.15);">

                            <div class="player-circle border border-white text-white opacity-50 rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">RY</div>

                            <span class="small opacity-50" style="font-size: 0.65rem;">Ray</span>

                            <div class="birdie-marker mt-2 text-warning opacity-0"><i class="fa-solid fa-circle fa-xs"></i></div>

                        </div>

                    </div>



                    <div class="row g-2">

                        <div class="col-6">

                            <button class="btn btn-info w-100 py-3 rounded-4 d-flex flex-column align-items-center shadow">

                                <span class="small fw-bold opacity-75">TEAM 1</span>

                                <div class="d-flex align-items-center gap-2">

                                    <i class="fa-solid fa-plus"></i>

                                    <span class="h2 mb-0 fw-bold">1</span>

                                </div>

                            </button>

                        </div>

                        <div class="col-6">

                            <button class="btn btn-outline-info w-100 py-3 rounded-4 d-flex flex-column align-items-center">

                                <span class="small fw-bold opacity-50">TEAM 2</span>

                                <div class="d-flex align-items-center gap-2">

                                    <i class="fa-solid fa-plus"></i>

                                    <span class="h2 mb-0 fw-bold">1</span>

                                </div>

                            </button>

                        </div>

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
        initialScoreB: <?php echo (int)$initialTeam2Score; ?>
    };
</script>
<?php include "includes/scorer-footer.php"; ?>
