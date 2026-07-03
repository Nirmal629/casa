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
            <div class="tab-scroll-container mb-3">
                <ul class="nav nav-pills nav-stacked-pills flex-nowrap" id="myTab" role="tablist">
                    
                    <li class="nav-item">
                        <button class="nav-link active" id="Scheduled-tab" data-bs-toggle="tab" data-bs-target="#Upcoming" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                            </svg>
                            <span>Schedule</span>
                        </button>
                    </li>
            
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="Play-Payment-tab" data-bs-toggle="tab" data-bs-target="#Play-Payment" type="button" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                                <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                            </svg>
                            <span>Payment</span>
                        </button>
                    </li>
            
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="Play-Monthly-Subscription" data-bs-toggle="tab" data-bs-target="#Monthly-Subscription" type="button" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h4v-3zM5 4h5V3H5v1zm5 4H5v1h5V8zm-5 4h5v1H5v-1zM4 3H3v1h1V3zm0 5H3v1h1V8zm0 4H3v1h1v-1z"/>
                            </svg>
                            <span>Subscribe</span>
                        </button>
                    </li>
                    
                </ul>
            </div>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="Upcoming" role="tabpanel" aria-labelledby="Upcoming-tab">
                    <?php include "player-Upcoming-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Completed" role="tabpanel" aria-labelledby="Play-Completed-tab">
                    <?php include "player-Complete-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Payment" role="tabpanel" aria-labelledby="Play-Payment-tab">
                    <?php include "player-payment-list.php"; ?>
                </div>
                <div class="tab-pane fade" id="Monthly-Subscription" role="tabpanel" aria-labelledby="Play-Monthly-Subscription">
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




<!------footer------>
<?php include "includes/footer.php"; ?>