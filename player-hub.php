<!-- Style to fix the white input background issue -->

<style>
    /* Styling for inputs inside the modal to match your theme */

    #PaymentModal .form-control,
    #PaymentModal .form-select {

        background: #0f172a !important;

        color: white !important;

        border: 1px solid #334155 !important;

    }

    #PaymentModal label {

        color: #94a3b8;

        margin-bottom: 5px;

        font-size: 0.9rem;

    }

    /* Success Overlay Style */

    #successOverlay {

        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;

        background: rgba(15, 23, 42, 0.98);

        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;

    }

    .success-card {

        background: #1f2937;
        padding: 50px;
        border-radius: 24px;
        text-align: center;

        border: 1px solid #334155;
        max-width: 500px;
        width: 90%;

    }
</style>

<?php

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}

// This line MUST be here to create the security key for the form

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

error_reporting(E_ALL & ~E_NOTICE);

ini_set('display_errors', 0);

include "dbConnection_PDO.php";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES => false,

];

try {

    $pdo = new PDO($dsn, $user, $pass, $options);

    $current_user_id = $_SESSION['user_id'] ?? null;

    if (!$current_user_id) {

        header("Location: index.php");

        exit();
    }

    // Fetch user premium status

    $premium_check = $pdo->prepare("SELECT PREMIUM FROM ca_users WHERE ID = ?");

    $premium_check->execute([$current_user_id]);

    $user_premium = $premium_check->fetchColumn() ?: 'N';

    // Fetch player's preferred game type, location and profile info
    $player_pref_stmt = $pdo->prepare("SELECT GAMES, COUNTRY, PROVINCE, CITY, PROFILE_IMAGE, GENDER, VERIFIED_LEVEL, LEVEL, NAME FROM ca_users WHERE ID = ?");
    $player_pref_stmt->execute([$current_user_id]);
    $player_prefs = $player_pref_stmt->fetch();
    $player_game = $player_prefs['GAMES'] ?? 'Badminton';
    $player_country = $player_prefs['COUNTRY'] ?? '';
    $player_province = $player_prefs['PROVINCE'] ?? '';
    $player_city = $player_prefs['CITY'] ?? '';
    if (!empty($player_prefs['PROFILE_IMAGE'])) {
        $_SESSION['profileImage'] = $player_prefs['PROFILE_IMAGE'];
    }

    // Fetch active clubs matching player's game preference AND location
    $clubs_sql = "SELECT c.*, u.NAME as host_name, u.PROFILE_IMAGE as host_img
                  FROM ca_clubs c
                  JOIN ca_users u ON c.host_id = u.ID
                  WHERE c.status = 'Active'
                    AND c.game_type = :game_type";
    
    $params = ['game_type' => $player_game];

    if (!empty($player_country)) {
        $clubs_sql .= " AND c.country = :country";
        $params['country'] = $player_country;
    }
    if (!empty($player_province)) {
        $clubs_sql .= " AND c.province = :province";
        $params['province'] = $player_province;
    }
    if (!empty($player_city)) {
        $clubs_sql .= " AND c.city = :city";
        $params['city'] = $player_city;
    }

    $clubs_sql .= " ORDER BY c.created_at DESC LIMIT 8";

    $clubs_stmt = $pdo->prepare($clubs_sql);
    $clubs_stmt->execute($params);
    $clubs = $clubs_stmt->fetchAll();

    // Check membership for each club in ca_player_club_status
    $status_stmt = $pdo->prepare("SELECT status FROM ca_player_club_status WHERE player_id = ? AND club_id = ? LIMIT 1");
    foreach ($clubs as &$club_row) {
        $status_stmt->execute([$current_user_id, $club_row['id']]);
        $club_status = $status_stmt->fetchColumn();
        $club_row['membership_status'] = $club_status ?: 'not_joined';
    }
    unset($club_row); // break reference

    if (!function_exists('get_club_logo_url')) {
        function get_club_logo_url($logo) {
            $logo = trim((string)$logo);
            if ($logo === '') {
                return 'assets/images/logo/Final-Logo.png';
            }
            $clean_logo = ltrim($logo, '/\\');
            if (strpos($clean_logo, 'assets/') === 0 || strpos($clean_logo, 'uploads/') === 0) {
                if (file_exists($clean_logo)) {
                    return $clean_logo;
                }
                if (file_exists($clean_logo . '.png')) {
                    return $clean_logo . '.png';
                }
                if (file_exists($clean_logo . '.jpg')) {
                    return $clean_logo . '.jpg';
                }
                return $clean_logo;
            }
            if (file_exists('uploads/clubs/' . $clean_logo)) {
                return 'uploads/clubs/' . $clean_logo;
            }
            if (file_exists('assets/images/logo/' . $clean_logo)) {
                return 'assets/images/logo/' . $clean_logo;
            }
            if (file_exists('assets/images/logo/' . $clean_logo . '.png')) {
                return 'assets/images/logo/' . $clean_logo . '.png';
            }
            if (file_exists('assets/images/' . $clean_logo)) {
                return 'assets/images/' . $clean_logo;
            }
            return 'uploads/clubs/' . $clean_logo;
        }
    }

    // SQL QUERY - Corrected with unique placeholders

    $sql = "SELECT e.*, b.IMGAE, 

            (SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = e.ID) as joined_count,

            (SELECT COUNT(*) FROM to_users tu 

             JOIN to_teams tt ON tu.TEAM_ID = tt.ID 

             WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid1) as is_joined,

            (SELECT tp.STATUS FROM to_payments tp 

             JOIN to_users tu ON tp.USER_ID = tu.ID

             JOIN to_teams tt ON tu.TEAM_ID = tt.ID

             WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid2 LIMIT 1) as pay_status,

            (SELECT tp.APPROVED_BY FROM to_payments tp 

             JOIN to_users tu ON tp.USER_ID = tu.ID

             JOIN to_teams tt ON tu.TEAM_ID = tt.ID

             WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid3 LIMIT 1) as pay_approved

            FROM to_tournaments e 

            LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 

            WHERE e.STATUS = 'Active' AND e.EVENT_DATE >= CURDATE() 

            ORDER BY e.ID DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        'uid1' => $current_user_id,

        'uid2' => $current_user_id,

        'uid3' => $current_user_id

    ]);

    $upcomingTournaments = $stmt->fetchAll();
} catch (Exception $e) {

    $upcomingTournaments = [];
}

include "includes/inner-header.php";

// Access control

if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'Player') {

    echo "<script>window.location.href='index.php';</script>";

    exit();
}

?>

<!-----player-dashboard------->

