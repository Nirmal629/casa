<?php
session_start();
include('dbConnection.php');
date_default_timezone_set('Asia/Kolkata');

$currentUserId = $_SESSION['user_id'] ?? 0;

// Define level hierarchy
$levelOrder = ["Beginner", "Amateur", "Intermediate", "Intermediate +", "Advanced"];

// Get current user's level
$currentUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT VERIFIED_LEVEL FROM ca_users WHERE ID = $currentUserId"));
$myLevel = $currentUser['VERIFIED_LEVEL'] ?? '';
$myLevelIndex = array_search($myLevel, $levelOrder);

// Get players to rate
$playersResult = mysqli_query($conn, "SELECT ID, NAME, VERIFIED_LEVEL FROM ca_users WHERE USERTYPE='Player' AND VERIFIED_LEVEL IS NOT NULL AND DEL_STATUS='N' AND LOG_STATUS='Y' AND ID != $currentUserId");
?>

<div class="">
    <div class="mb-4">
        <form id="ratingForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="rated_player_id" class="form-label">Player to Rate</label>
                <select name="rated_player_id" id="rated_player_id" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    mysqli_data_seek($playersResult, 0);
                    while ($player = mysqli_fetch_assoc($playersResult)) {
                        $playerIndex = array_search($player['VERIFIED_LEVEL'], $levelOrder);
                        if ($playerIndex !== false && $playerIndex <= $myLevelIndex) {
                            echo "<option value='{$player['ID']}'>{$player['NAME']} ({$player['VERIFIED_LEVEL']})</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Current Level</label>
                <input type="text" name="current_level" id="current_level" class="form-control" readonly>
            </div>

            <div class="col-md-2">
                <label class="form-label">Current Ranking</label>
                <input type="text" name="current_ranking" id="current_ranking" class="form-control" readonly>
            </div>

            <div class="col-md-2">
                <label for="skill_level" class="form-label">Update Level</label>
                <select name="skill_level" id="skill_level" class="form-select" required>
                    <option value="">-- Select Level --</option>
                    <?php foreach ($levelOrder as $level) {
                        echo "<option value='$level'>$level</option>";
                    } ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="ranking" class="form-label">Update Ranking</label>
                <input type="number" name="ranking" id="ranking" class="form-control" min="1" max="10" required>
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-success">Submit</button>
            </div>
        </form>
    </div>

    <div class="custom_card">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="card_heading">Level Wise Ranking</h6>
        </div>
        <div id="ranking_table"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadRankingTable() {
    $.getJSON('api/get_rating_table.php?t=' + new Date().getTime(), function(data) {
        console.log(data)
        let html = '';

        if (!data.length) {
            html = '<p>No ratings available.</p>';
        } else {
            data.forEach(group => {
                html += `<h5 class="mt-4">Players (Level: ${group.level})</h5>`;
                html += `
                    <table class="table table-bordered table-striped text-center align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Name</th>
                                <th>Ranking</th>
                                <th>Level</th>
                                <th>Gender</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                group.players.forEach(player => {
                    html += `
                        <tr>
                            <td>${player.name}</td>
                            <td>${player.ranking}</td>
                            <td>${player.level}</td>
                            <td>${player.gender}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
            });
        }

        $('#ranking_table').html(html);
    });
}

$('#rated_player_id').on('change', function () {
    let playerId = $(this).val();
    if (!playerId) return;

    $.post('api/get_player_info.php', { rated_player_id: playerId }, function (data) {
        $('#current_level').val(data.user?.VERIFIED_LEVEL || '');
        $('#current_ranking').val(data.user?.CURRENT_RANKING || '');

        if (data.rating) {
            $('#skill_level').val(data.rating.SKILL_LEVEL);
            $('#ranking').val(data.rating.RANKING);
        } else {
            $('#skill_level').val('');
            $('#ranking').val('');
        }
    }, 'json');
});

$('#ratingForm').on('submit', function (e) {
    e.preventDefault();
    const formData = $(this).serialize();
    $.post('api/save_rating.php', formData, function (res) {
        alert(res.message);
        $('#ratingForm')[0].reset();
        $('#current_level').val('');
        $('#current_ranking').val('');
        loadRankingTable();
    }, 'json');
});

$(document).ready(function () {
    loadRankingTable();
});
</script>
