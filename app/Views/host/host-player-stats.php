<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userModel = $userModel ?? new \App\Models\UserModel();
$uniqueLevels = $uniqueLevels ?? $userModel->getAllUniqueVerifiedLevels();
if (empty($uniqueLevels)) {
    $uniqueLevels = ["Beginner", "Amateur", "Intermediate", "Intermediate +", "Advanced"];
}
$uniqueGenders = $uniqueGenders ?? $userModel->getAllUniqueGenders();
if (empty($uniqueGenders)) {
    $uniqueGenders = ['Male', 'Female','kid'];
}
$levelOrder = $levelOrder ?? $uniqueLevels;
$host_id = (int)($host_id ?? ($_SESSION['user_id'] ?? 0));

$hostStatsCssPath = __DIR__ . '/../css/host/host-player-stats.css';
$hostStatsCssVer = file_exists($hostStatsCssPath) ? filemtime($hostStatsCssPath) : '1.0';
$cssPrefix = $assetPrefix ?? (strpos($_SERVER['REQUEST_URI'] ?? '', '/host/') !== false ? '../' : '/');
?>
<link rel="stylesheet" href="<?= e($cssPrefix) ?>assets/css/casa-dropdown.css">
<script src="<?= e($cssPrefix) ?>assets/js/casa-dropdown.js" defer></script>
<script src="<?= e($cssPrefix) ?>assets/js/casa-pagination.js" defer></script>
<link rel="stylesheet" href="<?= e($cssPrefix) ?>app/Views/css/host/host-player-stats.css?v=<?= $hostStatsCssVer ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script>window.currentHostId = <?= (int)$host_id ?>;</script>

<main class="shell player-stats-shell" data-host-id="<?= $host_id ?>">
  <!-- Panel & Toolbar Section -->
  <section class="panel">
    <div class="toolbar">
      <div class="toolbar-title">
        <h2>Player Activity & Rating List</h2>
        <small>Manage player activity, skill levels and ranking</small>
      </div>
      <div class="filters-row">
        <div class="search-box-wrap">
          <i class="bi bi-search"></i>
          <input id="playerStatsSearch" class="form-control" placeholder="Search player name..." autocomplete="off">
        </div>

        <select id="levelFilter" name="levelFilter" class="form-select" data-casa-dropdown data-size="sm">
          <option value="">Skill Level</option>
          <?php foreach ($uniqueLevels as $lvl): ?>
            <?php if (!empty($lvl)): ?>
              <option value="<?= e($lvl) ?>"><?= e($lvl) ?></option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>

        <select id="statusFilter" name="statusFilter" class="form-select" data-casa-dropdown data-size="sm">
          <option value="">All Status</option>
          <option value="Active">Active</option>
          <option value="Regular">Regular</option>
          <option value="Inactive">Inactive</option>
          <option value="New">New</option>
        </select>

        <select id="genderFilter" name="genderFilter" class="form-select" data-casa-dropdown data-size="sm">
          <option value="">Gender</option>
          <?php foreach ($uniqueGenders as $g): ?>
            <?php if (!empty($g)): ?>
              <option value="<?= e($g) ?>"><?= e($g) ?></option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-wrap">
      <table class="table table-hover mb-0" id="mainPlayerTable">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th>Player</th>
            <th>Skill Level</th>
            <th>Rank</th>
            <th class="text-center">Mon</th>
            <th class="text-center">Tue</th>
            <th class="text-center">Wed</th>
            <th class="text-center">Thu</th>
            <th class="text-center">Fri</th>
            <th class="text-center">Sat</th>
            <th class="text-center">Sun</th>
            <th class="text-center">Total Games</th>
            <th class="text-center">Last Played</th>
            <th class="text-center">Days Ago</th>
            <th class="text-center">Status</th>
            <th>Preferred</th>
            <th class="text-center" style="width: 100px;">Action</th>
          </tr>
        </thead>
        <tbody id="rows">
          <tr class="loading-row">
            <td colspan="17" class="text-center py-5 text-secondary">
              <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
              Loading player activity &amp; ratings...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Generic Pagination Footer Container -->
    <div id="count"></div>
  </section>
</main>

<!-- Edit Rating Modal -->
<div class="modal fade" id="ratingModal" data-host-id="<?= $host_id ?>" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="modalTitle">Update Player Rating</h2>
          <div class="small opacity-75" id="modalSubtitle"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="ratingForm">
        <input type="hidden" id="rated_player_id" name="rated_player_id" value="">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label" for="newLevel">New Skill Level</label>
              <select id="newLevel" name="skill_level" class="form-select" required>
                <option value="">Select skill level</option>
                <?php foreach ($levelOrder as $lvl): ?>
                  <option value="<?= e($lvl) ?>"><?= e($lvl) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label" for="newRank">New Rank</label>
              <select id="newRank" name="ranking" class="form-select" required>
                <option value="">Select rank</option>
              </select>
            </div>
          </div>

          <div id="feedback" class="small mt-3" role="status" aria-live="polite"></div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
          <button type="submit" id="saveBtn" class="btn btn-primary btn-sm px-4"><i class="bi bi-check2-circle me-1"></i>Save Rating</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$hostStatsJsPath = __DIR__ . '/../../script/host-player-stats.js';
$hostStatsJsVer = file_exists($hostStatsJsPath) ? filemtime($hostStatsJsPath) : '1.0';
$jsPrefix = $assetPrefix ?? (strpos($_SERVER['REQUEST_URI'] ?? '', '/host/') !== false ? '../' : '/');
?>
<script defer src="<?= e($jsPrefix) ?>app/script/host-player-stats.js?v=<?= $hostStatsJsVer ?>"></script>
