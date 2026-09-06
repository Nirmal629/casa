<?php 
session_start();
error_reporting(E_ALL & ~E_NOTICE); 
include "dbConnection_PDO.php"; 

$host_id = isset($_GET['host_id']) ? intval($_GET['host_id']) : ($_SESSION['mapped_host_id'] ?? 0);
if ($host_id === 0) {
    header("Location: player-hub.php");
    exit();
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
// --- START QUERY LOGIC ---
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // We fetch everything here so the sub-pages can just use the variable
    $sql = "SELECT e.*, b.IMGAE, 
            (SELECT COUNT(*) FROM to_tournaments WHERE EVENTS_ID = e.ID) as joined_count 
            FROM to_tournaments e 
            LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 
            WHERE e.STATUS = 'Active' AND e.EVENT_DATE >= CURDATE() 
          ORDER BY e.ID DESC";
    
    $stmt = $pdo->query($sql);
    $upcomingTournaments = $stmt->fetchAll(); // All data stored in this variable
} catch (Exception $e) {
    $upcomingTournaments = [];
}
// --- END QUERY LOGIC ---

include "includes/inner-header.php";

if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'Player') {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>

<style>
    /* Compact stacked pills - Synced with Host styles */
    .nav-stacked-pills .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6px 4px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        min-width: 75px; /* Slightly wider for longer words like "Upcoming" */
        color: #6c757d;
        border: none;
        background: none;
        transition: all 0.2s ease;
    }

    /* SVG sizing to match previous icons */
    .nav-stacked-pills .nav-link svg {
        margin-bottom: 3px;
    }

    /* Active state - Blue theme */
    .nav-stacked-pills .nav-link.active {
        background-color: #0d6efd !important;
        color: white !important;
        border-radius: 8px;
    }

    /* Horizontal scroll for mobile */
    .tab-scroll-container {
        display: flex;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 5px 0;
        border-bottom: 1px solid #dee2e6;
    }
    .tab-scroll-container::-webkit-scrollbar { display: none; }

    /* Compact creative empty state & card container */
    .custom_card {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
        padding: 20px;
        margin-bottom: 24px;
    }
    .discoverGames_wraper.plyerGame_wrapper {
        min-height: auto;
    }
    .empty-schedule-card {
        max-width: 440px;
        margin: 18px auto !important;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border: 1.5px dashed #cbd5e1;
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        padding: 24px 18px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .empty-schedule-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
    }
    .empty-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
        color: #0284c7;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
    }
</style>


<!----User-game----->
<section class="bothSide_gap">
    <div class="cust_container">
        <!-- <h6 class="sub_heading">Lorem ipsum</h6> -->
        <h2 class="heading">The Player Dashboard</h2>

        <div class="custom_card">
            <!----calender- Date-Picker--->
            <!--<div class="mb-4">-->
            <!--    <form>-->
            <!--        <div class="calendar-box">-->
            <!-- <h2 class="calendar-title">Select a Date</h2> -->
            <!--            <input type="text" id="dateInput" placeholder="Select a date ">-->
            <!--            <div class="calendar" id="calendar">-->
            <!--                <div class="header">-->
            <!--                    <button id="prevBtn">&lt;</button>-->
            <!--                    <h2 id="monthYear">Month Year</h2>-->
            <!--                    <button id="nextBtn">&gt;</button>-->
            <!--                </div>-->
            <!--                <div class="days" id="daysContainer"></div>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </form>-->
            <!--</div>-->


            <!----All-Event-tab-start------->
            <?php
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'Upcoming';
            ?>
            <div class="tab-scroll-container mb-3">
                <ul class="nav nav-pills nav-stacked-pills flex-nowrap" id="myTab" role="tablist">
                    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'Upcoming' ? 'active' : '' ?>" id="Scheduled-tab" data-bs-toggle="tab" data-bs-target="#Upcoming" type="button" role="tab" aria-controls="Upcoming" aria-selected="<?= $active_tab === 'Upcoming' ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                            </svg>
                            <span>Schedule</span>
                        </button>
                    </li>
            
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'Play-Payment' ? 'active' : '' ?>" id="Play-Payment-tab" data-bs-toggle="tab" data-bs-target="#Play-Payment" type="button" role="tab" aria-controls="Play-Payment" aria-selected="<?= $active_tab === 'Play-Payment' ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                                <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                            </svg>
                            <span>Payment</span>
                        </button>
                    </li>
            
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'Monthly-Subscription' ? 'active' : '' ?>" id="Play-Monthly-Subscription" data-bs-toggle="tab" data-bs-target="#Monthly-Subscription" type="button" role="tab" aria-controls="Monthly-Subscription" aria-selected="<?= $active_tab === 'Monthly-Subscription' ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h4v-3zM5 4h5V3H5v1zm5 4H5v1h5V8zm-5 4h5v1H5v-1zM4 3H3v1h1V3zm0 5H3v1h1V8zm0 4H3v1h1v-1z"/>
                            </svg>
                            <span>Subscribe</span>
                        </button>
                    </li>
                    
                </ul>
            </div>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade <?= $active_tab === 'Upcoming' ? 'show active' : '' ?>" id="Upcoming" role="tabpanel" aria-labelledby="Scheduled-tab">
                    <?php include "player-Upcoming-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Completed" role="tabpanel" aria-labelledby="Play-Completed-tab">
                    <?php include "player-Complete-game.php"; ?>
                </div>
                <div class="tab-pane fade <?= $active_tab === 'Play-Payment' ? 'show active' : '' ?>" id="Play-Payment" role="tabpanel" aria-labelledby="Play-Payment-tab">
                    <?php include "player-payment-list.php"; ?>
                </div>
                <div class="tab-pane fade <?= $active_tab === 'Monthly-Subscription' ? 'show active' : '' ?>" id="Monthly-Subscription" role="tabpanel" aria-labelledby="Play-Monthly-Subscription">
                    <?php include "player-monthly-subscription.php"; ?>
                </div>
                <!-- <div class="tab-pane fade" id="player-hub" role="tabpanel" aria-labelledby="play-player-hub">
                  <? //php include "player-hub.php"; ?>
                </div> -->
            </div>
            <!----All-Event-tab-End------->

        </div>
    </div>
</section>




<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', (e) => {
            const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
            const url = new URL(window.location.href);
            url.searchParams.set('tab', targetId);
            window.history.replaceState({}, '', url.toString());
        });
    });
});
</script>

<!------footer------>
<?php include "includes/footer.php"; ?>