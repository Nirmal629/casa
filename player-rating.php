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

// Get players that this user is allowed to rate
// (only players at or below the current user's level, active, not deleted, etc.)
$playersResult = mysqli_query(
    $conn,
    "SELECT ID, NAME, VERIFIED_LEVEL, GENDER 
     FROM ca_users 
     WHERE USERTYPE='Player' 
       AND VERIFIED_LEVEL IS NOT NULL 
       AND DEL_STATUS='N' 
       AND LOG_STATUS='Y' 
       AND ID != $currentUserId"
);

// Build an array for JS to filter on client-side
$players = [];
while ($row = mysqli_fetch_assoc($playersResult)) {
    $playerIndex = array_search($row['VERIFIED_LEVEL'], $levelOrder);
    if ($playerIndex !== false && $myLevelIndex !== false && $playerIndex <= $myLevelIndex) {
        $players[] = $row; // only allowed players
    }
}
?>

<div class="">
    <div class="mb-4">
        <h2 class="mb-4">Player Rating</h2>

        <style>
            /* Similar compact layout as host side */

            .single-row-wrap {
                display: flex;
                gap: 0.5rem;
                align-items: flex-end;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }

            .sr-field {
                display: flex;
                flex-direction: column;
                flex: 0 0 auto;
                min-width: 140px;
                margin-right: 0.5rem;
            }

            .sr-submit {
                display: flex;
                align-items: flex-end;
                min-width: 90px;
            }

            .sr-field .form-label {
                margin-bottom: 0.25rem;
                font-size: 0.85rem;
            }

            #filterForm, #ratingForm {
                margin: 0;
            }

            @media (max-width: 768px) {
                .sr-field { min-width: 120px; }
            }
        </style>

        <!-- SINGLE ROW WRAP: Filters + Rating form side by side -->
        <div class="single-row-wrap">

            <!-- FILTERS (Level -> Gender) -->
            <form id="filterForm" class="d-flex align-items-end">
                <div class="sr-field">
                    <label class="form-label">Select Level</label>
                    <select id="filter_level" class="form-select">
                        <option value="">-- All Levels --</option>
                        <?php
                        // Only show levels up to the current user's level
                        foreach ($levelOrder as $idx => $level) {
                            if ($myLevelIndex !== false && $idx <= $myLevelIndex) {
                                echo "<option value='".htmlspecialchars($level)."'>".htmlspecialchars($level)."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="sr-field">
                    <label class="form-label">Select Gender</label>
                    <select id="filter_gender" class="form-select">
                        <option value="">-- All --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Mix">Mix</option>
                    </select>
                </div>
            </form>

            <!-- RATING FORM -->
            <form id="ratingForm" class="d-flex align-items-end">

                <div class="sr-field" style="width:180px">
                    <label for="rated_player_id" class="form-label">Player to Rate</label>
                    <select name="rated_player_id" id="rated_player_id" class="form-select" required>
                        <option value="">-- Select Player --</option>
                        <!-- Options will be filled by JS based on filters -->
                    </select>
                </div>

                <div class="sr-field" style="width:120px">
                    <label class="form-label">Current Level</label>
                    <input type="text" name="current_level" id="current_level" class="form-control" readonly>
                </div>

                <div class="sr-field" style="width:120px">
                    <label class="form-label">Current Ranking</label>
                    <input type="text" name="current_ranking" id="current_ranking" class="form-control" readonly>
                </div>

                <div class="sr-field" style="width:140px">
                    <label for="skill_level" class="form-label">Update Level</label>
                    <select name="skill_level" id="skill_level" class="form-select" required>
                        <option value="">-- Select Level --</option>
                        <?php
                        // Player can only suggest levels within the defined order
                        foreach ($levelOrder as $level) {
                            echo "<option value='".htmlspecialchars($level)."'>".htmlspecialchars($level)."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="sr-field" style="width:120px">
                    <label for="ranking" class="form-label">Update Ranking</label>
                    <input type="number" name="ranking" id="ranking" class="form-control" min="1" max="10" required>
                </div>

                <div class="sr-field sr-submit">
                    <button type="submit" class="btn btn-success w-100">Submit</button>
                </div>
            </form>
        </div>

        <div id="player_rating_table" class="mb-4"></div>

        <div class="custom_card">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="card_heading">Level Wise Ranking</h6>
                <div>
                    <button id="refreshBtn" class="btn btn-sm btn-secondary">Refresh</button>
                </div>
            </div>
            <div id="ranking_table" class="mt-3"></div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Make allowed players available to JS -->
<script>
const ALL_PLAYERS = <?php echo json_encode($players); ?>;
</script>

<script>
// =============== PLAYER LIST BASED ON FILTERS ===============

function loadPlayers() {
    const level = $('#filter_level').val();
    const gender = $('#filter_gender').val();
    const $select = $('#rated_player_id');

    $select.html('<option value="">-- Select Player --</option>');

    if (!Array.isArray(ALL_PLAYERS) || ALL_PLAYERS.length === 0) return;

    ALL_PLAYERS.forEach(p => {
        if (level && p.VERIFIED_LEVEL !== level) return;
        if (gender && p.GENDER !== gender) return;

        const label = `${p.NAME} (${p.VERIFIED_LEVEL})`;
        $select.append(`<option value="${p.ID}">${label}</option>`);
    });
}

// =============== RANKING TABLE (LIKE HOST SIDE) ===============

function loadRankingTable() {
    const level = $('#filter_level').val();
    const gender = $('#filter_gender').val();

    $.getJSON('api/get_rating_table.php', { level, gender, t: Date.now() }, function(data) {
        let html = '';

        // expecting data.players = [ { ID, name, ranking, level, gender }, ... ]
        if (!data.players || data.players.length === 0) {
            html = '<p>No players found for selected filters.</p>';
            $('#ranking_table').html(html);
            return;
        }

        html += `<table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th style="width:40%;">Name</th>
                            <th style="width:15%;">Ranking</th>
                            <th style="width:20%;">Level</th>
                            <th style="width:15%;">Gender</th>
                            <th style="width:10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>`;

        data.players.forEach(p => {
            const ranking = p.ranking !== null ? p.ranking : '-';
            html += `<tr>
                        <td>${p.name}</td>
                        <td>${ranking}</td>
                        <td>${p.level}</td>
                        <td>${p.gender}</td>
                        <td>
                            <button class="btn btn-sm btn-primary view-ratings" data-id="${p.ID}">
                                Rate
                            </button>
                        </td>
                     </tr>`;
        });

        html += '</tbody></table>';
        $('#ranking_table').html(html);
    }).fail(function() {
        $('#ranking_table').html('<p>Error loading ranking table.</p>');
    });
}

// =============== LOAD PLAYER INFO WHEN SELECTED ===============

function loadPlayerRatings(playerId) {
    if (!playerId) {
        $('#current_level').val('');
        $('#current_ranking').val('');
        $('#skill_level').val('');
        $('#ranking').val('');
        $('#player_rating_table').html('');
        return;
    }

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

        if (data.ratings && data.ratings.length) {
            let list = "<table class='table table-bordered table-sm'><thead><tr><th>Rater</th><th>Level</th><th>Ranking</th></tr></thead><tbody>";
            data.ratings.forEach(r => {
                list += `<tr><td>${r.rater}</td><td>${r.level}</td><td>${r.ranking}</td></tr>`;
            });
            list += "</tbody></table>";
            $('#player_rating_table').html(list);
        } else {
            $('#player_rating_table').html('');
        }
    }, 'json').fail(function() {
        alert('Unable to fetch player info.');
    });
}

// =============== EVENTS ===============

// Filters changed: clear form & reload player list + table
$('#filter_level, #filter_gender').on('change', function() {
    $('#rated_player_id').val('');
    $('#current_level').val('');
    $('#current_ranking').val('');
    $('#skill_level').val('');
    $('#ranking').val('');
    $('#player_rating_table').html('');

    loadPlayers();
    loadRankingTable();
});

// Player selection from dropdown
$('#rated_player_id').on('change', function () {
    const playerId = $(this).val();
    loadPlayerRatings(playerId);
});

// Clicking "Rate" in table selects that player in dropdown and loads their info
$(document).on('click', '.view-ratings', function() {
    const id = $(this).data('id');
    $('#rated_player_id').val(id).trigger('change');
    // Scroll to top if needed
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Submit rating
$('#ratingForm').on('submit', function (e) {
    e.preventDefault();
    const formData = $(this).serialize();
    $.post('api/save_rating.php', formData, function (res) {
        alert(res.message);
        if (res.success) {
            // Only clear update fields; keep filters
            $('#rated_player_id').val('');
            $('#current_level').val('');
            $('#current_ranking').val('');
            $('#skill_level').val('');
            $('#ranking').val('');
            $('#player_rating_table').html('');
            loadRankingTable();
        }
    }, 'json').fail(function() {
        alert('Error saving rating.');
    });
});

// Refresh button
$('#refreshBtn').on('click', function() {
    loadPlayers();
    loadRankingTable();
});

// Init
$(document).ready(function () {
    loadPlayers();       // initial dropdown based on allowed players
    loadRankingTable();  // initial ranking table
});
</script>
