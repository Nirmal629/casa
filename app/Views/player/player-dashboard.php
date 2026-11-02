<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL & ~E_NOTICE);

$host_id = $host_id ?? (isset($_GET['host_id']) ? intval($_GET['host_id']) : ($_SESSION['mapped_host_id'] ?? 0));
if ($host_id === 0 && !empty($_SESSION['user_id'])) {
    if (file_exists(__DIR__ . '/../../Helpers/audit.php')) {
        require_once __DIR__ . '/../../Helpers/audit.php';
    }
    $conn = \App\Config\Database::getMysqli();
    $host_id = function_exists('auditResolveHostId') ? auditResolveHostId($conn, (int) $_SESSION['user_id']) : 0;
}

include __DIR__ . "/../layouts/header/inner-header.php";

if (empty($_SESSION['usertype']) || strcasecmp(trim((string) $_SESSION['usertype']), 'Player') !== 0) {
    echo "<script>window.location.href='/';</script>";
    exit();
}
?>
<link rel="stylesheet" href="/assets/css/player-dashboard.css?v=<?= @filemtime(__DIR__ . '/../../../assets/css/player-dashboard.css') ?: time() ?>">


<!----User-game----->
<section class="bothSide_gap player-dash-section">
    <div class="cust_container">
        <!-- <h6 class="sub_heading">Lorem ipsum</h6> -->
        <!-- <h2 class="heading">The Player Dashboard</h2> -->

        <div class="custom_card">

            <?php
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'Upcoming';
            ?>
            <div class="tab-scroll-container mb-3">
                <ul class="nav nav-pills nav-stacked-pills flex-nowrap" id="myTab" role="tablist">

                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'Upcoming' ? 'active' : '' ?>" id="Scheduled-tab"
                            data-bs-toggle="tab" data-bs-target="#Upcoming" type="button" role="tab"
                            aria-controls="Upcoming"
                            aria-selected="<?= $active_tab === 'Upcoming' ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z" />
                                <path
                                    d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" />
                            </svg>
                            <span>Schedule</span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'Play-Payment' ? 'active' : '' ?>"
                            id="Play-Payment-tab" data-bs-toggle="tab" data-bs-target="#Play-Payment" type="button"
                            role="tab" aria-controls="Play-Payment"
                            aria-selected="<?= $active_tab === 'Play-Payment' ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z" />
                                <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
                            </svg>
                            <span>Payment</span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'Monthly-Subscription' ? 'active' : '' ?>"
                            id="Play-Monthly-Subscription" data-bs-toggle="tab" data-bs-target="#Monthly-Subscription"
                            type="button" role="tab" aria-controls="Monthly-Subscription"
                            aria-selected="<?= $active_tab === 'Monthly-Subscription' ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h4v-3zM5 4h5V3H5v1zm5 4H5v1h5V8zm-5 4h5v1H5v-1zM4 3H3v1h1V3zm0 5H3v1h1V8zm0 4H3v1h1v-1z" />
                            </svg>
                            <span>Subscribe</span>
                        </button>
                    </li>

                </ul>
            </div>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade <?= $active_tab === 'Upcoming' ? 'show active' : '' ?>" id="Upcoming"
                    role="tabpanel" aria-labelledby="Scheduled-tab">
                    <?php include "player-Upcoming-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Completed" role="tabpanel" aria-labelledby="Play-Completed-tab">
                    <?php include "player-Complete-game.php"; ?>
                </div>
                <div class="tab-pane fade <?= $active_tab === 'Play-Payment' ? 'show active' : '' ?>" id="Play-Payment"
                    role="tabpanel" aria-labelledby="Play-Payment-tab">
                    <?php include "player-payment-list.php"; ?>
                </div>
                <div class="tab-pane fade <?= $active_tab === 'Monthly-Subscription' ? 'show active' : '' ?>"
                    id="Monthly-Subscription" role="tabpanel" aria-labelledby="Play-Monthly-Subscription">
                    <?php include "player-monthly-subscription.php"; ?>
                </div>
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

                if (targetId === 'Play-Payment') {
                    if (!url.searchParams.has('month')) {
                        if (typeof window.resetPaymentTabToCurrentMonth === 'function') {
                            window.resetPaymentTabToCurrentMonth();
                        }
                    }
                }
                window.history.replaceState({}, '', url.toString());
            });
        });
    });
</script>

<!------footer------>
<?php include __DIR__ . "/../layouts/footer/footer.php"; ?>