<?php
session_start();
include('dbConnection.php');
date_default_timezone_set('Asia/Kolkata');

$currentUserId = $_SESSION['user_id'] ?? 0;

// Level Hierarchy
$levelOrder = ["Beginner", "Amateur", "Intermediate", "Intermediate +", "Advanced"];
?>
<div class="">
    <div class="mb-4">
        <h2 class="mb-4">Player Rating Management</h2>

        <style>
            /* Single row wrapper holds the two forms inline on wide screens */
            .single-row-wrap {
                display: flex;
                gap: 0.5rem;
                align-items: flex-end;        /* align labels/inputs nicely */
                flex-wrap: nowrap;            /* keep everything on one line when possible */
                overflow-x: auto;             /* allow horizontal scroll on small screens */
                padding-bottom: 0.5rem;
            }

            /* Each field block uses this compact style */
            .sr-field {
                display: flex;
                flex-direction: column;
                flex: 0 0 auto;               /* don't grow; keep natural width */
                min-width: 140px;             /* tweak to fit content — adjust to taste */
                margin-right: 0.5rem;
            }

            /* Make the submit button a similar size as inputs */
            .sr-submit {
                display: flex;
                align-items: flex-end;
                min-width: 90px;
            }

            /* Tighten label spacing */
            .sr-field .form-label {
                margin-bottom: 0.25rem;
                font-size: 0.85rem;
            }

            /* reduce default margin on forms that used bootstrap row */
            #filterForm, #ratingForm {
                margin: 0;
            }

            /* Optional: reduce gap inside selects/inputs on very narrow screens */
            @media (max-width: 768px) {
                .sr-field { min-width: 120px; }
            }
        </style>

        <!-- SINGLE ROW WRAP: contains both forms visually on one line -->
        <div class="single-row-wrap">

            <!-- FILTERS (kept ID same so JS works) -->
            <form id="filterForm" class="d-flex align-items-end">
                <div class="sr-field">
                    <label class="form-label">Select Level</label>
                    <select id="filter_level" class="form-select">
                        <option value="">-- All Levels --</option>
                        <?php foreach ($levelOrder as $level) {
                            echo "<option value='".htmlspecialchars($level)."'>".htmlspecialchars($level)."</option>";
                        } ?>
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

            <!-- RATING FORM (kept ID and behavior same) -->
            <form id="ratingForm" class="d-flex align-items-end">

                <div class="sr-field" style="width:130px">
                    <label class="form-label">Player</label>
                    <select name="rated_player_id" id="rated_player_id" class="form-select" required></select>
                </div>

                <div class="sr-field" style="width:120px">
                    <label class="form-label" >Level</label>
                    <input type="text" id="current_level" class="form-control" readonly>
                </div>

                <div class="sr-field" style="width:120px">
                    <label class="form-label">Rank</label>
                    <input type="text" id="current_ranking" class="form-control" readonly>
                </div>

                <div class="sr-field" style="width:120px">
                    <label class="form-label">Update Level</label>
                    <select name="skill_level" id="skill_level" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($levelOrder as $level) echo "<option value='".htmlspecialchars($level)."'>".htmlspecialchars($level)."</option>"; ?>
                    </select>
                </div>

                <div class="sr-field">
                    <label class="form-label">Update Ranking</label>
                    <select name="ranking" id="ranking" class="form-select" required></select>
                </div>

                <div class="sr-field sr-submit">
                    <!-- Submit stays same, width adjusted for compactness -->
                    <button class="btn btn-success w-100">Submit</button>
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

<!-- JQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Populate player dropdown based on selected filters
function loadPlayers() {
    const level = $('#filter_level').val();
    const gender = $('#filter_gender').val();

    $.post('api/get_filtered_players.php', { level, gender }, function(res) {
        const $playerSelect = $('#rated_player_id');
        $playerSelect.html('<option value="">-- Select Player --</option>');

        if (res.players && res.players.length) {
            res.players.forEach(p => {
                $playerSelect.append(`<option value="${p.ID}">${p.NAME}</option>`);
            });
        }
    }, 'json').fail(function() {
        alert('Unable to load players.');
    });
}