<style>
    .tournamentCard {

        display: flex;

        background: #1f2937;

        border-radius: 12px;

        overflow: hidden;

        margin-bottom: 15px;

        border: 1px solid #334155;

        padding: 0;

        box-shadow: none;

    }

    .tournamentCard .banner {

        width: 132px;

        flex-shrink: 0;

        align-self: stretch;

    }

    .tournamentCard .banner img {

        width: 100%;

        height: 100%;

        object-fit: cover;

    }

    .tournamentCard .card-body {

        flex: 1;

        padding: 14px;

        min-width: 0;

    }

    /* Tournament card: tidy info rows + full-size stacked buttons (match production) */
    .tournamentCard .content {
        align-items: flex-start;
        gap: 14px;
    }

    .tournamentCard .info-grid {
        flex: 1 1 auto;
        min-width: 0;
        row-gap: 7px;
        column-gap: 16px;
    }

    .tournamentCard .info {
        font-size: 12px;
        white-space: normal;
    }

    .tournamentCard .info-grid .info:nth-child(3) {
        flex-basis: 100%;
    }

    .tournamentCard .actions.deskView {
        flex-direction: column;
        align-items: stretch;
        flex-shrink: 0;
        width: 92px;
        gap: 6px;
    }

    .tournamentCard .actions.deskView .joinbtn {
        font-size: 11px;
        padding: 5px 6px;
        text-align: center;
        border-radius: 8px;
    }

    .playerhub_sec .panel .scrollbox .card {

        display: flex;

        flex-direction: row;

        justify-content: space-between;

        gap: 10px;

    }

    .playerhub_sec .panel h3 {

        font-weight: 700;

    }

    .dossierCard .win .winParaDiv p {

        display: flex;

        flex-wrap: wrap;

        gap: 5px;

        margin-bottom: 6px;

    }

    .dossierCard .win .winParaDiv .small {

        padding-left: 4px;

    }

    /* ---- Dossier card polish (spacing / alignment only, colours unchanged) ---- */
    .dossierCard {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .dossierCard .stats,
    .dossierCard .win,
    .dossierCard .block {
        margin: 0;
    }

    /* Equal, centred stat tiles */
    .dossierCard .stats .statInnDiv {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 58px;
        line-height: 1.25;
    }

    .dossierCard .stats .statInnDiv span {
        margin-bottom: 2px;
    }

    /* Win-ratio block: keep text clear of the corner marker, tidy the rows */
    .dossierCard .win {
        padding-right: 26px;
    }

    .dossierCard .win .winParaDiv p {
        align-items: baseline;
        row-gap: 2px;
    }

    .dossierCard .win .winParaDiv small {
        flex-basis: 100%;
        padding-left: 0;
    }

    .dossierCard .win > p {
        margin: 4px 0 0;
    }

    /* "Upcoming" markers read as a subtle tag rather than a floating glyph */
    .dossierCard .upcmngBtn {
        opacity: 0.5;
        pointer-events: none;
    }

    .dossierCard .block {
        align-items: center;
        gap: 8px;
    }

    .dossierCard .block h4 {
        flex-shrink: 0;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .dossierCard .block h4 img {
        opacity: 0.5;
        vertical-align: middle;
    }

    /* Tournaments / Skill Badges mini-sliders: readable rounded pill, not a blob */
    .dossierCard .tournamentBtm,
    .dossierCard .badges {
        width: calc(100% - 92px);
        flex: 1 1 auto;
        min-width: 0;
    }

    .dossierCard .tourSlider,
    .dossierCard .badgeSlider {
        padding-left: 22px;
        padding-right: 22px;
    }

    .dossierCard .tourCard,
    .dossierCard .badgeCard {
        padding: 6px 10px;
        border-radius: 10px;
    }

    .dossierCard .tourCard {
        background: rgba(56, 189, 248, 0.12);
    }

    .dossierCard .tourSpan {
        font-size: 10px;
        line-height: 1.35;
    }

    .dossierCard .tourSlider .slick-arrow,
    .dossierCard .badgeSlider .slick-arrow {
        width: 18px;
        height: 18px;
    }

    @media(max-width: 768px) {
        .tournamentCard {
            flex-direction: column;
        }

        .tournamentCard .banner {
            width: 100%;
            height: 140px;
        }
    }
</style>

<!-----Anurag Added for icon and text------->
<style>
    /* 1: The Eye-Catcher Card */
    .card {
        background: #fff;
        border-radius: 12px;
        padding: 12px 12px 8px 12px;
        /* Tightened bottom padding */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef0f2;
        margin-bottom: 15px;
        font-family: 'Segoe UI', Roboto, sans-serif;
    }

    .card h4 {
        margin: 0 0 10px 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #ffffff;
        /* Club name is now white */
        letter-spacing: -0.02em;
        text-align: left;
    }

    /* 2: The Order Info Capsule */
    .order-capsule {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.08);
        /* Glass effect */
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 50px;
        padding: 2px 8px;
        margin-bottom: 10px;
        font-size: 0.55rem;
        color: #cbd5e1;
    }

    .order-capsule b {
        color: #fff;
        font-weight: 700;
    }

    .order-capsule .divider {
        opacity: 0.3;
        color: #fff;
    }

    /* Dynamic Status Pill inside Capsule */
    .stat-pill {
        padding: 1px 5px;
        border-radius: 3px;
        text-transform: uppercase;
        font-weight: 900;
        font-size: 0.48rem;
    }

    .stat-completed {
        background: #166534;
        color: #4ade80;
    }

    .stat-pending {
        background: #854d0e;
        color: #fbbf24;
    }

    .stat-default {
        background: #334155;
        color: #cbd5e1;
    }

    /* Unified straight-line container */
    .action-bar-unified {
        display: flex;
        align-items: center;
        gap: 4px;
        width: 100%;
        margin-bottom: 0;
    }

    /* Compact Stacked Design - 10% smaller */
    .btn-stacked-synced {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        padding: 6px 2px;
        font-size: 0.58rem;
        font-weight: 800;
        /* Extra bold text to match icons */
        text-transform: uppercase;
        color: #333;
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 8px;
        text-decoration: none;
        min-height: 45px;
    }

    /* Bold Icon Styling */
    .btn-stacked-synced svg {
        width: 18px;
        height: 18px;
        margin-bottom: 3px;
        color: #0d6efd;
        stroke-width: 0.5px;
        /* Adds slight weight */
        stroke: #0d6efd;
    }

    .btn-stacked-synced.status-member {
        border-color: #198754;
        color: #198754;
        background-color: #f0fff4;
    }

    .btn-stacked-synced.status-member svg {
        color: #198754;
        stroke: #198754;
    }

    .btn-stacked-synced.action-view {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    .btn-stacked-synced.action-view svg {
        color: white;
        stroke: white;
    }

    /* Keep the club-card action bar the same size / right edge on every card */
    .playerhub_sec .panel .scrollbox .card > .d-flex {
        flex: 1 1 auto;
        min-width: 0;
    }

    .playerhub_sec .panel .scrollbox .card > .content {
        flex: 0 1 280px;
    }

    .playerhub_sec .panel .scrollbox .card > .d-flex > div {
        min-width: 0;
    }

    .playerhub_sec .panel .scrollbox .card > .d-flex h4,
    .playerhub_sec .panel .scrollbox .card > .d-flex > div > div {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<section class="playerhub_sec bothSide_gap">

    <div class="cust_container">

        <div class="wraper">

            <div class="header_box">
                <div class="header_Box_left">
                    <span><i class="fa-solid fa-house-chimney-user"></i></span>
                    <span>PLAYER HUB Welcome back, Player !</span>
                </div>
                <div class="header_Box_Right">

                    <!-- Country -->
                    <select name="country" disabled style="-webkit-appearance: none;">
                        <option value="">--Country--</option>
                        <?php if (!empty($player_country)): ?>
                            <option value="<?= htmlspecialchars($player_country) ?>" selected>
                                <?= htmlspecialchars($player_country) ?>
                            </option>
                        <?php endif; ?>
                    </select>

                    <!-- Province -->
                    <select name="province" disabled style="-webkit-appearance: none;">
                        <option value="">--Province--</option>
                        <?php if (!empty($player_province)): ?>
                            <option value="<?= htmlspecialchars($player_province) ?>" selected>
                                <?= htmlspecialchars($player_province) ?>
                            </option>
                        <?php endif; ?>
                    </select>

                    <!-- City -->
                    <select name="area" disabled style="-webkit-appearance: none;">
                        <option value="">--City--</option>
                        <?php if (!empty($player_city)): ?>
                            <option value="<?= htmlspecialchars($player_city) ?>" selected>
                                <?= htmlspecialchars($player_city) ?>
                            </option>
                        <?php endif; ?>
                    </select>

                </div>
            </div>

            <div class="main-grid">

                <!-- LEFT -->

                <div class="panel left-panel">

                    <h3 style="text-align: center;">The Casa Dossier</h3>

                    <div class="dossierCard">

                        <?php
                        $userProfileImg = !empty($player_prefs['PROFILE_IMAGE']) ? $player_prefs['PROFILE_IMAGE'] : ($_SESSION['profileImage'] ?? '');

                        $imagePath = 'assets/images/profile.jpg';
                        if (!empty($userProfileImg)) {
                            $clean_img = ltrim($userProfileImg, '/\\');
                            if (file_exists('profile_img/' . $clean_img)) {
                                $imagePath = 'profile_img/' . $clean_img;
                            } elseif (file_exists($clean_img)) {
                                $imagePath = $clean_img;
                            } else {
                                $imagePath = 'profile_img/' . $clean_img;
                            }
                        }
                        ?>

                        <div class="profileDetails">

                            <!-- Profile -->

                            <div class="profile">

                                <img src="<?= htmlspecialchars($imagePath) ?>" alt="Profile Image" onerror="this.onerror=null; this.src='assets/images/profile.jpg';">

                            </div>

                            <!-- Info -->

                            <div class="info">

                                <p><b>Name:</b> <?= htmlspecialchars($player_prefs['NAME'] ?? $_SESSION['name'] ?? 'N/A') ?></p>

                                <p><b>Gender:</b> <?= htmlspecialchars($player_prefs['GENDER'] ?? $_SESSION['gender'] ?? 'N/A') ?></p>

                                <p><b>Level:</b> <?= htmlspecialchars($player_prefs['VERIFIED_LEVEL'] ?? $_SESSION['vlevel'] ?? $player_prefs['LEVEL'] ?? 'N/A') ?></p>

                                <p><b>Area:</b> <?= htmlspecialchars($player_city ?: 'N/A') ?></p>

                            </div>

                        </div>

                        <!-- Stats -->

                        <div class="stats">

                            <?php

                            try {

                                $sqlsession = "SELECT COUNT(*) AS total_session FROM ca_gamejoin WHERE USER_ID = :user_id";

                                $stmt = $pdo->prepare($sqlsession);
                                $stmt->execute(['user_id' => $current_user_id]);

                                $resultsession = $stmt->fetch(PDO::FETCH_ASSOC);

                                $totalSessions = (int) $resultsession['total_session'];

                                ?>

                                <div class="statInnDiv"><span><?= $totalSessions ?></span>Total Sessions</div>

                                <div class="statInnDiv"><span><?= $totalSessions * 12 ?></span>Total Games</div>

                                <div class="statInnDiv">
                                    <div class="upcmngBtn">
                                        <img src="assets/images/upcoming.png" alt="img">
                                    </div>
                                    <span>5</span>No-Show
                                </div>

                                <div class="statInnDiv">
                                    <div class="upcmngBtn">
                                        <img src="assets/images/upcoming.png" alt="img">
                                    </div>
                                    <span>210</span>Recorded
                                </div>

                                <?php

                            } catch (PDOException $e) {

                                echo "Connection failed: " . $e->getMessage();
                            }

                            ?>

                        </div>

                        <!-- Win Ratio -->
                        <div class="win">
                            <div class="upcmngBtn">
                                <img src="assets/images/upcoming.png" alt="img">
                            </div>
                            <div class="winParaDiv">

                                <p>

                                    <span>Win Ratio</span>

                                    <b>62%</b>

                                    <span class="up">▲</span>

                                    <small>(Based on recorded games)</small>

                                </p>

                            </div>
                            <p><b>Top Partner:</b> Alex</p>
                            <p><b>Rating:</b> ⭐ 4.5</p>

                        </div>

                        <!-- Tournaments -->
                        <div class="block">
                            <h4>Tournaments <img src="assets/images/upcoming.png" alt="img"></h4>
                            <!-- <p>Casa Open • Summer Smash</p> -->

                            <div class="tournamentBtm">
                                <div class="tourSlider tourSlick">
                                    <div class="tourSlide">
                                        <div class="tourCard">
                                            <span class="tourSpan">Casa Open • Summer Smash</span>
                                        </div>
                                    </div>
                                    <div class="tourSlide">
                                        <div class="tourCard">
                                            <span class="tourSpan">Casa Open • Summer Smash</span>
                                        </div>
                                    </div>
                                    <div class="tourSlide">
                                        <div class="tourCard">
                                            <span class="tourSpan">Casa Open • Summer Smash</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Badges -->

                        <div class="block">

                            <h4>Skill Badges <img src="assets/images/upcoming.png" alt="img"></h4>

                            <div class="badges">
                                <div class="badgeSlider badgeSlick">
                                    <div class="badgeSlide">
                                        <div class="badgeCard">
                                            <span class="badge smash">Smash Master</span>
                                        </div>
                                    </div>
                                    <div class="badgeSlide">
                                        <div class="badgeCard">
                                            <span class="badge net">Net Ninja</span>
                                        </div>
                                    </div>
                                    <div class="badgeSlide">
                                        <div class="badgeCard">
                                            <span class="badge def">Iron Defense</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Refer -->

                        <button class="refer" type="button" data-bs-toggle="modal" data-bs-target="#ReferModal">Refer
                            Good Player</button>
                    </div>
                </div>

                <!-- -------------------------------------------------------------- THE CENTER PANNEL CLUBS AND TOURNAMENT ---------------------------------------------------------------------------------------------------------->
                <div class="panel center-panel">
                    <h3 style="text-align: center;">All Clubs</h3>
                    <div class="scrollbox">
<!-- ======================== DYNAMIC CLUB CARDS (from ca_clubs DB) ======================== -->
                        <?php if (!empty($clubs)): ?>
                            <?php foreach ($clubs as $club_data):
                                $club_name_safe = htmlspecialchars($club_data['club_name']);
                                $club_info_val = trim($club_data['club_info'] ?? '');
                                $club_info_safe = htmlspecialchars(!empty($club_info_val) ? $club_info_val : 'There is no data available.');
                                
                                $club_cost_val = trim($club_data['cost_info'] ?? '');
                                $club_cost_safe = htmlspecialchars(!empty($club_cost_val) ? $club_cost_val : 'There is no data available.');
                                
                                $club_schedule_val = trim($club_data['schedule'] ?? '');
                                $club_schedule_safe = htmlspecialchars(!empty($club_schedule_val) ? $club_schedule_val : 'There is no data available.');
                                
                                $is_new = (strtotime($club_data['created_at']) > strtotime('-30 days'));
                            ?>
                            <div class="card">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div style="width: 40px; height: 40px; border-radius: 6px; overflow: hidden; border: 1px solid #334155; flex-shrink: 0; background: #e2e8f0;">
                                        <?php $club_logo_src = get_club_logo_url($club_data['logo'] ?? ''); ?>
                                        <img src="<?= htmlspecialchars($club_logo_src) ?>" alt="Logo" onerror="this.onerror=null; this.src='assets/images/logo/Final-Logo.png';" style="width:100%; height:100%; object-fit:cover;" />
                                    </div>
                                    <div>
                                        <h4 style="margin: 0;"><?= $club_name_safe ?></h4>
                                        <div style="font-size: 0.75rem; color: #64748b;">By <?= htmlspecialchars($club_data['host_name'] ?? 'Unknown Host') ?></div>
                                    </div>
                                </div>
                                <div class="content">
                                    <div class="action-bar-unified">
                                        <button class="btn-stacked-synced club-info-btn" data-bs-toggle="modal" data-bs-target="#Infomodal"
                                                data-club-name="<?= $club_name_safe ?>"
                                                data-club-info="<?= $club_info_safe ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                            </svg>
                                            <span>Info</span>
                                        </button>

                                        <button class="btn-stacked-synced club-cost-btn" data-bs-toggle="modal" data-bs-target="#Costingmodal"
                                                data-club-name="<?= $club_name_safe ?>"
                                                data-club-cost="<?= $club_cost_safe ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm7.76 4.085c-1.202-.29-1.547-.582-1.547-1.012 0-.396.314-.721.986-.826v1.838zm1.043 2.505c1.238.31 1.62.66 1.62 1.144 0 .524-.424.89-1.202.99V9.59h-.418zM7.5 4.3c-1.63.266-2.5 1.3-2.5 2.622 0 1.253.86 2.072 2.3 2.441v3.31c-.964-.175-1.5-.722-1.603-1.465H4.155c.133 1.54 1.362 2.5 3.345 2.68V15h1.043v-1.12c1.868-.18 3-.98 3-2.43 0-1.427-.922-2.144-2.585-2.54V5.514c.85.116 1.3.565 1.403 1.242h1.53c-.15-1.403-1.196-2.333-2.933-2.5V1h-1.043v1.168c-1.348.165-2.288.75-2.457 1.832H6.1c.148-.734.6-1.166 1.4-1.31v2.61z"/>
                                            </svg>
                                            <span>Costing</span>
                                        </button>

                                        <button class="btn-stacked-synced club-schedule-btn" data-bs-toggle="modal" data-bs-target="#GameSchedulemodal"
                                                data-club-name="<?= $club_name_safe ?>"
                                                data-club-schedule="<?= $club_schedule_safe ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM1 14V4h14v10a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm7-6.507c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132z"/>
                                            </svg>
                                            <span>Schedule</span>
                                        </button>

                                        <?php if ($club_data['membership_status'] === 'accepted'): ?>
                                        <div class="btn-stacked-synced status-member">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                            </svg>
                                            <span>Member</span>
                                        </div>
                                        <a href="player-dashboard.php?host_id=<?= $club_data['host_id'] ?>" class="btn-stacked-synced action-view">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
                                            </svg>
                                            <span>Games</span>
                                        </a>
                                        <?php elseif ($club_data['membership_status'] === 'pending'): ?>
                                        <div class="btn-stacked-synced status-pending-state" style="border-color:#ffc107; color:#ffc107; background-color:#ffc10714;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="color:#ffc107; stroke:#ffc107;">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                <path d="M7.5 4.5a.5.5 0 0 1 1 0V8H11a.5.5 0 0 1 0 1H8a.5.5 0 0 1-.5-.5v-4z"/>
                                            </svg>
                                            <span>Pending</span>
                                        </div>
                                        <a href="#" class="btn-stacked-synced action-view disabled" style="opacity: 0.5; pointer-events: none;" title="Awaiting host approval">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
                                            </svg>
                                            <span>Games</span>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn-stacked-synced join-club-btn" data-club-id="<?= $club_data['id'] ?>" style="cursor: pointer; border: 1px solid #ced4da; background: #f8f9fa;">
                                            <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                            </svg>
                                            <span>Join</span>
                                        </button>
                                        <a href="#" class="btn-stacked-synced action-view disabled" style="opacity: 0.5; pointer-events: none;" title="Must join club first">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
                                            </svg>
                                            <span>Games</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="label" style="text-align:center; color: #94a3b8;">No clubs found matching your preferred game.</p>
                        <?php endif; ?>
                    </div>

                    <h3 style="text-align: center;">Tournaments</h3>

                    <div class="scrollbox">

                        <?php

                        if (!empty($upcomingTournaments)) {

                            foreach ($upcomingTournaments as $row) {

                                $date = date("d M Y", strtotime($row['EVENT_DATE']));

                                $time = date("h:i A", strtotime($row['EVENT_TIME']));

                                $imgPath = !empty($row['IMGAE'])

                                    ? "admin/assets/images/tournaments_banner/" . $row['IMGAE']

                                    : "assets/images/default.jpg";

                                $statusClass = ($row['EVENT_CATEGORY'] == 'Open') ? 'tag open' : 'tag close';

                                $words = explode(' ', strip_tags($row['EVENT_DESCRIPTION']));

                                $quote = implode(' ', array_slice($words, 0, 8)) . (count($words) > 8 ? '...' : '');

                                // Registration Open/Closed Logic based on CANCEL_DATE & CANCEL_TIME
                                $isRegistrationOpen = true;
                                if (!empty($row['CANCEL_DATE'])) {
                                    try {
                                        $nowEst = new DateTime('now', new DateTimeZone('America/New_York'));
                                        $cTime = !empty($row['CANCEL_TIME']) ? $row['CANCEL_TIME'] : '10:00:00';
                                        $cancelEst = new DateTime($row['CANCEL_DATE'] . ' ' . $cTime, new DateTimeZone('America/New_York'));

                                        if ($nowEst >= $cancelEst) {
                                            $isRegistrationOpen = false;
                                        }
                                    } catch (Exception $e) {
                                        $isRegistrationOpen = true;
                                    }
                                }

                                // Draw-live logic based on DRAW_ANNOUNCEMENT (10:00 AM EST)
                                $isDrawLive = true;
                                $drawDisplayDate = '';
                                if (!empty($row['DRAW_ANNOUNCEMENT'])) {
                                    try {
                                        $nowEst = new DateTime('now', new DateTimeZone('America/New_York'));
                                        $drawEst = new DateTime($row['DRAW_ANNOUNCEMENT'] . ' 10:00:00', new DateTimeZone('America/New_York'));
                                        $drawDisplayDate = $drawEst->format('d M Y');
                                        if ($nowEst < $drawEst) {
                                            $isDrawLive = false;
                                        }
                                    } catch (Exception $e) {
                                        $isDrawLive = true;
                                    }
                                }

                                ?>

                                <div class="tournamentCard">
                                    <div class="banner">
                                        <img src="<?php echo $imgPath; ?>" alt="Tournament Banner" onerror="this.onerror=null; this.src='assets/images/default.jpg';">
                                    </div>

                                    <div class="card-body">
                                        <div class="card-header">
                                            <div class="headwrap">
                                                <div class="title">
                                                    <?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>
                                                </div>
                                            </div>
                                            <div class="subtitle">
                                                <p><?php echo htmlspecialchars($row['EVENT_CATEGORY']); ?> -
                                                    <?php echo $row['GENDER_CATEGORY']; ?> - <?php echo $row['EVENT_TYPE']; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="quoteDiv deskView">
                                            <div class="quote">
                                                "<?php echo htmlspecialchars($quote); ?>"
                                            </div>
                                            <?php if ($isRegistrationOpen): ?>
                                                <div class="regBtn">
                                                    <p>Registration Open</p>
                                                </div>
                                            <?php else: ?>
                                                <!-- Grayed out Registration Closed button -->
                                                <div class="regBtn"
                                                    style="background: #475569 !important; border-color: #334155 !important;">
                                                    <p style="color: #cbd5e1 !important;">Registration Closed</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="content">
                                            <div class="info-grid">
                                                <div class="info"><i class="fa fa-calendar-alt"></i><span>Date:</span>
                                                    <?php echo $date; ?></div>
                                                <div class="info"><i class="fa fa-clock"></i><span>Time:</span>
                                                    <?php echo $time; ?></div>
                                                <div class="info"><i class="fa fa-map-marker-alt"></i><span>Venue:</span>
                                                    <?php echo htmlspecialchars($row['EVENT_VENUE']); ?></div>
                                                <div class="info"><i class="fa-solid fa-comment-dollar"></i><span>40 <strong>per
                                                            player</strong></span></div>
                                                <div class="info"><i class="fa-solid fa-feather-pointed"></i></i><span>Birdie:
                                                        <strong> Feather</strong></span></div>
                                                <div class="info">
                                                    <i class="fa fa-check-circle"></i>
                                                    <span><?php echo $row['joined_count']; ?> <?php echo $row['MAX_TEAMS'] ?? ''; ?>
                                                        <strong>teams joined</strong>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="actions deskView">

                                                <?php if ($row['is_joined'] > 0): ?>
                                                    <?php if ($row['pay_status'] == 'N' || empty($row['pay_status'])): ?>

                                                        <!-- Scenario 1: Joined but not paid (STATUS = N) -->

                                                        <button type="button" class="joinbtn pay-now-trigger"
                                                            style="background: #fb172e; border: none;" data-bs-toggle="modal"
                                                            data-bs-target="#PaymentModal" data-id="<?php echo $row['ID']; ?>"
                                                            data-name="<?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>"
                                                            data-amount="<?php echo htmlspecialchars($row['AMOUNT']); ?>">

                                                            Mark Paid

                                                        </button>

                                                    <?php elseif ($row['pay_status'] == 'Y' && empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 2: Paid but admin hasn't approved yet (STATUS = Y and APPROVED_BY is NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn"
                                                            style="background: #ffc107; color: #000; cursor: default;">Pending</a>

                                                    <?php elseif ($row['pay_status'] == 'Y' && !empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 3: Payment approved by admin (STATUS = Y and APPROVED_BY is NOT NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn"
                                                            style="background: #28a745; cursor: default;">Payment Success</a>

                                                    <?php endif; ?>

                                                <?php else: ?>

                                                    <!-- Show 'Join Now' only if they haven't joined yet -->
                                                    <?php if ($isRegistrationOpen): ?>
                                                        <a href="tournament-details.php?id=<?php echo $row['ID']; ?>"
                                                            class="joinbtn">Join Now</a>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0);" onclick="return false;" class="joinbtn"
                                                            style="background: #475569 !important; color: #94a3b8 !important; border-color: #334155 !important; cursor: not-allowed; opacity: 0.8;">Join
                                                            Now</a>
                                                    <?php endif; ?>

                                                <?php endif; ?>


                                                <?php if ($isDrawLive): ?>
                                                    <a href="tournament/?id=<?php echo (int)$row['ID']; ?>" class="joinbtn">View Live</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);"
                                                        onclick="alert('The Draw is almost here! Live match schedules and brackets will go live on <?php echo $drawDisplayDate; ?> at 10:00 am EST. Stay tuned!');"
                                                        class="joinbtn">View Live</a>
                                                <?php endif; ?>
                                            </div>

                                        </div>

                                        <div class="resBtnDiv">
                                            <div class="quoteDiv">
                                                <?php if ($isRegistrationOpen): ?>
                                                    <div class="regBtn">
                                                        <p>Registration Open</p>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Grayed out Registration Closed button -->
                                                    <div class="regBtn"
                                                        style="background: #475569 !important; border-color: #334155 !important;">
                                                        <p style="color: #cbd5e1 !important;">Registration Closed</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="actions">

                                                <?php if ($row['is_joined'] > 0): ?>
                                                    <?php if ($row['pay_status'] == 'N' || empty($row['pay_status'])): ?>

                                                        <!-- Scenario 1: Joined but not paid (STATUS = N) -->

                                                        <button type="button" class="joinbtn pay-now-trigger"
                                                            style="background: #fb172e; border: none;" data-bs-toggle="modal"
                                                            data-bs-target="#PaymentModal" data-id="<?php echo $row['ID']; ?>"
                                                            data-name="<?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>"
                                                            data-amount="<?php echo htmlspecialchars($row['AMOUNT']); ?>">

                                                            Mark Paid

                                                        </button>

                                                    <?php elseif ($row['pay_status'] == 'Y' && empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 2: Paid but admin hasn't approved yet (STATUS = Y and APPROVED_BY is NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn"
                                                            style="background: #ffc107; color: #000; cursor: default;">Pending</a>

                                                    <?php elseif ($row['pay_status'] == 'Y' && !empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 3: Payment approved by admin (STATUS = Y and APPROVED_BY is NOT NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn"
                                                            style="background: #28a745; cursor: default;">Payment Success</a>

                                                    <?php endif; ?>

                                                <?php else: ?>

                                                    <!-- Show 'Join Now' only if they haven't joined yet -->
                                                    <?php if ($isRegistrationOpen): ?>
                                                        <a href="tournament-details.php?id=<?php echo $row['ID']; ?>"
                                                            class="joinbtn">Join Now</a>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0);" onclick="return false;" class="joinbtn"
                                                            style="background: #475569 !important; color: #94a3b8 !important; border-color: #334155 !important; cursor: not-allowed; opacity: 0.8;">Join
                                                            Now</a>
                                                    <?php endif; ?>

                                                <?php endif; ?>


                                                <?php if ($isDrawLive): ?>
                                                    <a href="tournament/?id=<?php echo (int)$row['ID']; ?>" class="joinbtn">View Live</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);"
                                                        onclick="alert('The Draw is almost here! Live match schedules and brackets will go live on <?php echo $drawDisplayDate; ?> at 10:00 am EST. Stay tuned!');"
                                                        class="joinbtn">View Live</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <?php

                            }
                        } else {

                            echo "<p class='label'>No upcoming tournaments found.</p>";
                        }

                        ?>

                    </div>

                </div>

                <!-- RIGHT -->

                <div class="panel right-panel">

                    <h3 style="text-align: center;">The Casa Store</h3>

                    <div class="scrollbox">
                        <div class="casaStore">

                            <?php
                            try {

                                $userEmail = $_SESSION['email'] ?? null;

                                // 1️⃣ Get all products
                                $productDtls = "SELECT * FROM ca_products ORDER BY ID DESC";
                                $stmt = $pdo->prepare($productDtls);
                                $stmt->execute();
                                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // 2️⃣ Get all ordered items for this user (ONE QUERY ONLY)
                                $orderMap = [];

                                if ($userEmail) {
                                    $orderStmt = $pdo->prepare("
            SELECT 
                oi.PRODUCT_ID,
                oi.QUANTITY,
                oi.PRICE,
                o.ORDER_DATE,
                oi.STATUS
            FROM ca_orders o
            INNER JOIN ca_orders_item oi 
                ON o.ORDER_ID = oi.ORDER_ID
            WHERE o.EMAIL = ?
        ");
                                    $orderStmt->execute([$userEmail]);
                                    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($orders as $order) {
                                        $orderMap[$order['PRODUCT_ID']] = $order;
                                    }
                                }

                                if (!empty($products)) {

                                    foreach ($products as $product) {

                                        $productID = $product['ID'];
                                        $productName = !empty($product['PRODUCT_NAME'])
                                            ? htmlspecialchars($product['PRODUCT_NAME'])
                                            : 'N/A';

                                        $price = !empty($product['PRICE'])
                                            ? htmlspecialchars($product['PRICE'])
                                            : '0';

                                        $image = $product['IMAGE'] ?? '';

                                        $imagePath = !empty($image)
                                            ? 'admin/' . htmlspecialchars($image)
                                            : 'assets/images/default.jpg';

                                        $orderData = $orderMap[$productID] ?? null;
                                        ?>

                                        <div class="store-item">
                                            <img src="<?= $imagePath ?>" alt="Product Image" onerror="this.onerror=null; this.src='assets/images/t-shirt.jpg';">
                                            <div class="item-info">

                                                <div class="storeItemTop">
                                                    <h4><?= $productName ?></h4>
                                                    <p class="price">CAD <?= $price ?></p>
                                                </div>

                                                <?php if ($orderData): ?>

                                                    <p class="order">
                                                        Last order:
                                                        <b>
                                                            <?= htmlspecialchars($orderData['QUANTITY']) ?> pcs –
                                                            CAD <?= htmlspecialchars($orderData['PRICE']) ?>
                                                        </b>
                                                    </p>

                                                    <div class="order-capsule">
                                                        <span>Last Order </span>
                                                        <b><?= htmlspecialchars($orderData['QUANTITY']) ?> Pcs</b>
                                                        <span class="divider">|</span>
                                                        <b>CAD <?= htmlspecialchars($orderData['PRICE']) ?></b>
                                                        <span class="divider">|</span>
                                                        <span><?= date("M d, Y", strtotime($orderData['ORDER_DATE'])) ?></span>
                                                    </div>

                                                    <?php
                                                    $status = trim($orderData['STATUS']);

                                                    if (strtolower($status) === 'completed') {
                                                        echo '<span class="status delivered">Delivered</span>';
                                                    } else {
                                                        $safeStatus = htmlspecialchars($status);
                                                        $statusClass = strtolower(str_replace(' ', '-', $safeStatus));
                                                        echo '<span class="status ' . $statusClass . '">' . $safeStatus . '</span>';
                                                    }
                                                    ?>

                                                <?php endif; ?>

                                            </div>
                                        </div>

                                        <?php
                                    }
                                } else {
                                    echo "<p>No products found.</p>";
                                }
                            } catch (PDOException $e) {
                                echo "<p>Something went wrong. Please try again later.</p>";
                            }
                            ?>

                        </div>
                    </div>

                    <a href="https://casainfotech.com/staging/product-listing.php" class="casastore_btn mt-4">
                        The Casa Store
                    </a>

                </div>

            </div>

            <div class="review">
                <h2>✨ WE VALUE YOUR VOICE ✨</h2>
                <div class="reviewSlider reviewSlick">
                    <div class="reviewSlide">
                        <div class="reviewCard">
                            <div class="reviewTextDiv">
                                <p>I am really satisfied with casa tournaments. Lorem ipsum dolor sit amet, consectetur
                                    adipisicing elit. Minus laboriosam hic voluptates necessitatibus totam natus quae
                                    blanditiis, illum id veniam atque accusamus harum animi cupiditate rem possimus
                                    quidem esse nemo nam ratione! Minus optio amet soluta veniam inventore, porro illum
                                    ab quidem ut tenetur veritatis tempore ex, quo similique corrupti?</p>
                            </div>
                        </div>
                    </div>

                    <div class="reviewSlide">
                        <div class="reviewCard">
                            <div class="reviewTextDiv">
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto recusandae
                                    tempora ad beatae dolorum quod.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#reviewmodal">Please Write a Review</button>
            </div>

        </div>

    </div>

</section>

<!-- Info modal (Dynamic - populated via JS) -->
<div class="modal fade" id="Infomodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="InfomodalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="InfomodalLabel">Club Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="InfomodalBody">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Costingmodal -->
<div class="modal fade" id="ReferModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="ReferModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="ReferModalLabel">Refer Good Player</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <!-- new design -->
            <div class="modal-body">

                <ul>
                    <li>Take a moment to fill in your details using “Register Here”</li>

                    <li>Once registered, DM the club admin to receive your login credentials</li>

                    <li>Change your password after logging in for the first time (for security reasons)</li>

                    <li>Check the Game Card for available games by category & level</li>

                    <li>Click Join based on your availability</li>

                    <li>More details and game schedule are available on the portal</li>
                </ul>
                <p><i class="fa-solid fa-envelope"></i><span>info.casagames@gmail.com</span></p>
                <p><i class="fa-solid fa-globe"></i><span>https://casa-games.com</span></p>
                <p><i class="fa-solid fa-dumbbell"></i><span>Stay fit and active!</span></p>
            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

            </div>

        </div>

    </div>

</div>

<!------ Costingmodal (Dynamic - populated via JS) ------>
<div class="modal fade" id="Costingmodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="CostingmodalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="CostingmodalLabel">Game Costing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="CostingmodalBody">
                <p>Loading...</p>
            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

            </div>

        </div>

    </div>

</div>

<!------ GameSchedulemodal (Dynamic - populated via JS) ------>
<div class="modal fade" id="GameSchedulemodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="GameSchedulemodalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="GameSchedulemodalLabel">Game Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="GameSchedulemodalBody">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->

<div class="modal fade" id="PaymentModal" tabindex="-1" aria-labelledby="PaymentModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content" style="background: #1f2937; color: white;">

            <div class="modal-header" style="border-bottom: 1px solid #334155;">

                <h5 class="modal-title" id="PaymentModalLabel">Notify Payment</h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>

            </div>

            <form id="paymentForm">

                <!-- Security Token -->

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <input type="hidden" name="tournament_id" id="modalTournamentId">

                <div class="modal-body">

                    <div class="mb-3">

                        <p class="mb-1">Tournament: <strong id="modalTournamentName"></strong></p>

                        <p>Total Amount: <strong id="modalTournamentAmountDisplay" style="color: #22c55e;"></strong></p>

                    </div>

                    <div id="paymentError" class="alert alert-danger d-none" style="font-size: 0.8rem;"></div>

                    <div class="mb-3">

                        <label>Enter Payment Amount</label>

                        <input type="number" step="0.01" name="payment_amount" class="form-control" value="40.00"
                            required readonly>

                    </div>

                    <div class="row">

                        <div class="col-6 mb-3">

                            <label>Enter Payment Date</label>

                            <input type="date" id="payment_date" name="payment_date" class="form-control" required
                                readonly>

                        </div>

                        <div class="col-6 mb-3">

                            <label>Enter Payment Time</label>

                            <input type="time" id="payment_time" name="payment_time" class="form-control" required
                                readonly>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label>Payment Type</label>

                        <select name="payment_type" class="form-control" required>

                            <option value="" disabled selected>Select Type</option>

                            <option value="Internet" selected>Interac</option>

                            <option value="Cash">Cash</option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label>Any Payment Details (Optional)</label>

                        <input type="text" name="payment_details" class="form-control"
                            placeholder="e.g. Reference number">

                    </div>

                    <div class="mb-3">

                        <label>Message (Optional)</label>

                        <textarea name="payment_message" class="form-control" rows="2"
                            placeholder="Your message here..."></textarea>

                    </div>

                </div>

                <div class="modal-footer" style="border-top: 1px solid #334155;">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" id="submitBtn" class="btn btn-success">Submit Payment</button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- Big Congratulations Overlay -->

<div id="successOverlay">

    <div class="success-card">

        <div style="font-size: 70px; color: #22c55e;">✓</div>

        <h1 style="color:white; margin-top:20px; font-weight: 800;">Transaction Pending Verification!</h1>

        <p style="color:#94a3b8; font-size: 1.2rem;">We have logged your <br><strong style="color:white;">CAD <span
                    id="dispAmt"></span>(Interac/Cash) payment</strong></p>

        <p style="color:#94a3b8;">Admin verifies the transfer and confirms your entry.Your button will turn green and
            display "Payment Success".See you on the court soon!
        </p>

        <button onclick="location.reload()" class="btn btn-danger mt-4"
            style="background:#fb172e; border:none; padding:12px 40px; font-weight:bold;">Close</button>

    </div>

</div>

<!-- review modal -->
<?php
$reviewUserName = !empty($player_prefs['NAME']) ? $player_prefs['NAME'] : ($_SESSION['name'] ?? 'Mr. Lorem Ipsum');
$reviewWords = preg_split('/\s+/', trim($reviewUserName));
$reviewInitials = '';
if (count($reviewWords) >= 2) {
    $reviewInitials = strtoupper(substr($reviewWords[0], 0, 1) . substr($reviewWords[count($reviewWords)-1], 0, 1));
} elseif (!empty($reviewWords[0])) {
    $reviewInitials = strtoupper(substr($reviewWords[0], 0, 2));
} else {
    $reviewInitials = 'ML';
}
$reviewCurrentDate = date('d.m.y');
?>
<div class="modal fade" id="reviewmodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="reviewmodalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered review-custom-dialog">
        <div class="modal-content review-custom-content">
            <!-- Top Header Row -->
            <div class="review-top-header">
                <div class="review-user-profile">
                    <div class="review-avatar-circle">
                        <?= htmlspecialchars($reviewInitials) ?>
                    </div>
                    <div class="review-user-details">
                        <h4 class="review-user-fullname"><?= htmlspecialchars($reviewUserName) ?></h4>
                        <span class="review-post-date"><i class="fa-regular fa-calendar"></i> <?= $reviewCurrentDate ?></span>
                    </div>
                </div>
                <button type="button" class="review-close-x" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Rating Row -->
            <div class="review-rating-section">
                <span class="review-rating-heading">Your Rating</span>
                <div class="review-star-group" id="reviewStars" role="radiogroup" aria-label="Star rating">
                    <button type="button" class="review-star-btn is-active" data-value="1" aria-label="1 star">★</button>
                    <button type="button" class="review-star-btn is-active" data-value="2" aria-label="2 stars">★</button>
                    <button type="button" class="review-star-btn is-active" data-value="3" aria-label="3 stars">★</button>
                    <button type="button" class="review-star-btn is-active" data-value="4" aria-label="4 stars">★</button>
                    <button type="button" class="review-star-btn is-active" data-value="5" aria-label="5 stars">★</button>
                </div>
                <span class="review-rating-status" id="reviewRatingText">Excellent</span>
                <input type="hidden" name="rating" id="reviewRatingInput" value="5">
            </div>

            <!-- Toolbar Grid -->
            <div class="review-toolbar-grid">
                <!-- Row 1: B, I, U, S, Align Left, Align Center, Align Right, Justify, HR, Ordered List, H1 -->
                <button type="button" class="review-tb-btn btn-bold" data-command="bold" title="Bold">B</button>
                <button type="button" class="review-tb-btn btn-italic" data-command="italic" title="Italic">I</button>
                <button type="button" class="review-tb-btn btn-underline" data-command="underline" title="Underline"><u>U</u></button>
                <button type="button" class="review-tb-btn btn-strike" data-command="strikeThrough" title="Strikethrough"><s>S</s></button>
                <button type="button" class="review-tb-btn" data-command="justifyLeft" title="Align left">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/></svg>
                </button>
                <button type="button" class="review-tb-btn" data-command="justifyCenter" title="Align center">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M4 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/></svg>
                </button>
                <button type="button" class="review-tb-btn" data-command="justifyRight" title="Align right">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm4-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/></svg>
                </button>
                <button type="button" class="review-tb-btn" data-command="justifyFull" title="Justify">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/></svg>
                </button>
                <button type="button" class="review-tb-btn" data-command="insertHorizontalRule" title="Horizontal line">=</button>
                <button type="button" class="review-tb-btn btn-num" data-command="insertOrderedList" title="Numbered list">1.</button>
                <button type="button" class="review-tb-btn btn-h" data-command="formatBlock" data-value="h1" title="Heading 1">H1</button>

                <!-- Row 2: H2, P, Link, Clear Format -->
                <div class="review-tb-break"></div>
                <button type="button" class="review-tb-btn btn-h" data-command="formatBlock" data-value="h2" title="Heading 2">H2</button>
                <button type="button" class="review-tb-btn btn-h" data-command="formatBlock" data-value="p" title="Paragraph">P</button>
                <button type="button" class="review-tb-btn" data-command="createLink" title="Insert link">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1.002 1.002 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4.018 4.018 0 0 1-.128-1.287z"/><path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 0 0-4.243-4.243L6.586 4.672z"/></svg>
                </button>
                <button type="button" class="review-tb-btn btn-eraser" data-command="removeFormat" title="Clear formatting">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8.086 2.207a2 2 0 0 1 2.828 0l3.879 3.879a2 2 0 0 1 0 2.828l-5.5 5.5A2 2 0 0 1 7.879 15H5.12a2 2 0 0 1-1.414-.586l-2.5-2.5a2 2 0 0 1 0-2.828l6.879-6.879zm.66 10.793H14a.5.5 0 0 0 0-1H8.746l-.66 1z"/>
                        <line x1="2" y1="14" x2="14" y2="2" stroke="#ec4899" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- Textarea Box -->
            <div class="review-editor-container">
                <div class="review-editor-area" id="reviewMessageEditor" contenteditable="true" data-placeholder="Write your review here..."></div>
            </div>

            <!-- Footer Buttons -->
            <div class="review-action-row">
                <button type="button" class="review-action-btn btn-review-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="review-action-btn btn-review-submit" id="reviewSubmitBtn">Submit Review</button>
            </div>
        </div>
    </div>
</div>

<style>
.review-custom-dialog {
    max-width: 490px;
    margin: 1.75rem auto;
}
.review-custom-content {
    background-color: #ebedf0 !important;
    border: none !important;
    border-radius: 8px !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.22) !important;
    padding: 24px 28px 24px 28px !important;
    color: #111827;
}

/* Top Header */
.review-top-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
}
.review-user-profile {
    display: flex;
    align-items: center;
    gap: 14px;
}
.review-avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: #ddf2fb;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.15rem;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    flex-shrink: 0;
}
.review-user-details {
    display: flex;
    flex-direction: column;
}
.review-user-fullname {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
}
.review-post-date {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 3px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.review-close-x {
    background: transparent;
    border: none;
    color: #0284c7;
    cursor: pointer;
    padding: 2px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease, color 0.15s ease;
}
.review-close-x:hover {
    color: #0369a1;
    opacity: 0.8;
}

/* Rating */
.review-rating-section {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}
.review-rating-heading {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.review-star-group {
    display: inline-flex;
    gap: 6px;
}
.review-star-btn {
    background: none;
    border: none;
    padding: 0;
    font-size: 1.45rem;
    color: #cbd5e1;
    cursor: pointer;
    line-height: 1;
    transition: color 0.15s ease, transform 0.1s ease;
}
.review-star-btn:hover {
    transform: scale(1.1);
}
.review-star-btn.is-active,
.review-star-btn.is-hover {
    color: #f59e0b;
}
.review-rating-status {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 0.98rem;
    color: #64748b;
    margin-left: auto;
}

/* Toolbar Grid */
.review-toolbar-grid {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 6px;
    margin-bottom: 12px;
}
.review-tb-break {
    flex-basis: 100%;
    height: 0;
    margin: 0;
}
.review-tb-btn {
    width: 29px;
    height: 29px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #1e293b;
    font-size: 0.82rem;
    cursor: pointer;
    padding: 0;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}
.review-tb-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}
.review-tb-btn.btn-bold {
    font-weight: 900;
    font-family: sans-serif;
}
.review-tb-btn.btn-italic {
    font-style: italic;
    font-family: "Times New Roman", Georgia, serif;
    font-weight: bold;
}
.review-tb-btn.btn-underline {
    text-decoration: underline;
    font-weight: bold;
}
.review-tb-btn.btn-strike {
    text-decoration: line-through;
    font-weight: bold;
}
.review-tb-btn.btn-num,
.review-tb-btn.btn-h {
    font-weight: 700;
    font-size: 0.76rem;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Editor */
.review-editor-container {
    width: 100%;
    margin-bottom: 22px;
}
.review-editor-area {
    width: 100%;
    min-height: 140px;
    max-height: 240px;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 12px 14px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 0.95rem;
    color: #1e293b;
    line-height: 1.5;
    outline: none;
    box-shadow: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.review-editor-area:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
}
.review-editor-area:empty:before {
    content: attr(data-placeholder);
    color: #9ca3af;
    font-family: Georgia, "Times New Roman", serif;
}

/* Footer Buttons */
.review-action-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}
.review-action-btn {
    border-radius: 4px;
    padding: 8px 24px;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 700;
    font-size: 0.98rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-review-cancel {
    background: #f1f3f5;
    border: 1px solid #cbd5e1;
    color: #111827;
}
.btn-review-cancel:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
}
.btn-review-submit {
    background: #0284c7;
    border: none;
    color: #ffffff;
    padding: 8px 26px;
}
.btn-review-submit:hover {
    background: #0369a1;
}
</style>

<script>
(function () {
    // Star Rating logic
    var stars = document.querySelectorAll('#reviewStars .review-star-btn');
    var ratingInput = document.getElementById('reviewRatingInput');
    var ratingText = document.getElementById('reviewRatingText');
    var labels = ['', 'Terrible', 'Poor', 'Good', 'Very good', 'Excellent'];
    var currentRating = 5;

    function paintStars(val) {
        stars.forEach(function (star) {
            var starVal = parseInt(star.getAttribute('data-value'), 10);
            star.classList.toggle('is-hover', starVal <= val);
        });
    }

    stars.forEach(function (star) {
        var val = parseInt(star.getAttribute('data-value'), 10);

        star.addEventListener('mouseenter', function () { paintStars(val); });
        star.addEventListener('click', function () {
            currentRating = val;
            if (ratingInput) ratingInput.value = currentRating;
            if (ratingText) ratingText.textContent = labels[currentRating] || 'Excellent';
            stars.forEach(function (s) {
                var sVal = parseInt(s.getAttribute('data-value'), 10);
                s.classList.toggle('is-active', sVal <= currentRating);
            });
        });
    });

    var starsWrap = document.getElementById('reviewStars');
    if (starsWrap) {
        starsWrap.addEventListener('mouseleave', function () { paintStars(currentRating); });
    }

    // Rich Text Toolbar logic
    var editor = document.getElementById('reviewMessageEditor');
    var toolbarButtons = document.querySelectorAll('.review-tb-btn');

    toolbarButtons.forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) {
            e.preventDefault(); // Prevent losing focus in editor
            var command = btn.getAttribute('data-command');
            var value = btn.getAttribute('data-value') || null;

            if (command === 'createLink') {
                var url = prompt('Enter the link URL:', 'https://');
                if (url) {
                    document.execCommand(command, false, url);
                }
            } else if (command === 'formatBlock') {
                document.execCommand(command, false, '<' + value + '>');
            } else if (command) {
                document.execCommand(command, false, value);
            }
            if (editor) editor.focus();
        });
    });

    // Submit Review AJAX
    var submitBtn = document.getElementById('reviewSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var rating = ratingInput ? ratingInput.value : 5;
            var message = editor ? editor.innerHTML.trim() : '';

            // Check if empty
            var textContent = editor ? editor.innerText.trim() : '';
            if (!textContent && (!message || message === '<br>' || message === '<div><br></div>')) {
                alert('Please write your review before submitting.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            var formData = new FormData();
            formData.append('rating', rating);
            formData.append('message', message);

            fetch('api/save_review.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
                if (data.status === 'success') {
                    alert(data.message || 'Thank you! Your review has been submitted.');
                    if (editor) editor.innerHTML = '';
                    var reviewModalEl = document.getElementById('reviewmodal');
                    var modal = bootstrap.Modal.getInstance(reviewModalEl);
                    if (modal) { modal.hide(); }
                } else {
                    alert(data.message || 'Failed to submit review.');
                }
            })
            .catch(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
                alert('Something went wrong. Please try again.');
            });
        });
    }
})();
</script>

