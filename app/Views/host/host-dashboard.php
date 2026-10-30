<?php
declare(strict_types=1);

// If accessed directly instead of via HostController::dashboard()
if (!isset($this) || !($this instanceof \App\Core\Controller)) {
    require_once __DIR__ . '/../../../init.php';
    $controller = new \App\Controllers\HostController();
    $controller->dashboard();
    return;
}

include __DIR__ . '/../layouts/header/inner-header.php';

$userRole = strtolower(trim((string) ($userRole ?? ($_SESSION['usertype'] ?? ''))));
$dashboardHeading = match ($userRole) {
    'host' => 'Host Dashboard',
    'trainer' => 'Trainer Dashboard',
    default => 'Dashboard',
};

if (!isset($canViewSpecialTabs) && !empty($_SESSION['user_id'])) {
    $clubModel = new \App\Models\ClubModel();
    $canViewSpecialTabs = ($clubModel->getJoinTypeByHostId((int) $_SESSION['user_id']) === 'H');
} else {
    $canViewSpecialTabs = (bool) ($canViewSpecialTabs ?? false);
}

$dashboardCssPath = __DIR__ . '/../css/host/dashboard.css';
$dashboardCssVer = file_exists($dashboardCssPath) ? filemtime($dashboardCssPath) : '1.0';
$cssPrefix = $assetPrefix ?? (strpos($_SERVER['REQUEST_URI'] ?? '', '/host/') !== false ? '../' : '/');
?>

<link rel="stylesheet" href="<?= e($cssPrefix) ?>app/Views/css/host/dashboard.css?v=<?= $dashboardCssVer ?>">
<link rel="stylesheet" href="<?= e($cssPrefix) ?>assets/css/host/modern-dashboard.css?v=1">

<!----Dashboard------>
<section class="bothSide_gap host-modern-dashboard">
    <div class="cust_container">
        <h2 class="heading"><?= e($dashboardHeading) ?></h2>

        <div class="custom_card">
            <div class="tab-scroll-container mb-3">
                <ul class="nav nav-pills nav-stacked-pills flex-nowrap" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="Scheduled-tab" data-bs-toggle="tab"
                            data-bs-target="#Scheduled" type="button" role="tab" aria-controls="Scheduled"
                            aria-selected="true">
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
                        <button class="nav-link" id="Completed-tab" data-bs-toggle="tab" data-bs-target="#Completed"
                            type="button" role="tab" aria-controls="Completed" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                            </svg>
                            <span>Complete</span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="hostgame-tab" data-bs-toggle="tab" data-bs-target="#hostgame"
                            type="button" role="tab" aria-controls="hostgame" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z" />
                                <path
                                    d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
                            </svg>
                            <span>New</span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="Tournament-tab" data-bs-toggle="tab" data-bs-target="#Tournament"
                            type="button" role="tab" aria-controls="Tournament" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.133-5.89A33.076 33.076 0 0 1 2.5.5zm.099 2.54a2 2 0 0 0 .72 3.935c-.333-1.05-.588-2.346-.72-3.935zm10.083 3.935a2 2 0 0 0 .72-3.935c-.133 1.59-.388 2.885-.72 3.935z" />
                            </svg>
                            <span>Tournament</span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="Payment-tab" data-bs-toggle="tab" data-bs-target="#Payment"
                            type="button" role="tab" aria-controls="Payment" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
                                <path
                                    d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z" />
                            </svg>
                            <span>Payment</span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="MonthlySub-tab" data-bs-toggle="tab" data-bs-target="#MonthlySub"
                            type="button" role="tab" aria-controls="MonthlySub" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.156v4.31c-1.153-.147-1.94-.75-2.12-1.997H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z" />
                            </svg>
                            <span>Service</span>
                        </button>
                    </li>

                    <?php if ($canViewSpecialTabs): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="Subscription-tab" data-bs-toggle="tab"
                                data-bs-target="#Subscription" type="button" role="tab" aria-controls="Subscription"
                                aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                    viewBox="0 0 16 16">
                                    <path
                                        d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z" />
                                    <path
                                        d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 10.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z" />
                                </svg>
                                <span>Subscribe</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="playerstats-tab" data-bs-toggle="tab" data-bs-target="#playerstats"
                                type="button" role="tab" aria-controls="playerstats" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                    viewBox="0 0 16 16">
                                    <path
                                        d="M0 0h1v15h15v1H0V0zm10 10h2v5h-2v-5zm-4-3h2v8H6V7zm-4 5h2v3H2v-3zm8-7h2v10h-2V5z" />
                                </svg>
                                <span>Players</span>
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="Scheduled" role="tabpanel" aria-labelledby="Scheduled-tab">
                    <?php include __DIR__ . "/host-scheduled-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Completed" role="tabpanel" aria-labelledby="Completed-tab">
                    <?php include __DIR__ . "/host-complete-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="hostgame" role="tabpanel" aria-labelledby="hostgame-tab">
                    <?php include __DIR__ . "/host-creat-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Tournament" role="tabpanel" aria-labelledby="Tournament-tab">
                    <?php include __DIR__ . "/host-tournament-list.php"; ?>
                </div>

                <div class="tab-pane fade" id="Payment" role="tabpanel" aria-labelledby="Payment-tab">
                    <?php include __DIR__ . "/host-payment-list.php"; ?>
                </div>
                <div class="tab-pane fade" id="MonthlySub" role="tabpanel" aria-labelledby="MonthlySub-tab">
                    <?php include __DIR__ . "/host-monthly-subscription.php"; ?>
                </div>
                <?php if ($canViewSpecialTabs): ?>
                    <div class="tab-pane fade" id="Subscription" role="tabpanel" aria-labelledby="Subscription-tab">
                        <?php include __DIR__ . "/subscription-list.php"; ?>
                    </div>
                    <div class="tab-pane fade" id="playerstats" role="tabpanel" aria-labelledby="playerstats-tab">
                        <?php include __DIR__ . '/host-player-stats.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../layouts/footer/footer.php"; ?>