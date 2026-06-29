<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/dbConnection.php';
date_default_timezone_set('Asia/Kolkata');

$currentUserId = $_SESSION['user_id'] ?? 0;

// Level Hierarchy
$levelOrder = ["All", "Beginner", "Amateur", "Intermediate", "Intermediate +", "Advanced"];
?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-2">
        <!--<h2 class="mb-4">Player Rating Management</h2>-->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-primary">The Player Rating Management</h6>
            <button id="refreshBtn" class="btn btn-sm btn-outline-secondary py-0" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>        

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

        <div class="overflow-auto">
            <div class="d-flex align-items-end gap-1 text-nowrap pb-2">
                
                <div class="d-flex gap-1 p-1 bg-light rounded border align-items-end">
                    
                    <div style="min-width: 110px; flex-shrink: 0;">
                        <label class="form-label mb-0 small fw-bold text-muted" style="font-size: 0.7rem;">Skill</label>
                        <select id="filter_level" class="form-select form-select-sm" style="height: 31px; font-size: 0.75rem; padding-top: 2px; padding-bottom: 2px;">
                            <option value="">Level</option>
                            <?php foreach ($levelOrder as $level){
                                $selected = ($level == 'Intermediate +') ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($level)."' $selected>".htmlspecialchars($level)."</option>"; 
                            } ?>
                        </select>
                    </div>
        
                    <div style="min-width: 90px; flex-shrink: 0;">
                        <label class="form-label mb-0 small fw-bold text-muted" style="font-size: 0.7rem;">Gender</label>
                        <select id="filter_gender" class="form-select form-select-sm" style="height: 31px; font-size: 0.75rem; padding-top: 2px; padding-bottom: 2px;">
                            <option value="">All</option>
                            <option value="Male" selected>Male</option> <option value="Female">Female</option>
                        </select>
                    </div>
        
                </div>
            </div>    
        </div>
                
        <div class="single-row-wrap overflow-auto"> 
            <form id="ratingForm" class="d-flex align-items-end gap-1 p-1 border rounded bg-light text-nowrap">
        
                <div class="sr-field" style="width:130px; flex-shrink: 0;">
                    <label class="form-label mb-0 small fw-bold text-success" style="font-size: 0.7rem;">Player</label>
                    <select name="rated_player_id" id="rated_player_id" class="form-select form-select-sm" style="height: 31px; font-size: 0.75rem; padding-top: 2px; padding-bottom: 2px;"></select>
                </div>
        
                <div class="sr-field" style="width:85px; flex-shrink: 0;">
                    <label class="form-label mb-0 small fw-bold text-success" style="font-size: 0.7rem;">Level</label>
                    <input type="text" id="current_level" class="form-control form-control-sm bg-light text-center" readonly style="height: 31px; font-size: 0.75rem;">
                </div>
        
                <div class="sr-field" style="width:75px; flex-shrink: 0;">
                    <label class="form-label mb-0 small fw-bold text-success" style="font-size: 0.7rem;">Rank</label>
                    <input type="text" id="current_ranking" class="form-control form-control-sm bg-light text-center" readonly style="height: 31px; font-size: 0.75rem;">
                </div>
        
                <div class="sr-field" style="width:115px; flex-shrink: 0;">
                    <label class="form-label mb-0 small fw-bold text-primary" style="font-size: 0.7rem;">New Level</label>
                    <select name="skill_level" id="skill_level" class="form-select form-select-sm border-primary" style="height: 31px; font-size: 0.75rem; padding-top: 2px; padding-bottom: 2px;">
                        <option value="">Select</option>
                        <?php foreach ($levelOrder as $level) {
                            $selected = ($level == 'Intermediate+') ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($level)."' $selected>".htmlspecialchars($level)."</option>"; 
                        } ?>
                    </select>
                </div>
        
                <div class="sr-field" style="width:95px; flex-shrink: 0;">
                    <label class="form-label mb-0 small fw-bold text-primary" style="font-size: 0.7rem;">New Rank</label>
                    <select name="ranking" id="ranking" class="form-select form-select-sm border-primary" style="height: 31px; font-size: 0.75rem; padding-top: 2px; padding-bottom: 2px;"></select>
                </div>
        
                <div class="sr-field" style="flex-shrink: 0;">
                    <button type="submit" class="btn btn-success btn-sm d-flex align-items-center justify-content-center" id="rating_submit" title="Submit" style="height: 31px; width: 40px; padding: 0; margin-bottom: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.552 2.55 5.92-5.903z"/>
                        </svg>
                    </button>
                </div>
        
            </form>
        </div>


        <div id="player_rating_table" class="mb-4"></div>

        <div class="custom_card">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="card_heading">The Rank List</h6>
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

        // if (data.ratings?.length) {
        //     let list = "<table class='table table-bordered table-sm'><thead><tr><th>Rater</th><th>Level</th><th>Ranking</th></tr></thead><tbody>";
        //     data.ratings.forEach(r => {
        //         list += `<tr><td>${r.rater}</td><td>${r.level}</td><td>${r.ranking}</td></tr>`;
        //     });
        //     list += "</tbody></table>";
        //     $('#player_rating_table').html(list);
        // } else {
        //     $('#player_rating_table').html('');
        // }
        
if (data.ratings?.length) {
    let list = `
        <div class="mt-3" style="max-width: 450px;"> <div class="card border shadow-sm">
                <div class="card-header bg-light border-bottom py-2 d-flex justify-content-between align-items-center">
                    <span class="small text-muted" style="letter-spacing: 0.5px;">The Rating History</span>
                    <span class="badge bg-secondary rounded-pill" style="font-size: 0.6rem;">${data.ratings.length} Entries</span>
                </div>
                
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm align-middle mb-0" style="font-size: 0.75rem;">
                        <thead class="bg-light sticky-top"> <tr>
                                <th class="ps-3 border-0">Rated By</th>
                                <th class="text-center border-0">Level</th>
                                <th class="text-center border-0">Rank</th>
                            </tr>
                        </thead>
                        <tbody>`;
    
    data.ratings.forEach(r => {
        list += `
            <tr class="border-bottom-0">
                <td class="ps-3 py-2">
                    <div class="fw-bold text-dark">${r.rater}</div>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">${r.level}</span>
                </td>
                <td class="text-center fw-bold text-primary">${r.ranking}</td>
            </tr>`;
    });

    list += `</tbody></table></div></div></div>`;
    $('#player_rating_table').html(list);
} else {
    $('#player_rating_table').html(`
        <div class="mx-auto mt-2 text-center p-2 border rounded bg-light" style="max-width: 450px;">
            <small class="text-muted">No rating history.</small>
        </div>
    `);
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