<?php include "includes/footer.php"; ?>

    <script>
        $(document).ready(function () {

            var paymentModalEl = document.getElementById('PaymentModal');

            var paymentModal = new bootstrap.Modal(paymentModalEl);

            // Populate data when modal opens

            paymentModalEl.addEventListener('show.bs.modal', function (event) {

                var button = event.relatedTarget;

                $('#modalTournamentId').val(button.getAttribute('data-id'));

                $('#modalTournamentName').text(button.getAttribute('data-name'));

                $('#modalTournamentAmountDisplay').text("CAD " + button.getAttribute('data-amount'));

                $('#paymentError').addClass('d-none');

            });

            // AJAX Form Submission

            $('#paymentForm').on('submit', function (e) {

                e.preventDefault();

                const btn = $('#submitBtn');

                btn.prop('disabled', true).text('Processing...');

                $.ajax({

                    url: 'api/player_payment.php',

                    type: 'POST',

                    data: $(this).serialize(),

                    dataType: 'json',

                    success: function (response) {

                        if (response.success) {

                            paymentModal.hide();

                            $('#dispAmt').text(response.paid_amount);

                            $('#successOverlay').css('display', 'flex').hide().fadeIn();

                        } else {

                            $('#paymentError').text(response.message).removeClass('d-none');

                            btn.prop('disabled', false).text('Submit Payment');

                        }

                    },

                    error: function () {

                        $('#paymentError').text('Server error. Please try again.').removeClass('d-none');

                        btn.prop('disabled', false).text('Submit Payment');

                    }

                });

            });

        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dateInput = document.getElementById('payment_date');
            const timeInput = document.getElementById('payment_time');

            const now = new Date();

            // Format Date as YYYY-MM-DD
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            dateInput.value = `${year}-${month}-${day}`;

            // Format Time as HH:MM (24-hour format)
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            timeInput.value = `${hours}:${minutes}`;
        });
    </script>

    <script>
        var wys = select('.edit-text');
        var wyg = select('.text');
        console.log(wyg);

        wys.addEventListener('keyup', function (e) {
            wyg.innerHTML = this.innerHTML;
        });

        var buttons = select('.formatter button');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                formatText(this.getAttribute('data-command'));
            })
        });

        function formatText(command) {
            if (command === 'h1' || command === 'h2' || command === 'p') {
                document.execCommand('formatBlock', false, command);
                return;
            } else if (command === 'createlink') {
                var url = prompt('Enter the link here: ', 'http:\/\/');
                document.execCommand(command, false, url);
            } else {
                document.execCommand(command, false, null);
            }
            wys.dispatchEvent(new Event('keyup'));
        }

        function select(query) {
            var elements = document.querySelectorAll(query);
            return (elements.length > 1) ? elements : elements[0];
        }
    </script>

    <!-- Dynamic Modal Population for Club Cards (Info / Costing / Schedule) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function setSafeModalContent(container, html) {
            if (!container) return;
            if (!html || html.trim() === '') {
                container.innerHTML = '<p class="text-muted text-center my-3">There is no data available.</p>';
                return;
            }

            // Strip DOCTYPE, html, head, title, meta, body tags if present (with strict \b so <header> is preserved)
            var cleaned = html
                .replace(/<!DOCTYPE[^>]*>/gi, '')
                .replace(/<\/?(?:html|head|meta|title|body)\b[^>]*>/gi, '');

            // Strip global body / html / universal (*) css rules from any inline <style> tags
            cleaned = cleaned.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, function(match, css) {
                var safeCss = css
                    .replace(/\b(body|html)\s*\{[^}]*\}/gi, '')
                    .replace(/^\s*\*\s*\{[^}]*\}/gmi, '');
                return '<style>' + safeCss + '</style>';
            });

            container.innerHTML = cleaned;
        }

        // Info modal: populate title + body from data attributes
        document.querySelectorAll('.club-info-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var name = this.dataset.clubName || 'Club';
                document.getElementById('InfomodalLabel').textContent = name;
                setSafeModalContent(document.getElementById('InfomodalBody'), this.dataset.clubInfo);
            });
        });

        // Costing modal: populate title + body from data attributes
        document.querySelectorAll('.club-cost-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var name = this.dataset.clubName || 'Club';
                document.getElementById('CostingmodalLabel').textContent = name;
                setSafeModalContent(document.getElementById('CostingmodalBody'), this.dataset.clubCost);
            });
        });

        // Schedule modal: populate title + body from data attributes
        document.querySelectorAll('.club-schedule-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var name = this.dataset.clubName || 'Club';
                document.getElementById('GameSchedulemodalLabel').textContent = name;
                setSafeModalContent(document.getElementById('GameSchedulemodalBody'), this.dataset.clubSchedule);
            });
        });
    });
    </script>

    <!-- AJAX handler for Join Club -->
    <script>
    $(document).on('click', '.join-club-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var clubId = btn.data('club-id');
        
        if (btn.prop('disabled')) return;
        btn.prop('disabled', true).text('Joining...');
        
        $.ajax({
            url: 'api/join_club.php',
            type: 'POST',
            data: { club_id: clubId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Error occurred.');
                    btn.prop('disabled', false).html('<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/></svg><span>Join</span>');
                }
            },
            error: function() {
                alert('Server error while joining. Please try again.');
                btn.prop('disabled', false).html('<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/></svg><span>Join</span>');
            }
        });
    });
    </script>