// Load the single ranking table based on filters (level + gender).
function loadRankingTable() {
    const level = $('#filter_level').val();
    const gender = $('#filter_gender').val();

    // Request a flat list of players matching filters
    $.getJSON('api/get_rating_table.php', { level, gender, t: Date.now() }, function(data) {
        let html = '';

        if (!data.players || data.players.length === 0) {
            html = '<p>No players found for selected filters.</p>';
            $('#ranking_table').html(html);
            return;
        }

        // Single table for filtered results
        html += `<table class="table table-bordered table-striped">
                    <thead class="table-secondary">
                        <tr>
                            <th style="width:40%;">Name</th>
                            <th style="width:15%;">Ranking</th>
                            <th style="width:20%;">Level</th>
                            <th style="width:15%;">Gender</th>
                            <th style="width:10%;">Actions</th>
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
                            <button class="btn btn-sm btn-primary view-ratings" data-id="${p.ID}">View</button>
                        </td>
                    </tr>`;
        });

        html += '</tbody></table>';
        $('#ranking_table').html(html);
    }).fail(function() {
        $('#ranking_table').html('<p>Error loading ranking table.</p>');
    });
}

// Load single player's rating/details for host panel
function loadPlayerRatings(playerId) {
    $.post('api/get_player_info_host.php', { rated_player_id: playerId }, function (data) {
        $('#current_level').val(data.user?.VERIFIED_LEVEL || '');
        $('#current_ranking').val(data.user?.CURRENT_RANKING || '');

        if (data.rating) {
            $('#skill_level').val(data.rating.SKILL_LEVEL);
            $('#ranking').val(data.rating.RANKING);
        } else {
            $('#skill_level').val('');
            $('#ranking').val('');
        }

        if (data.ratings?.length) {
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

// On filters change, reload players and the single ranking table
$('#filter_level, #filter_gender').on('change', function() {
    loadPlayers();
    loadRankingTable();
});

// When "View" button in table clicked, load that player's ratings in the left form area
$(document).on('click', '.view-ratings', function() {
    const id = $(this).data('id');
    $('#rated_player_id').val(id).trigger('change');
    loadPlayerRatings(id);
    // Scroll to form
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Update ranking dropdown options when skill_level changes
$('#skill_level').on('change', function () {
    const level = $(this).val();
    if (!level) return;
    $.get('api/get_ranking_range.php', { level }, function(res) {
        let opt = '<option value="">-- Select Ranking --</option>';
        for (let i = 1; i <= res.total; i++) opt += `<option value="${i}">${i}</option>`;
        $('#ranking').html(opt);
    }, 'json').fail(function() {
        alert('Unable to fetch ranking range.');
    });
});

// When player selected in select box, load details
$('#rated_player_id').on('change', function () {
    const playerId = $(this).val();
    if (!playerId) {
        $('#current_level').val('');
        $('#current_ranking').val('');
        $('#skill_level').val('');
        $('#ranking').html('<option value="">-- Select Ranking --</option>');
        $('#player_rating_table').html('');
        return;
    }
    loadPlayerRatings(playerId);
});

// Save rating
$('#ratingForm').on('submit', function(e) {
    e.preventDefault();
    $.post('api/save_rating_by_host.php', $(this).serialize(), function(res) {
        alert(res.message);
        if (res.success) {
            // reset only the update fields, keep filters and player selection
            $('#skill_level').val('');
            $('#ranking').html('<option value="">-- Select Ranking --</option>');
            loadRankingTable();
        }
    }, 'json').fail(function() {
        alert('Error saving rating.');
    });
});

$('#refreshBtn').on('click', function() {
    loadPlayers();
    loadRankingTable();
});

// Initialize
$(document).ready(function() {
    loadPlayers();
    loadRankingTable();
    // Run when either filter changes
$('#filter_level, #filter_gender').on('change', function() {
    // Clear dependent fields immediately
    $('#rated_player_id').val('');                  // clear player select
    $('#current_level').val('');                    // clear current level
    $('#current_ranking').val('');                  // clear current ranking
    $('#skill_level').val('');                      // clear update level
    $('#ranking').html('<option value="">-- Select Ranking --</option>'); // reset ranking dropdown
    $('#player_rating_table').html('');             // clear any player rating panel

    // Now reload players and ranking table based on new filters
    loadPlayers();
    loadRankingTable();
});
$('#rated_player_id').on('change', function () {
    const playerId = $(this).val();
    if (!playerId) {
        // cleared by user or by filter change — reset dependent fields
        $('#current_level').val('');
        $('#current_ranking').val('');
        $('#skill_level').val('');
        $('#ranking').html('<option value="">-- Select Ranking --</option>');
        $('#player_rating_table').html('');
        return;
    }
    // existing behavior when a player selected
    loadPlayerRatings(playerId);
});

});
</script>
