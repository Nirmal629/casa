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
        <h2 class="mb-4">Player Rating Management</h2>

    <form id="ratingForm" class="row g-3 align-items-end mb-4">
        <div class="col-md-3">
            <label for="rated_player_id" class="form-label">Player to Rate</label>
            <select name="rated_player_id" id="rated_player_id" class="form-select" required>
                <option value="">-- Select --</option>
                <?php while ($player = mysqli_fetch_assoc($playersResult)) {
                    echo "<option value='{$player['ID']}'>{$player['NAME']} ({$player['VERIFIED_LEVEL']})</option>";
                } ?>
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
            <select name="ranking" id="ranking" class="form-select" required>
                <option value="">-- Select Ranking --</option>
            </select>
        </div>

        <div class="col-md-1">
            <button type="submit" class="btn btn-success">Submit</button>
        </div>
    </form>

    <div id="player_rating_table" class="mb-4"></div>

    <div class="custom_card">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="card_heading">Level Wise Ranking</h6>
        </div>
        <div id="ranking_table"></div>
    </div>

    <!-- jQuery + Bootstrap Bundle -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function loadRankingTable() {
        $.getJSON('api/get_rating_table.php', function(data) {
            let html = '';
            data.forEach(group => {
                html += `<h5 class="mt-4">Players (Level: ${group.level})</h5>`;
                html += `<table class="table table-bordered text-center">
                            <thead><tr><th>Name</th><th>Ranking</th><th>Level</th><th>Gender</th></tr></thead><tbody>`;
                group.players.forEach(player => {
                    html += `<tr><td>${player.name}</td><td>${player.ranking}</td><td>${player.level}</td><td>${player.gender}</td></tr>`;
                });
                html += `</tbody></table>`;
            });
            $('#ranking_table').html(html);
        });
    }

    function loadPlayerRatings(playerId) {
        $.post('api/get_player_info_host.php', { rated_player_id: playerId }, function (data) {
            $('#current_level').val(data.user?.VERIFIED_LEVEL || '');
            $('#current_ranking').val(data.user?.CURRENT_RANKING || '');

            // if (data.rating) {
            //     $('#skill_level').val(data.rating.SKILL_LEVEL);
            //     $('#ranking').val(data.rating.RANKING);
            // } else {
            //     $('#skill_level').val('');
            //     $('#ranking').val('');
            // }

            if (data.ratings?.length) {
                let html = `<h6 class='mb-2'>Ratings Given:</h6><table class='table table-sm table-bordered'><thead><tr><th>Rater</th><th>Level</th><th>Ranking</th></tr></thead><tbody>`;
                data.ratings.forEach(r => {
                    html += `<tr><td>${r.rater}</td><td>${r.level}</td><td>${r.ranking}</td></tr>`;
                });
                html += `</tbody></table>`;
                $('#player_rating_table').html(html);
            } else {
                $('#player_rating_table').html('');
            }
        }, 'json');
    }

    $('#rated_player_id').on('change', function () {
        let playerId = $(this).val();
        if (!playerId) return;
        loadPlayerRatings(playerId);
    });

    $('#skill_level').on('change', function () {
        const level = $(this).val();
        if (!level) return;

        $.get('api/get_ranking_range.php', { level }, function (res) {
            let options = '<option value="">-- Select Ranking --</option>';
            for (let i = 1; i <= res.total; i++) {
                options += `<option value="${i}">${i}</option>`;
            }
            $('#ranking').html(options);
        }, 'json');
    });

    $('#ratingForm').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post('api/save_rating_by_host.php', formData, function (res) {
            alert(res.message);
            $('#ratingForm')[0].reset();
            $('#player_rating_table').html('');
            $('#ranking').html('<option value="">-- Select Ranking --</option>');
            loadRankingTable();
        }, 'json');
    });

    $(document).ready(function () {
        loadRankingTable();
    });
    </script>
