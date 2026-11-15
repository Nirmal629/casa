<?php
// DB connection ($conn) is already provided by inner-header.php

$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)


$select_event_cat = mysqli_query($conn, "select * from ca_event_category where 1");
$host_id = isset($host_id) ? $host_id : (isset($_GET['host_id']) ? intval($_GET['host_id']) : ($_SESSION['mapped_host_id'] ?? 0));

// Audit: record that this player browsed this host's game list (throttled to once / 10 min)
if (function_exists('logPlayerActivity') && !empty($_SESSION['user_id']) && (int) $host_id > 0) {
    $auditKey = 'audit_glv_' . (int) $host_id;
    if (!isset($_SESSION[$auditKey]) || (time() - (int) $_SESSION[$auditKey]) > 600) {
        $_SESSION[$auditKey] = time();
        logPlayerActivity($conn, $_SESSION['user_id'], 'GAME_LIST_VIEWED', 'Browsed game list', (int) $host_id, null);
    }
}
// Get player's sport from session (e.g., "Badminton")
$playerSport = isset($_SESSION['games']) ? trim($_SESSION['games']) : '';

$cardCssPath = __DIR__ . '/../css/sports-session-card.css';
$cardCssVer = file_exists($cardCssPath) ? filemtime($cardCssPath) : '1.0';
?>
<link rel="stylesheet" href="/app/Views/css/sports-session-card.css?v=<?= $cardCssVer ?>">

<!----player-Upcoming-game----->
<div class="mb-4">
    <form onsubmit="return false;">
        <div class="row g-2 align-items-center">
            <input type="hidden" id="host" value="<?= htmlspecialchars((string) $host_id) ?>" />
            <div class="col-auto">
                <select class="form-select" id="event_category" aria-label="Category" data-casa-dropdown data-size="sm"
                    data-icon="fa-solid fa-shapes">
                    <?php
                    $allEventCategories = [];
                    $matchedCategory = '';
                    while ($fetchEventCat = mysqli_fetch_assoc($select_event_cat)) {
                        $catName = $fetchEventCat['NAME'];
                        $allEventCategories[] = $catName;
                        if ($matchedCategory === '' && $playerSport !== '' && strpos(strtolower($catName), strtolower($playerSport)) !== false) {
                            $matchedCategory = $catName;
                        }
                    }

                    // Check requested or matched category
                    $requestedCat = $_GET['event_category'] ?? ($_GET['category'] ?? null);
                    if ($requestedCat !== null) {
                        $selectedCategory = $requestedCat;
                    } else {
                        // Check if host actually has events for this matched category
                        $selectedCategory = $matchedCategory;
                        if (!empty($selectedCategory)) {
                            $checkEvQuery = "SELECT 1 FROM ca_events WHERE HOST_ID='" . (int)$host_id . "' AND STATUS='Active' AND EVENT_CATEGORY='" . mysqli_real_escape_string($conn, $selectedCategory) . "' LIMIT 1";
                            $checkEv = mysqli_query($conn, $checkEvQuery);
                            if (!$checkEv || mysqli_num_rows($checkEv) === 0) {
                                $selectedCategory = ''; // Fallback to 'All' so events are not hidden
                            }
                        }
                    }
                    ?>
                    <option value="" <?= ($selectedCategory === '') ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach ($allEventCategories as $catName): ?>
                        <option value="<?= htmlspecialchars($catName) ?>" <?= ($selectedCategory === $catName) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($catName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select" id="year" aria-label="Year" data-casa-dropdown data-size="sm"
                    data-icon="fa-regular fa-calendar"></select>
            </div>
            <div class="col-auto">
                <select class="form-select" id="month" aria-label="Month" data-casa-dropdown data-size="sm"
                    data-icon="fa-regular fa-calendar-days"></select>
            </div>
        </div>
    </form>
</div>
<div class="discoverGames_wraper plyerGame_wrapper">
    <?php
    $hostController = new \App\Controllers\HostController();
    $hostController->filterSchedule((int) $currentYear, (int) $currentMonth, $selectedCategory, (int) $host_id, true);
    ?>
</div>

<!-----View-modal------->
<section class="customModal_wrap hostgameview_modal hostgameview_modal_upcoming">
    <div class="customModal_body">
        <h6 class="customModal_head">View Players</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <hr />
        <div class="customModal_content">
            <div class="playerList-container" id="playerList_upcoming">
            </div>

        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize popovers using shared global helper
        if (typeof initPopovers === 'function') {
            initPopovers();
        }

        // Copy session details on Share button click
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.copy_session_btn');
            if (!btn) return;
            e.preventDefault();
            const eventId = btn.getAttribute('data-id');
            const dataInput = document.getElementById('data_' + eventId);
            if (!dataInput || !dataInput.value) return;

            try {
                const ev = JSON.parse(dataInput.value);
                const title = ev.EVENT_CATEGORY || 'Sports Match';
                const date = (ev.EVENT_DATE || '') + ' ' + (ev.EVENT_TIME || '');
                const venue = ev.EVENT_VENUE || '';
                const shareText = `🏸 Match: ${title}\n📅 Time: ${date}\n📍 Venue: ${venue}\n🔗 Link: ${window.location.href}`;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shareText).then(() => {
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Session details copied to clipboard!');
                        } else {
                            alert('Session details copied to clipboard!');
                        }
                    }).catch(() => {
                        prompt('Copy session details:', shareText);
                    });
                } else {
                    prompt('Copy session details:', shareText);
                }
            } catch (err) {
                console.error(err);
            }
        });

        // Copy payment email on click
        document.addEventListener('click', function (e) {
            const emailBtn = e.target.closest('.session-email-copy');
            if (!emailBtn) return;
            e.preventDefault();
            e.stopPropagation();
            const email = emailBtn.getAttribute('data-email') || emailBtn.innerText.trim();
            if (!email) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(email).then(() => {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Copied payment email: ' + email);
                    } else {
                        alert('Copied payment email: ' + email);
                    }
                }).catch(() => {
                    prompt('Copy payment email:', email);
                });
            } else {
                prompt('Copy payment email:', email);
            }
        });
    });
</script>