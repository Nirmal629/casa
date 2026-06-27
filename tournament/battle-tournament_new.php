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
?>
<div class="battleTournament_sec">
    <!-- <h2 class="title">Winter - Mini Casa Tournament 2025</h2> -->

    <!-- INPUT SUMMARY -->
    <div class="input-box">
        <div class="detail">Club Name: <span>Casa Badminton Club</span></div>
        <div class="detail">Tag Line: <span>lorem Ipsum..........</span></div>
        <div class="detail">Category: <span>Men-Doubles-Open</span></div>
        <div class="detail">Date: <span>06/02/2026</span></div>
        <div class="detail">Time: <span>Feb 21, 2026 at 9:36 AM EST</span></div>
        <div class="detail">Venue: <span>Casa Badminton Club</span></div>
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
                            <input type="number" id="groupsRequired" name="groups_required" value="<?php echo (int)$groupsRequired; ?>" min="1" max="4">
                        </div>

                        <div class="field small">
                            <label>Teams in each group</label>
                            <input type="number" value="<?php echo (int)$teamsPerGroup; ?>" readonly>
                        </div>

                        <div class="field small">
                            <label>Teams from GL to Q (per group) <span>(Max 6)</span></label>
                            <input type="number" value="2">
                        </div>

                        <div class="field small">
                            <label>Teams from Q to Semi <span>(Max 4)</span></label>
                            <input type="number" value="4">
                        </div>
                    </div>

                    <!-- Game Sets -->
                    <h4 class="sub-title">Game Sets</h4>

                    <div class="grid">
                        <div class="field small">
                            <label>Group League</label>
                            <select id="groupLeagueSets" class="game-set-select" data-stage="GROUP">
                                <option value="1" selected>1</option>
                                <option value="3">3</option>
                            </select>
                        </div>
                        <div class="field small">
                            <label>Quarter Final</label>
                            <select class="game-set-select" data-stage="QUARTER_FINAL" disabled>
                                <option value="3" selected>3</option>
                            </select>
                        </div>
                        <div class="field small">
                            <label>Semi Final</label>
                            <select class="game-set-select" data-stage="SEMI_FINAL" disabled>
                                <option value="3" selected>3</option>
                            </select>
                        </div>
                        <div class="field small">
                            <label>Winner Final</label>
                            <select class="game-set-select" data-stage="FINAL" disabled>
                                <option value="3" selected>3</option>
                            </select>
                        </div>
                        <div class="field small">
                            <label>Loser Final</label>
                            <select class="game-set-select" data-stage="BRONZE_FINAL" disabled>
                                <option value="3" selected>3</option>
                            </select>
                        </div>
                    </div>

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
                                            data-bs-target="#spinWheelModal" data-spin-mode="quarter-groups" title="Spin">Spin</button>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="d-none">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
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
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
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
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
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
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
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
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
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
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                        </div>
                    </div>

                    <div class="action-area">
                        <!-- <button type="button" class="spin-btn" data-bs-toggle="modal" data-bs-target="#spinWheelModal">🎯 Spin a Wheel</button> -->
                        <button class="save">Save Parameters</button>
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
                                <th>Team</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>Group</th>
                                <th>Action</th>
                            </tr>
                            <?php if ($dbError): ?>
                                <tr>
                                    <td colspan="5"><?php echo htmlspecialchars($dbError); ?></td>
                                </tr>
                            <?php elseif (empty($teamRows)): ?>
                                <tr>
                                    <td colspan="5">No teams registered.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teamRows as $team): ?>
                                    <?php
                                    $players = array_values(array_filter(explode('||', $team['PLAYERS'] ?? '')));
                                    $selectedGroup = $team['GROUP_NAME'] ?? '';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($team['TEAM_NAME'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($players[0] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($players[1] ?? '-'); ?></td>
                                        <td class="team-group-display" id="team-group-<?php echo (int)$team['ID']; ?>">
                                            <?php echo htmlspecialchars($selectedGroup ?: ''); ?>
                                        </td>
                                        <td>
                                            <select class="team-group-select" data-team-id="<?php echo (int)$team['ID']; ?>">
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
                <h4 class="section-title">League Stage</h4>

                <div class="grid-4">
                    <?php foreach ($allowedGroups as $groupName): ?>
                        <?php $groupRows = $leagueGroups[$groupName] ?? []; ?>
                        <div class="card">
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <h6><?php echo htmlspecialchars($groupName); ?></h6>
                            </div>
                            <table>
                                <tr>
                                    <th>Team</th>
                                    <th>P1</th>
                                    <th>P2</th>
                                    <th>Pts</th>
                                    <th>Rank</th>
                                </tr>
                                <?php if (empty($groupRows)): ?>
                                    <tr>
                                        <td colspan="5">No teams allocated.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($groupRows as $groupTeam): ?>
                                        <?php $players = array_values(array_filter(explode('||', $groupTeam['PLAYERS'] ?? ''))); ?>
                                        <tr>
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
                        <a href="court-dashboard.php" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                    </table>
                </div>

                <!-- SEMI FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Semi Final</h4>
                        <a href="#" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                         <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="grid-4">
                <!-- FINAL -->
                <div class="card winner">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Championship Final</h4>
                        <a href="badminton-scorer.php" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                    </table>
                </div>

                <!-- LOOSER FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Bronze Final</h4>
                        <a href="#" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>L-3</td>
                            <td>3</td>
                        </tr>
                        <tr>
                            <td>L-4</td>
                            <td>4</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



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
    const tournamentSetStorageKey = 'badmintonTournamentGameSets_<?php echo (int)$tournamentId; ?>';
    const allowedQuarterGroups = <?php echo json_encode($allowedGroups); ?>;
    const groupLeagueSetsSelect = document.getElementById('groupLeagueSets');

    function readTournamentSetConfig() {
        try {
            return JSON.parse(localStorage.getItem(tournamentSetStorageKey) || '{}') || {};
        } catch (error) {
            return {};
        }
    }

    function saveTournamentSetConfig(config) {
        localStorage.setItem(tournamentSetStorageKey, JSON.stringify(config));
    }

    function applyStoredSetConfig() {
        if (!groupLeagueSetsSelect) {
            return;
        }
        const config = readTournamentSetConfig();
        groupLeagueSetsSelect.value = String(config.GROUP || '1');
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
    restoreQuarterGroupPairInputs();

    if (groupLeagueSetsSelect) {
        groupLeagueSetsSelect.addEventListener('change', function () {
            saveTournamentSetConfig({
                GROUP: this.value === '3' ? 3 : 1,
                QUARTER_FINAL: 3,
                SEMI_FINAL: 3,
                FINAL: 3,
                BRONZE_FINAL: 3
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
