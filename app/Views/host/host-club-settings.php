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
        <div class="custom_card p-4 rounded-4 shadow-sm border-0 bg-white" style="border: 1px solid #e2e8f0 !important;">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4" style="border-bottom: 1px solid #f1f5f9;">
                <h5 class="fw-bold m-0 d-flex align-items-center gap-2" style="color: #0f172a; font-size: 1.2rem;">
                    <div class="d-flex align-items-center justify-content-center rounded-3 me-1" style="width: 36px; height: 36px; background: #e0f2fe; color: #0284c7;">
                        <i class="fa-solid fa-house-chimney-window"></i>
                    </div>
                    <?= $club ? 'Edit My Club' : 'Create My Club' ?>
                </h5>
            </div>

            <?php if (isset($_GET['club_saved'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-xs mb-4" role="alert" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0 !important;">
                    <i class="fa-solid fa-circle-check me-2 text-emerald-600"></i> Club settings saved successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['club_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-xs mb-4" role="alert" style="background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3 !important;">
                    <i class="fa-solid fa-triangle-exclamation me-2 text-rose-600"></i> Error: <?= htmlspecialchars($_GET['club_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form id="clubSettingsForm" method="POST" action="../api/save_club_settings.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="row g-3">
                    <!-- Club Name -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="club_name" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Club Name <span class="text-rose-500">*</span></label>
                        <input type="text" class="form-control" id="club_name" name="club_name"
                               placeholder="e.g. Casa Badminton Club" required
                               style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 0.9rem; color: #0f172a;"
                               value="<?= htmlspecialchars($club['club_name'] ?? '') ?>">
                    </div>

                    <!-- Game Type -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="game_type" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Game Type <span class="text-rose-500">*</span></label>
                        <select class="form-select form-control" id="game_type" name="game_type" required style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 0.9rem; color: #0f172a;">
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
                        <label for="category" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Category</label>
                        <select class="form-select form-control" id="category" name="category" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 0.9rem; color: #0f172a;">
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
                        <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <label class="form-label fw-semibold d-block mb-1" style="color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                <i class="fa-solid fa-location-dot text-rose-500 me-1"></i> Club Location (Inherited from Host Profile)
                            </label>
                            <p class="fw-bold m-0" style="font-size: 1.05rem; color: #0f172a;">
                                <?= htmlspecialchars($club['city'] ?? $host_location['CITY'] ?? 'N/A') ?>, 
                                <?= htmlspecialchars($club['province'] ?? $host_location['PROVINCE'] ?? 'N/A') ?>, 
                                <?= htmlspecialchars($club['country'] ?? $host_location['COUNTRY'] ?? 'N/A') ?>
                            </p>
                            <input type="hidden" id="country" name="country" value="<?= htmlspecialchars($club['country'] ?? $host_location['COUNTRY'] ?? '') ?>">
                            <input type="hidden" id="province" name="province" value="<?= htmlspecialchars($club['province'] ?? $host_location['PROVINCE'] ?? '') ?>">
                            <input type="hidden" id="city" name="city" value="<?= htmlspecialchars($club['city'] ?? $host_location['CITY'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Club Info (rich text) -->
                    <div class="col-xl-12 col-lg-12 col-md-12 col-12 mb-3">
                        <label for="club_info" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Club Info</label>
                        <p class="text-muted" style="font-size: 0.78rem; margin-bottom: 6px; color: #64748b;">
                            Displayed when a player clicks "Info" on your club card. You can use HTML for formatting.
                        </p>
                        <textarea class="form-control" id="club_info" name="club_info" rows="7"
                                  style="border-radius: 10px; border: 1px solid #cbd5e1; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 0.85rem; color: #0f172a; background: #f8fafc; padding: 12px; line-height: 1.4;"
                                  placeholder="Describe your club — what you offer, community spirit, etc."><?= htmlspecialchars($club['club_info'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Schedule -->
                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                        <label for="schedule" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Game Schedule</label>
                        <p class="text-muted" style="font-size: 0.78rem; margin-bottom: 6px; color: #64748b;">
                            Displayed in the "Schedule" popup. You can use HTML.
                        </p>
                        <textarea class="form-control" id="schedule" name="schedule" rows="7"
                                  style="border-radius: 10px; border: 1px solid #cbd5e1; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 0.85rem; color: #0f172a; background: #f8fafc; padding: 12px; line-height: 1.4;"
                                  placeholder="e.g. Monday 8pm-10pm at Epic Venue..."><?= htmlspecialchars($club['schedule'] ?? '') ?></textarea>
                    </div>

                    <!-- Cost Info -->
                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                        <label for="cost_info" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Costing Info</label>
                        <p class="text-muted" style="font-size: 0.78rem; margin-bottom: 6px; color: #64748b;">
                            Displayed in the "Costing" popup. You can use HTML.
                        </p>
                        <textarea class="form-control" id="cost_info" name="cost_info" rows="7"
                                  style="border-radius: 10px; border: 1px solid #cbd5e1; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 0.85rem; color: #0f172a; background: #f8fafc; padding: 12px; line-height: 1.4;"
                                  placeholder="e.g. Men Double: 4 players $25 each..."><?= htmlspecialchars($club['cost_info'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Join Type -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="join_type" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Join Permission Type</label>
                        <select class="form-select form-control" id="join_type" name="join_type" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 0.9rem; color: #0f172a;">
                            <option value="A" <?= (($club['join_type'] ?? 'A') === 'A') ? 'selected' : '' ?>>Auto-Join (All players instantly approved)</option>
                            <option value="R" <?= (($club['join_type'] ?? 'A') === 'R') ? 'selected' : '' ?>>Request-Join (Host moderation required)</option>
                            <option value="H" <?= (($club['join_type'] ?? 'A') === 'H') ? 'selected' : '' ?>>Home Location Auto-Join (Match location)</option>
                        </select>
                    </div>

                    <!-- Logo Upload -->
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="club_logo" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Club Logo Image</label>
                        <div class="mb-2 text-center" id="logo-preview-container" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 12px; min-height: 120px; display: flex; align-items: center; justify-content: center;">
                            <?php
                            $club_logo_preview = '';
                            if (!empty($club['logo'])) {
                                $clean_logo = ltrim($club['logo'], '/\\');
                                if (file_exists(__DIR__ . '/../../../' . $clean_logo)) {
                                    $club_logo_preview = '/' . $clean_logo;
                                } elseif (file_exists(__DIR__ . '/../../../uploads/clubs/' . $clean_logo)) {
                                    $club_logo_preview = '/uploads/clubs/' . $clean_logo;
                                } elseif (file_exists(__DIR__ . '/../../../assets/images/logo/' . $clean_logo)) {
                                    $club_logo_preview = '/assets/images/logo/' . $clean_logo;
                                } elseif (strpos($clean_logo, 'assets/') === 0 || strpos($clean_logo, 'uploads/') === 0) {
                                    $club_logo_preview = '/' . $clean_logo;
                                } else {
                                    $club_logo_preview = '/uploads/clubs/' . $clean_logo;
                                }
                            }
                            ?>
                            <?php if (!empty($club_logo_preview)): ?>
                                <img id="logo-preview" src="<?= htmlspecialchars($club_logo_preview) ?>" alt="Club Logo"
                                     onerror="this.onerror=null; this.src='../assets/images/logo/Final-Logo.png';"
                                     style="max-width: 100%; max-height: 110px; object-fit: contain; border-radius: 8px;" />
                            <?php else: ?>
                                <span id="logo-placeholder" style="color: #64748b; font-size: 0.85rem; font-weight: 500;">No logo uploaded</span>
                                <img id="logo-preview" src="" alt="Club Logo" style="max-width: 100%; max-height: 110px; object-fit: contain; border-radius: 8px; display: none;" />
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control" id="club_logo" name="club_logo" accept="image/*" onchange="previewLogo(this)" style="border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem;">
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem; color: #64748b;">JPG, PNG, GIF, WebP. Max 2MB.</small>
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
                        <label for="status" class="form-label fw-semibold" style="color: #334155; font-size: 0.9rem;">Club Status</label>
                        <select class="form-select form-control" id="status" name="status" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 0.9rem; color: #0f172a;">
                            <option value="Active" <?= (($club['status'] ?? 'Active') === 'Active') ? 'selected' : '' ?>>Active (visible to players)</option>
                            <option value="Inactive" <?= (($club['status'] ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive (hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="row align-items-center mt-3 pt-2" style="border-top: 1px solid #f1f5f9;">
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary" id="clubSaveBtn" style="background: linear-gradient(135deg, #0d6efd 0%, #0284c7 100%); border: none; border-radius: 8px; padding: 10px 28px; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);">
                            <?= $club ? 'Update Club' : 'Create Club' ?>
                        </button>
                    </div>
                    <?php if ($club): ?>
                    <div class="col-auto">
                        <span class="text-muted" style="font-size: 0.82rem; color: #64748b;">
                            <i class="fa-regular fa-clock me-1"></i> Last updated: <?= date('M d, Y h:i A', strtotime($club['updated_at'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
