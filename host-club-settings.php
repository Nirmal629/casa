<?php
/**
     * Host Club Settings Tab
     * Included inside host-dashboard.php tab panel.
     * Allows host to create or edit their club info (one club per host).
     *
     * Available from parent: $conn (mysqli), $_SESSION['user_id'], $_SESSION['name']
     */

    // Fetch existing club for this host (if any)
    $club = null;
    $club_query = mysqli_query($conn, "SELECT * FROM ca_clubs WHERE host_id = '" . intval($_SESSION['user_id']) . "' LIMIT 1");
    if (!$club_query) {
        echo "<div class='alert alert-danger'>DB Error (ca_clubs): " . mysqli_error($conn) . "</div>";
    } elseif (mysqli_num_rows($club_query) > 0) {
        $club = mysqli_fetch_assoc($club_query);
    }

    // Fetch host's location from ca_users (for pre-fill)
    $host_location = ['COUNTRY' => '', 'PROVINCE' => '', 'CITY' => ''];
    $host_user = mysqli_query($conn, "SELECT COUNTRY, PROVINCE, CITY FROM ca_users WHERE ID = '" . intval($_SESSION['user_id']) . "'");
    if (!$host_user) {
        echo "<div class='alert alert-danger'>DB Error (ca_users): " . mysqli_error($conn) . "</div>";
    } else {
        $fetched = mysqli_fetch_assoc($host_user);
        if ($fetched) {
            $host_location = $fetched;
        }
    }
    ?>

    <div class="newgame_host">
        <div class="custom_card">
            <h6 class="card_heading"><?= $club ? 'Edit My Club' : 'Create My Club' ?></h6>

            <?php if (isset($_GET['club_saved'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Club settings saved successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['club_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error: <?= htmlspecialchars($_GET['club_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form id="clubSettingsForm" method="POST" action="api/save_club_settings.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="row">
                    <!-- Club Name -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="club_name" class="form-label">Club Name<span>*</span></label>
                        <input type="text" class="form-control" id="club_name" name="club_name"
                               placeholder="e.g. Casa Badminton Club" required
                               value="<?= htmlspecialchars($club['club_name'] ?? '') ?>">
                    </div>

                    <!-- Game Type -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="game_type" class="form-label">Game Type<span>*</span></label>
                        <select class="form-select form-control" id="game_type" name="game_type" required>
                            <?php
                            $game_types = ['Badminton', 'Football', 'Cricket', 'Tennis', 'Basketball', 'Other'];
                            foreach ($game_types as $gt):
                                $selected = (($club['game_type'] ?? 'Badminton') === $gt) ? 'selected' : '';
                            ?>
                                <option value="<?= $gt ?>" <?= $selected ?>><?= $gt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select form-control" id="category" name="category">
                            <option value="">-- Select --</option>
                            <?php
                            $categories = ['Recreational', 'Competitive', 'Social', 'Training', 'Family'];
                            foreach ($categories as $cat):
                                $selected = (($club['category'] ?? '') === $cat) ? 'selected' : '';
                            ?>
                                <option value="<?= $cat ?>" <?= $selected ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Club Location (Inherited) -->
                    <div class="col-xl-12 col-12 mb-3">
                        <label class="form-label" style="color: #94a3b8;">Club Location (Inherited from Host Profile)</label>
                        <p class="text-white fw-bold m-0" style="font-size: 1rem;">
                            <?= htmlspecialchars($club['city'] ?? $host_location['CITY'] ?? 'N/A') ?>, 
                            <?= htmlspecialchars($club['province'] ?? $host_location['PROVINCE'] ?? 'N/A') ?>, 
                            <?= htmlspecialchars($club['country'] ?? $host_location['COUNTRY'] ?? 'N/A') ?>
                        </p>
                        <input type="hidden" id="country" name="country" value="<?= htmlspecialchars($club['country'] ?? $host_location['COUNTRY'] ?? '') ?>">
                        <input type="hidden" id="province" name="province" value="<?= htmlspecialchars($club['province'] ?? $host_location['PROVINCE'] ?? '') ?>">
                        <input type="hidden" id="city" name="city" value="<?= htmlspecialchars($club['city'] ?? $host_location['CITY'] ?? '') ?>">
                    </div>
                </div>

                <div class="row">
                    <!-- Club Info (rich text) -->
                    <div class="col-xl-12 col-lg-12 col-md-12 col-12 mb-3">
                        <label for="club_info" class="form-label">Club Info</label>
                        <p class="text-muted" style="font-size: 0.75rem; margin-bottom: 4px;">
                            Displayed when a player clicks "Info" on your club card. You can use HTML for formatting.
                        </p>
                        <textarea class="form-control" id="club_info" name="club_info" rows="6"
                                  placeholder="Describe your club — what you offer, community spirit, etc."><?= htmlspecialchars($club['club_info'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <!-- Schedule -->
                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                        <label for="schedule" class="form-label">Game Schedule</label>
                        <p class="text-muted" style="font-size: 0.75rem; margin-bottom: 4px;">
                            Displayed in the "Schedule" popup. You can use HTML.
                        </p>
                        <textarea class="form-control" id="schedule" name="schedule" rows="6"
                                  placeholder="e.g. Monday 8pm-10pm at Epic Venue..."><?= htmlspecialchars($club['schedule'] ?? '') ?></textarea>
                    </div>

                    <!-- Cost Info -->
                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                        <label for="cost_info" class="form-label">Costing Info</label>
                        <p class="text-muted" style="font-size: 0.75rem; margin-bottom: 4px;">
                            Displayed in the "Costing" popup. You can use HTML.
                        </p>
                        <textarea class="form-control" id="cost_info" name="cost_info" rows="6"
                                  placeholder="e.g. Men Double: 4 players $25 each..."><?= htmlspecialchars($club['cost_info'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <!-- Join Type -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="join_type" class="form-label">Join Permission Type</label>
                        <select class="form-select form-control" id="join_type" name="join_type">
                            <option value="A" <?= (($club['join_type'] ?? 'A') === 'A') ? 'selected' : '' ?>>Auto-Join (All players instantly approved)</option>
                            <option value="R" <?= (($club['join_type'] ?? 'A') === 'R') ? 'selected' : '' ?>>Request-Join (Host moderation required)</option>
                            <option value="H" <?= (($club['join_type'] ?? 'A') === 'H') ? 'selected' : '' ?>>Home Location Auto-Join (Match location)</option>
                        </select>
                    </div>

                    <!-- Logo Upload -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="club_logo" class="form-label">Club Logo Image</label>
                        <div class="mb-2 text-center" id="logo-preview-container" style="background: #1e293b; border: 2px dashed #334155; border-radius: 12px; padding: 12px; min-height: 120px; display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($club['logo'])): ?>
                                <img id="logo-preview" src="uploads/clubs/<?= htmlspecialchars($club['logo']) ?>" alt="Club Logo"
                                     style="max-width: 100%; max-height: 120px; object-fit: contain; border-radius: 8px;" />
                            <?php else: ?>
                                <span id="logo-placeholder" style="color: #64748b; font-size: 0.85rem;">No logo uploaded</span>
                                <img id="logo-preview" src="" alt="Club Logo" style="max-width: 100%; max-height: 120px; object-fit: contain; border-radius: 8px; display: none;" />
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control" id="club_logo" name="club_logo" accept="image/*" onchange="previewLogo(this)">
                        <small class="text-muted" style="font-size: 0.75rem;">JPG, PNG, GIF, WebP. Max 2MB.</small>
                    </div>
                    <script>
                    function previewLogo(input) {
                        if (input.files && input.files[0]) {
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var preview = document.getElementById('logo-preview');
                                var placeholder = document.getElementById('logo-placeholder');
                                preview.src = e.target.result;
                                preview.style.display = 'block';
                                if (placeholder) placeholder.style.display = 'none';
                            };
                            reader.readAsDataURL(input.files[0]);
                        }
                    }
                    </script>

                    <!-- Status -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="status" class="form-label">Club Status</label>
                        <select class="form-select form-control" id="status" name="status">
                            <option value="Active" <?= (($club['status'] ?? 'Active') === 'Active') ? 'selected' : '' ?>>Active (visible to players)</option>
                            <option value="Inactive" <?= (($club['status'] ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive (hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary" id="clubSaveBtn">
                            <?= $club ? 'Update Club' : 'Create Club' ?>
                        </button>
                    </div>
                    <?php if ($club): ?>
                    <div class="col-auto">
                        <span class="text-muted" style="font-size: 0.8rem; line-height: 2.5;">
                            Last updated: <?= date('M d, Y h:i A', strtotime($club['updated_at'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
