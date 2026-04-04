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

    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES   => false,

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



    }







    .tournamentCard .banner {



        width: 140px;



        flex-shrink: 0;



    }







    .tournamentCard .banner img {



        width: 100%;



        height: 100%;



        object-fit: cover;



    }







    .tournamentCard .card-body {



        flex: 1;



        padding: 14px;



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
        padding: 12px 12px 8px 12px; /* Tightened bottom padding */
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #eef0f2;
        margin-bottom: 15px;
        font-family: 'Segoe UI', Roboto, sans-serif;
    }

    .card h4 {
        margin: 0 0 10px 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #ffffff; /* Club name is now white */
        letter-spacing: -0.02em;
        text-align: left;
    }
    
    /* 2: The Order Info Capsule */
    .order-capsule {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.08); /* Glass effect */
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
    .stat-completed { background: #166534; color: #4ade80; }
    .stat-pending { background: #854d0e; color: #fbbf24; }
    .stat-default { background: #334155; color: #cbd5e1; }
    
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
        font-weight: 800; /* Extra bold text to match icons */
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
        stroke-width: 0.5px; /* Adds slight weight */
        stroke: #0d6efd;
    }

    .btn-stacked-synced.status-member { 
        border-color: #198754; 
        color: #198754; 
        background-color: #f0fff4; 
    }
    .btn-stacked-synced.status-member svg { color: #198754; stroke: #198754; }
    
    .btn-stacked-synced.action-view { 
        background-color: #0d6efd; 
        color: white; 
        border-color: #0d6efd; 
    }
    .btn-stacked-synced.action-view svg { color: white; stroke: white; }
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
                        <?php if (!empty($_SESSION['country'])): ?>
                            <option value="<?= htmlspecialchars($_SESSION['country']) ?>" selected>
                                <?= htmlspecialchars($_SESSION['country']) ?>
                            </option>
                        <?php endif; ?>
                    </select>

                    <!-- Province -->
                    <select name="province" disabled style="-webkit-appearance: none;">
                        <option value="">--Province--</option>
                        <?php if (!empty($_SESSION['province'])): ?>
                            <option value="<?= htmlspecialchars($_SESSION['province']) ?>" selected>
                                <?= htmlspecialchars($_SESSION['province']) ?>
                            </option>
                        <?php endif; ?>
                    </select>

                    <!-- City -->
                    <select name="area" disabled style="-webkit-appearance: none;">
                        <option value="">--City--</option>
                        <?php if (!empty($_SESSION['city'])): ?>
                            <option value="<?= htmlspecialchars($_SESSION['city']) ?>" selected>
                                <?= htmlspecialchars($_SESSION['city']) ?>
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



                        <!-- Actions -->



                        <!--<div class="actions">-->



                        <!--    <button>Show Review</button>-->



                        <!--    <button class="outline">Request Review</button>-->



                        <!--</div>-->





                        <?php
                        $profileImage = $_SESSION['profileImage'] ?? '';

                        $imagePath = !empty($profileImage)
                            ? 'profile_img/' . htmlspecialchars($profileImage)
                            : 'assets/images/profile.jpg';
                        ?>

                        <div class="profileDetails">



                            <!-- Profile -->



                            <div class="profile">



                                <img src="<?= $imagePath ?>" alt="Profile Image">



                            </div>







                            <!-- Info -->



                            <div class="info">



                                <p><b>Name:</b> <?= htmlspecialchars($_SESSION['name'] ?? 'N/A') ?></p>



                                <p><b>Gender:</b> <?= htmlspecialchars($_SESSION['gender'] ?? 'N/A') ?></p>



                                <p><b>Level:</b> <?= htmlspecialchars($_SESSION['vlevel'] ?? 'N/A') ?></p>

                                <p><b>Area:</b> <?= htmlspecialchars($_SESSION['area'] ?? 'N/A') ?></p>



                            </div>



                        </div>







                        <!-- Stats -->



                        <!--<div class="stats">-->



                        <!--    <div><span>120</span>Total Sessions</div>-->



                        <!--    <div><span>340</span>Total Games</div>-->



                        <!--    <div><span>5</span>No-Show</div>-->



                        <!--    <div><span>210</span>Recorded</div>-->



                        <!--</div>-->



                        <div class="stats">



                            <?php



                            try {



                                $sqlsession = "SELECT COUNT(*) AS total_session FROM ca_gamejoin WHERE USER_ID = :user_id";

                                $stmt = $pdo->prepare($sqlsession);
                                $stmt->execute(['user_id' => $current_user_id]);



                                $resultsession = $stmt->fetch(PDO::FETCH_ASSOC);







                                $totalSessions = (int)$resultsession['total_session'];



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



                        <button class="refer" type="button" data-bs-toggle="modal" data-bs-target="#ReferModal">Refer Good Player</button>
                    </div>
                </div>
                
<!-- -------------------------------------------------------------- THE CENTER PANNEL CLUBS AND TOURNAMENT ---------------------------------------------------------------------------------------------------------->
                <div class="panel center-panel">
                    <h3 style="text-align: center;">All Clubs</h3>
                    <div class="scrollbox">
<!-- -------------------------------------------------------------- CASA BADMINTON CLUB ----------------------------------------------------------------------------------------------------------------------------->
                        <div class="card">
                            <!-- <h4>Casa Club</h4> -->
                            <h4>Casa Badminton Club</h4>
                            <div class="content">
                                <!--<div class="">-->
                                    <!-- <p class="label">Casa Badminton Club</p> -->
                                <!--    <div class="info-grid">-->
                                <!--        <button class="joinbtn" data-bs-toggle="modal" data-bs-target="#Infomodal"><i class="fa-solid fa-circle-info"></i> <span>Info</span></button>-->

                                <!--        <button class="joinbtn" data-bs-toggle="modal" data-bs-target="#Costingmodal"><i class="fa-solid fa-comments-dollar"></i> <span>Costing</span></button>-->

                                <!--        <button class="joinbtn" data-bs-toggle="modal" data-bs-target="#GameSchedulemodal"><i class="fa-solid fa-clipboard-list"></i> <span>Schedule</span></button>-->
                                <!--    </div>-->
                                <!--</div>-->

                                <!--<div class="button_box">-->
                                <!--    <a href="#" class="joinbtn"><i class="fa-solid fa-circle-user"></i> <span>You are member</span></a>-->
                                <!--    <a href="player-dashboard.php" class="joinbtn"><i class="fa-solid fa-gamepad"></i> <span>View Game</span></a>-->
                                <!--</div>-->
                                <div class="action-bar-unified">
                                    <button class="btn-stacked-synced" data-bs-toggle="modal" data-bs-target="#Infomodal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                        </svg>
                                        <span>Info</span>
                                    </button>
                                
                                    <button class="btn-stacked-synced" data-bs-toggle="modal" data-bs-target="#Costingmodal">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm7.76 4.085c-1.202-.29-1.547-.582-1.547-1.012 0-.396.314-.721.986-.826v1.838zm1.043 2.505c1.238.31 1.62.66 1.62 1.144 0 .524-.424.89-1.202.99V9.59h-.418zM7.5 4.3c-1.63.266-2.5 1.3-2.5 2.622 0 1.253.86 2.072 2.3 2.441v3.31c-.964-.175-1.5-.722-1.603-1.465H4.155c.133 1.54 1.362 2.5 3.345 2.68V15h1.043v-1.12c1.868-.18 3-.98 3-2.43 0-1.427-.922-2.144-2.585-2.54V5.514c.85.116 1.3.565 1.403 1.242h1.53c-.15-1.403-1.196-2.333-2.933-2.5V1h-1.043v1.168c-1.348.165-2.288.75-2.457 1.832H6.1c.148-.734.6-1.166 1.4-1.31v2.61z"/>
                                        </svg>
                                        <span>Costing</span>
                                    </button>
                                
                                    <button class="btn-stacked-synced" data-bs-toggle="modal" data-bs-target="#GameSchedulemodal">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM1 14V4h14v10a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm7-6.507c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132z"/>
                                        </svg>
                                        <span>Schedule</span>
                                    </button>
                                
                                    <div class="btn-stacked-synced status-member">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                        </svg>
                                        <span>Member</span>
                                    </div>
                                
                                    <a href="player-dashboard.php" class="btn-stacked-synced action-view">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-10.5a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
                                        </svg>
                                        <span>Games</span>
                                    </a>
                                </div>
                            </div>
                        </div>
<!-- -------------------------------------------------------------- BIRDIE BUSTERS CLUB ----------------------------------------------------------------------------------------------------------------------------->
                        <div class="card">
                            <!-- <h4>The Casamigos <span class="tag">New</span></h4> -->
                            <h4>Birdie Busters Club <span class="tag">New</span></h4>
                            <div class="content">
                                <div class="">
                                    <!-- <p class="label">Club joined tonight</p> -->
                                    <div class="info-grid">
                                        <button class="joinbtn" data-bs-toggle="modal" data-bs-target="#Infomodal"><i class="fa-solid fa-circle-info"></i> <span>Info</span></button>
                                        <button class="joinbtn" data-bs-toggle="modal" data-bs-target="#Costingmodal"><i class="fa-solid fa-comments-dollar"></i> <span>Costing</span></button>
                                        <button class="joinbtn" data-bs-toggle="modal" data-bs-target="#GameSchedulemodal"><i class="fa-solid fa-clipboard-list"></i> <span>Schedule</span></button>
                                    </div>
                                </div>
                                <div class="button_box">
                                    <a href="#" class="joinbtn"><i class="fa-solid fa-user-plus"></i> <span>Request to Join</span></a>
                                    <a href="player-dashboard.php" class="joinbtn"><i class="fa-solid fa-gamepad"></i> <span>View Game</span></a>
                                </div>
                            </div>
                        </div>
<!-- -------------------------------------------------------------- NET NINJA CLUB ----------------------------------------------------------------------------------------------------------------------------->
                        <div class="card">
                            <h4>Net Ninjs Club <span class="tag">New</span></h4>
                            <div class="content">
                                <button class="btn-stacked-synced" data-bs-toggle="modal" data-bs-target="#Infomodal">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                    </svg>
                                    <span>Info</span>
                                </button>
                            
                                <button class="btn-stacked-synced" data-bs-toggle="modal" data-bs-target="#Costingmodal">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm7.76 4.085c-1.202-.29-1.547-.582-1.547-1.012 0-.396.314-.721.986-.826v1.838zm1.043 2.505c1.238.31 1.62.66 1.62 1.144 0 .524-.424.89-1.202.99V9.59h-.418zM7.5 4.3c-1.63.266-2.5 1.3-2.5 2.622 0 1.253.86 2.072 2.3 2.441v3.31c-.964-.175-1.5-.722-1.603-1.465H4.155c.133 1.54 1.362 2.5 3.345 2.68V15h1.043v-1.12c1.868-.18 3-.98 3-2.43 0-1.427-.922-2.144-2.585-2.54V5.514c.85.116 1.3.565 1.403 1.242h1.53c-.15-1.403-1.196-2.333-2.933-2.5V1h-1.043v1.168c-1.348.165-2.288.75-2.457 1.832H6.1c.148-.734.6-1.166 1.4-1.31v2.61z"/>
                                    </svg>
                                    <span>Costing</span>
                                </button>
                            
                                <button class="btn-stacked-synced" data-bs-toggle="modal" data-bs-target="#GameSchedulemodal">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM1 14V4h14v10a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm7-6.507c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132z"/>
                                    </svg>
                                    <span>Schedule</span>
                                </button>
                                
                                <a href="#" class="btn-stacked-synced join-action">
                                    <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/></svg>
                                    <span>Join</span>
                                </a>
                            
                                <a href="player-dashboard.php" class="btn-stacked-synced action-view">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-10.5a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
                                    </svg>
                                    <span>Games</span>
                                </a>
                            </div>        
                        </div>                        
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

                        ?>







                                <div class="tournamentCard">
                                    <div class="banner">
                                        <img src="<?php echo $imgPath; ?>" alt="Tournament Banner">
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
                                                    <?php echo $row['GENDER_CATEGORY']; ?> - <?php echo $row['EVENT_TYPE']; ?></p>
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
                                                <div class="regBtn" style="background: #475569 !important; border-color: #334155 !important;">
                                                    <p style="color: #cbd5e1 !important;">Registration Closed</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="content">
                                            <div class="info-grid">
                                                <div class="info"><i class="fa fa-calendar-alt"></i><span>Date:</span> <?php echo $date; ?></div>
                                                <div class="info"><i class="fa fa-clock"></i><span>Time:</span> <?php echo $time; ?></div>
                                                <div class="info"><i class="fa fa-map-marker-alt"></i><span>Venue:</span> <?php echo htmlspecialchars($row['EVENT_VENUE']); ?></div>
                                                <div class="info"><i class="fa-solid fa-comment-dollar"></i><span>40 <strong>per player</strong></span></div>
                                                <div class="info"><i class="fa-solid fa-feather-pointed"></i></i><span>Birdie: <strong> Feather</strong></span></div>
                                                <div class="info">
                                                    <i class="fa fa-check-circle"></i>
                                                    <span><?php echo $row['joined_count']; ?> <?php echo $row['MAX_TEAMS']; ?>
                                                        <strong>teams joined</strong>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="actions deskView">

                                                <?php if ($row['is_joined'] > 0): ?>
                                                    <?php if ($row['pay_status'] == 'N' || empty($row['pay_status'])): ?>

                                                        <!-- Scenario 1: Joined but not paid (STATUS = N) -->

                                                        <!-- Updated Pay Now Button -->

                                                        <!-- Change the 'Pay Now' link to this button -->

                                                        <!-- Updated Pay Now Button -->

                                                        <!-- Updated Pay Now Button -->

                                                        <button type="button"

                                                            class="joinbtn pay-now-trigger"

                                                            style="background: #fb172e; border: none;"

                                                            data-bs-toggle="modal"

                                                            data-bs-target="#PaymentModal"

                                                            data-id="<?php echo $row['ID']; ?>"

                                                            data-name="<?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>"

                                                            data-amount="<?php echo htmlspecialchars($row['AMOUNT']); ?>"> <!-- Added this line -->

                                                            Mark Paid

                                                        </button>

                                                    <?php elseif ($row['pay_status'] == 'Y' && empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 2: Paid but admin hasn't approved yet (STATUS = Y and APPROVED_BY is NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn" style="background: #ffc107; color: #000; cursor: default;">Pending</a>



                                                    <?php elseif ($row['pay_status'] == 'Y' && !empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 3: Payment approved by admin (STATUS = Y and APPROVED_BY is NOT NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn" style="background: #28a745; cursor: default;">Payment Success</a>



                                                    <?php endif; ?>



                                                <?php else: ?>

                                                    <!-- Show 'Join Now' only if they haven't joined yet -->
                                                    <?php if ($isRegistrationOpen): ?>
                                                        <a href="tournament-details.php?id=<?php echo $row['ID']; ?>" class="joinbtn">Join Now</a>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0);" onclick="return false;" class="joinbtn" style="background: #475569 !important; color: #94a3b8 !important; border-color: #334155 !important; cursor: not-allowed; opacity: 0.8;">Join Now</a>
                                                    <?php endif; ?>

                                                <?php endif; ?>



                                                <!-- <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a> -->

                                                <?php
                                                $isDrawLive = true;
                                                $drawDisplayDate = '';
                                                if (!empty($row['DRAW_ANNOUNCEMENT'])) {
                                                    try {
                                                        // Set timezone to EST
                                                        $nowEst = new DateTime('now', new DateTimeZone('America/New_York'));
                                                        // Combine DB Date with 10:00 AM EST
                                                        $drawEst = new DateTime($row['DRAW_ANNOUNCEMENT'] . ' 10:00:00', new DateTimeZone('America/New_York'));
                                                        $drawDisplayDate = $drawEst->format('d M Y'); // Format for popup message

                                                        // Check if current time is before the draw time
                                                        if ($nowEst < $drawEst) {
                                                            $isDrawLive = false;
                                                        }
                                                    } catch (Exception $e) {
                                                        $isDrawLive = true;
                                                    }
                                                }
                                                ?>

                                                <?php if ($isDrawLive): ?>
                                                    <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);" onclick="alert('The Draw is almost here! Live match schedules and brackets will go live on <?php echo $drawDisplayDate; ?> at 10:00 am EST. Stay tuned!');" class="joinbtn">View Live</a>
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
                                                    <div class="regBtn" style="background: #475569 !important; border-color: #334155 !important;">
                                                        <p style="color: #cbd5e1 !important;">Registration Closed</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="actions">

                                                <?php if ($row['is_joined'] > 0): ?>
                                                    <?php if ($row['pay_status'] == 'N' || empty($row['pay_status'])): ?>

                                                        <!-- Scenario 1: Joined but not paid (STATUS = N) -->

                                                        <!-- Updated Pay Now Button -->

                                                        <!-- Change the 'Pay Now' link to this button -->

                                                        <!-- Updated Pay Now Button -->

                                                        <!-- Updated Pay Now Button -->

                                                        <button type="button"

                                                            class="joinbtn pay-now-trigger"

                                                            style="background: #fb172e; border: none;"

                                                            data-bs-toggle="modal"

                                                            data-bs-target="#PaymentModal"

                                                            data-id="<?php echo $row['ID']; ?>"

                                                            data-name="<?php echo htmlspecialchars($row['CUP_NAME'] ?: $row['HOST_NAME']); ?>"

                                                            data-amount="<?php echo htmlspecialchars($row['AMOUNT']); ?>"> <!-- Added this line -->

                                                            Mark Paid

                                                        </button>

                                                    <?php elseif ($row['pay_status'] == 'Y' && empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 2: Paid but admin hasn't approved yet (STATUS = Y and APPROVED_BY is NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn" style="background: #ffc107; color: #000; cursor: default;">Pending</a>



                                                    <?php elseif ($row['pay_status'] == 'Y' && !empty($row['pay_approved'])): ?>

                                                        <!-- Scenario 3: Payment approved by admin (STATUS = Y and APPROVED_BY is NOT NULL) -->

                                                        <a href="javascript:void(0);" class="joinbtn" style="background: #28a745; cursor: default;">Payment Success</a>



                                                    <?php endif; ?>



                                                <?php else: ?>

                                                    <!-- Show 'Join Now' only if they haven't joined yet -->
                                                    <?php if ($isRegistrationOpen): ?>
                                                        <a href="tournament-details.php?id=<?php echo $row['ID']; ?>" class="joinbtn">Join Now</a>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0);" onclick="return false;" class="joinbtn" style="background: #475569 !important; color: #94a3b8 !important; border-color: #334155 !important; cursor: not-allowed; opacity: 0.8;">Join Now</a>
                                                    <?php endif; ?>

                                                <?php endif; ?>



                                                <!-- <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a> -->

                                                <?php
                                                $isDrawLive = true;
                                                $drawDisplayDate = '';
                                                if (!empty($row['DRAW_ANNOUNCEMENT'])) {
                                                    try {
                                                        // Set timezone to EST
                                                        $nowEst = new DateTime('now', new DateTimeZone('America/New_York'));
                                                        // Combine DB Date with 10:00 AM EST
                                                        $drawEst = new DateTime($row['DRAW_ANNOUNCEMENT'] . ' 10:00:00', new DateTimeZone('America/New_York'));
                                                        $drawDisplayDate = $drawEst->format('d M Y'); // Format for popup message

                                                        // Check if current time is before the draw time
                                                        if ($nowEst < $drawEst) {
                                                            $isDrawLive = false;
                                                        }
                                                    } catch (Exception $e) {
                                                        $isDrawLive = true;
                                                    }
                                                }
                                                ?>

                                                <?php if ($isDrawLive): ?>
                                                    <a href="https://casainfotech.com/staging/tournament/" class="joinbtn">View Live</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);" onclick="alert('The Draw is almost here! Live match schedules and brackets will go live on <?php echo $drawDisplayDate; ?> at 10:00 am EST. Stay tuned!');" class="joinbtn">View Live</a>
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

                                session_start();
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

                                        $productID   = $product['ID'];
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
                                            <img src="<?= $imagePath ?>" alt="Product Image">
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
                                <p>I am really satisfied with casa tournaments. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minus laboriosam hic voluptates necessitatibus totam natus quae blanditiis, illum id veniam atque accusamus harum animi cupiditate rem possimus quidem esse nemo nam ratione! Minus optio amet soluta veniam inventore, porro illum ab quidem ut tenetur veritatis tempore ex, quo similique corrupti?</p>
                            </div>
                        </div>
                    </div>

                    <div class="reviewSlide">
                        <div class="reviewCard">
                            <div class="reviewTextDiv">
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto recusandae tempora ad beatae dolorum quod.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#reviewmodal">Please Write a Review</button>
            </div>



        </div>



    </div>



</section>


<!-- Info modal -->
<div class="modal fade" id="Infomodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="InfomodalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="InfomodalLabel">Casa badminton club</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>


            <div class="modal-body">
                <p>What We Offer:</p>
                <ul>
                    <li><strong>Family & Couples Games –</strong> We encourage husbands and wives, parents and kids, and even entire families to enjoy the sport together. Our family sessions create the perfect opportunity to bond, have fun, and stay active.</li>
                    <li><strong>Skill-Level Based Matches –</strong> Players are grouped into beginner, intermediate, and advanced levels so everyone enjoys fair, balanced, and competitive games.</li>
                    <li><strong>Mixed-Gender & Mixed-Level Games –</strong> We regularly host fun matches where men, women, and players of different levels can team up, learn from each other, and enjoy the social side of badminton.</li>
                    <li><strong>Friendly Matches & Social Play –</strong> Connect with fellow badminton enthusiasts in a fun, inclusive setting.</li>
                    <li><strong>Tournaments & Events –</strong> Regular competitions to challenge your skills and celebrate progress.</li>
                    <li><strong>Community Spirit –</strong> A place where sportsmanship, teamwork, and enjoyment come first.</li>
                </ul>
                <p>At Casa Badminton Club, our mission is simple: to grow the love of badminton and create a community where everyone feels at home—whether you play for fitness, family bonding, training, or competition.</p>
                <p>Come join us, pick up your racket, and be part of the Casa family!</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Costingmodal -->
<div class="modal fade" id="ReferModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="ReferModalLabel" aria-hidden="true">



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

<!------ Costingmodal ------>
<div class="modal fade" id="Costingmodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="CostingmodalLabel" aria-hidden="true">



    <div class="modal-dialog modal-dialog-scrollable">



        <div class="modal-content">



            <div class="modal-header">



                <h5 class="modal-title" id="CostingmodalLabel">Game Costing</h5>



                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>



            </div>


            <!-- old design -->
            <div class="modal-body d-none">
                <p>Here's the breakdown:</p>
                <p>Men Double</p>
                <ul>



                    <li>(Court Cost + Birdie cost)/ no of players</li>



                    <li>We consider two birdies per player</li>



                    <li>4 players: $25 each</li>



                    <li>5 players: $22 each</li>



                    <li>6 players: $20 each</li>



                </ul>
                <p>Women Double</p>
                <ul>



                    <li>(Court Cost + Birdie cost)/ no of players</li>



                    <li>We consider one birdies per player</li>



                    <li>4 players: $18 each</li>



                    <li>5 players: $15 each</li>



                    <li>6 players: $14 each</li>



                </ul>
            </div>

            <!-- new design -->
            <div class="modal-body">
                <p>Men Double</p>
                <ul>
                    <li>Price = (Court Cost + Birdie cost)/ no of players</li>
                    <li>We consider two birdies per player</li>
                    <li>4 players: $25 each</li>
                    <li>5 players: $22 each</li>
                    <li>6 players: $20 each</li>
                </ul>
                <p>Women Double</p>
                <ul>
                    <li>Price = (Court Cost + Birdie cost)/ no of players</li>
                    <li>We consider one birdies per player</li>
                    <li>4 players: $18 each</li>
                    <li>5 players: $15 each</li>
                    <li>6 players: $14 each</li>
                </ul>
                <p>Note: The portal dynamically adjusts the price based on players confirmed</p>
            </div>



            <div class="modal-footer">



                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>



            </div>



        </div>



    </div>



</div>



<!------ GameSchedulemodal ------>
<div class="modal fade" id="GameSchedulemodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="GameSchedulemodalLabel" aria-hidden="true">



    <div class="modal-dialog modal-lg modal-dialog-scrollable">



        <div class="modal-content">



            <div class="modal-header">



                <h5 class="modal-title" id="GameSchedulemodalLabel">Game Schedule</h5>



                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>



            </div>



            <div class="modal-body">
                <!-- old design -->
                <div class="row d-none">
                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Monday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate / Intermediate+</li>



                            <li>📍 Venue: Epic</li>



                            <li>⏰ Time: 8:30 pm- 10:30 pm</li>



                        </ul>



                    </div>

                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Tuesday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate / Intermediate+</li>



                            <li>📍 Venue: Epic</li>



                            <li>⏰ Time: 8:30 pm- 10:30 pm</li>



                        </ul>



                    </div>

                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Wednesday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate / Intermediate+</li>



                            <li>📍 Venue: Epic</li>



                            <li>⏰ Time: 6:00 pm- 8:00 pm</li>



                            <li><b>Note:</b> More ladies join in (seperate court)</li>



                        </ul>



                    </div>

                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Thursday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate+</li>



                            <li>📍 Venue: Epic</li>



                            <li>⏰ Time: 9:30 pm- 11:30 pm</li>



                        </ul>



                    </div>

                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Friday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate / Intermediate+</li>



                            <li>📍 Venue: Hymus</li>



                            <li>⏰ Time: 6:00 pm- 8:00 pm</li>



                            <li><b>Note:</b> More ladies join in (seperate court)</li>



                        </ul>



                    </div>
                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Saturday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate+</li>



                            <li>📍 Venue: Epic</li>



                            <li>⏰ Time: 7:00 am- 9:00 am</li>



                        </ul>



                    </div>

                    <div class="col-md-6 col-12 mb-2">



                        <p>📅 Sunday</p>



                        <ul>



                            <li>👨‍👩‍👧 Category: Male & Female</li>



                            <li>⭐ Level: Intermediate / Intermediate+</li>



                            <li>📍 Venue: Hymus</li>



                            <li>⏰ Time: 4:00 pm- 6:00 pm</li>



                            <li><b>Note:</b> More ladies join in (seperate court)</li>



                        </ul>



                    </div>
                </div>

                <!-- new design -->
                <div class="scheduleTable">
                    <table>
                        <thead>
                            <th>Day</th>
                            <th>Gender</th>
                            <th>Level</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Time</th>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Monday</td>
                                <td>Male & Female</td>
                                <td>Intermediate / Intermediate+</td>
                                <td>Doubles</td>
                                <td>Epic</td>
                                <td>9:00 pm to 11:00 pm</td>
                            </tr>
                            <tr>
                                <td>Tuesday</td>
                                <td>Male & Female</td>
                                <td>Intermediate / Intermediate+</td>
                                <td>Doubles</td>
                                <td>Epic</td>
                                <td>9:00 pm to 11:00 pm</td>
                            </tr>
                            <tr>
                                <td>Wednesday</td>
                                <td>Male & Female</td>
                                <td>Intermediate / Intermediate+</td>
                                <td>Doubles</td>
                                <td>Hymus</td>
                                <td>6:30 pm to 8:30 pm</td>
                            </tr>
                            <tr>
                                <td>Thursday</td>
                                <td>Male & Female</td>
                                <td>Intermediate+</td>
                                <td>Doubles</td>
                                <td>Epic</td>
                                <td>9:30 pm- 11:30 pm</td>
                            </tr>
                            <tr>
                                <td>Friday</td>
                                <td>Male & Female</td>
                                <td>Intermediate+</td>
                                <td>Doubles</td>
                                <td>Hymus</td>
                                <td>6:30 pm to 8:30 pm</td>
                            </tr>
                            <tr>
                                <td>Saturday</td>
                                <td>Male & Female</td>
                                <td>Intermediate+</td>
                                <td>Doubles</td>
                                <td>Epic</td>
                                <td>7:00 am to 9:00 am</td>
                            </tr>
                            <tr>
                                <td>Sunday</td>
                                <td>Male & Female</td>
                                <td>Intermediate / Intermediate+</td>
                                <td>Doubles</td>
                                <td>Hymus</td>
                                <td>4:00 pm- 6:00 pm</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>

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

                        <!-- <input type="number" step="0.01" name="payment_amount" class="form-control" required placeholder="0.00"> -->
                        <input type="number" step="0.01" name="payment_amount" class="form-control" value="40.00" required readonly>

                    </div>



                    <div class="row">

                        <div class="col-6 mb-3">

                            <label>Enter Payment Date</label>

                            <!-- <input type="date" name="payment_date" class="form-control" required> -->
                            <input type="date" id="payment_date" name="payment_date" class="form-control" required readonly>


                        </div>

                        <div class="col-6 mb-3">

                            <label>Enter Payment Time</label>

                            <!-- <input type="time" name="payment_time" class="form-control" required> -->
                            <input type="time" id="payment_time" name="payment_time" class="form-control" required readonly>


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

                        <input type="text" name="payment_details" class="form-control" placeholder="e.g. Reference number">

                    </div>



                    <div class="mb-3">

                        <label>Message (Optional)</label>

                        <textarea name="payment_message" class="form-control" rows="2" placeholder="Your message here..."></textarea>

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

        <p style="color:#94a3b8; font-size: 1.2rem;">We have logged your <br><strong style="color:white;">CAD <span id="dispAmt"></span>(Interac/Cash) payment</strong></p>

        <p style="color:#94a3b8;">Admin verifies the transfer and confirms your entry.Your button will turn green and display "Payment Success".See you on the court soon!
        </p>

        <button onclick="location.reload()" class="btn btn-danger mt-4" style="background:#fb172e; border:none; padding:12px 40px; font-weight:bold;">Close</button>

    </div>

</div>

<!-- review modal -->
<div class="modal fade" id="reviewmodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="reviewmodalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel"><strong>Please share a review</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="name"><strong>Mr. Lorem Ipsum</strong></h5>
                    <p><strong>Date:</strong> 05.03.26</p>
                </div>
                <div class="formatter">
                    <button class="btn btn-default" data-command="bold">
                        <i class="fa fa-bold"></i>
                    </button>
                    <button class="btn btn-default" data-command="italic">
                        <i class="fa fa-italic"></i>
                    </button>
                    <button class="btn btn-default" data-command="underline">
                        <i class="fa fa-underline"></i>
                    </button>
                    <button class="btn btn-default" data-command="strikeThrough">
                        <i class="fa fa-strikethrough"></i>
                    </button>
                    <button class="btn btn-default" data-command="justifyLeft">
                        <i class="fa fa-align-left"></i>
                    </button>
                    <button class="btn btn-default" data-command="justifyCenter">
                        <i class="fa fa-align-center"></i>
                    </button>
                    <button class="btn btn-default" data-command="justifyRight">
                        <i class="fa fa-align-right"></i>
                    </button>
                    <button class="btn btn-default" data-command="justifyFull">
                        <i class="fa fa-align-justify"></i>
                    </button>
                    <button class="btn btn-default" data-command="indent">
                        <i class="fa fa-indent"></i>
                    </button>
                    <button class="btn btn-default" data-command="outdent">
                        <i class="fa fa-outdent"></i>
                    </button>
                    <button class="btn btn-default" data-command="insertUnorderedList">
                        <i class="fa fa-list-ul"></i>
                    </button>
                    <button class="btn btn-default" data-command="insertOrderedList">
                        <i class="fa fa-list-ol"></i>
                    </button>
                    <button class="btn btn-default" data-command="h1">H1</button>
                    <button class="btn btn-default" data-command="h2">H2</button>
                    <button class="btn btn-default" data-command="p">P</button>
                    <button class="btn btn-default" data-command="createlink">
                        <i class="fa fa-link"></i>
                    </button>
                    <button class="btn btn-default" data-command="unlink">
                        <i class="fa fa-unlink"></i>
                    </button>
                </div>

                <p class="edit-text" contenteditable="true" rows="15"></p>

                <div class="d-flex justify-content-center" style="gap: 12px;">
                    
                    <form action="">
                        <input class="reviewBtn" type="submit" value="Submit">
                    </form>
                    <button type="button" class="btn reviewCancelBtn" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<

    <?php include "includes/footer.php"; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js">
    </script>

    <script>
        $(document).ready(function() {

            var paymentModalEl = document.getElementById('PaymentModal');

            var paymentModal = new bootstrap.Modal(paymentModalEl);



            // Populate data when modal opens

            paymentModalEl.addEventListener('show.bs.modal', function(event) {

                var button = event.relatedTarget;

                $('#modalTournamentId').val(button.getAttribute('data-id'));

                $('#modalTournamentName').text(button.getAttribute('data-name'));

                $('#modalTournamentAmountDisplay').text("CAD " + button.getAttribute('data-amount'));

                $('#paymentError').addClass('d-none');

            });



            // AJAX Form Submission

            $('#paymentForm').on('submit', function(e) {

                e.preventDefault();



                const btn = $('#submitBtn');

                btn.prop('disabled', true).text('Processing...');



                $.ajax({

                    url: 'api/player_payment.php',

                    type: 'POST',

                    data: $(this).serialize(),

                    dataType: 'json',

                    success: function(response) {

                        if (response.success) {

                            paymentModal.hide();

                            $('#dispAmt').text(response.paid_amount);

                            $('#successOverlay').css('display', 'flex').hide().fadeIn();

                        } else {

                            $('#paymentError').text(response.message).removeClass('d-none');

                            btn.prop('disabled', false).text('Submit Payment');

                        }

                    },

                    error: function() {

                        $('#paymentError').text('Server error. Please try again.').removeClass('d-none');

                        btn.prop('disabled', false).text('Submit Payment');

                    }

                });

            });

        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
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

        wys.addEventListener('keyup', function(e) {
            wyg.innerHTML = this.innerHTML;
        });

        var buttons = select('.formatter button');

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
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