<?php

declare(strict_types=1);

use App\Controllers\PlayerController;
use App\Middleware\AuthGuard;

/** @var App\Core\Router $router */

$playerGuard = [AuthGuard::class, 'Player'];

// Player routes protected by AuthGuard for Player role
$router->get('/player/player-hub.php', [PlayerController::class, 'hub'], $playerGuard);
$router->get('/player/hub', [PlayerController::class, 'hub'], $playerGuard);

$router->get('/player/player-dashboard.php', [PlayerController::class, 'dashboard'], $playerGuard);
$router->get('/player/player-dashboard', [PlayerController::class, 'dashboard'], $playerGuard);
$router->get('/player/dashboard', [PlayerController::class, 'dashboard'], $playerGuard);

$router->get('/player/player-profile.php', [PlayerController::class, 'profile'], $playerGuard);
$router->get('/player/profile', [PlayerController::class, 'profile'], $playerGuard);

// Event API Routes
$participationGuard = [AuthGuard::class, ['Player', 'Host', 'Trainer']];

$router->post('/api/join_event.php', [PlayerController::class, 'joinEvent'], $participationGuard);
$router->post('/api/join-event', [PlayerController::class, 'joinEvent'], $participationGuard);
$router->post('/api/update_inviteJoin.php', [PlayerController::class, 'acceptInvite'], $participationGuard);
$router->post('/api/update-invite-join', [PlayerController::class, 'acceptInvite'], $participationGuard);

$router->post('/api/cancel_event.php', [PlayerController::class, 'cancelEvent'], $participationGuard);
$router->post('/api/cancel-event', [PlayerController::class, 'cancelEvent'], $participationGuard);
$router->post('/api/player-pay-ledger', [PlayerController::class, 'fetchMonthlyLedger'], $participationGuard);
$router->post('/api/player-ledger-data', [PlayerController::class, 'fetchMonthlyLedgerData'], $participationGuard);
$router->post('/api/join_event_default.php', [PlayerController::class, 'handleDefaultEventAction'], $participationGuard);
$router->post('/api/cancel_event_default.php', [PlayerController::class, 'handleDefaultEventAction'], $participationGuard);
$router->post('/api/join-event-default', [PlayerController::class, 'handleDefaultEventAction'], $participationGuard);
$router->post('/api/cancel-event-default', [PlayerController::class, 'handleDefaultEventAction'], $participationGuard);

// Review API Routes
$router->post('/api/save_review.php', [PlayerController::class, 'saveReview'], $participationGuard);
$router->post('/api/save-review', [PlayerController::class, 'saveReview'], $participationGuard);

// Tournament Player Payment Routes
$router->post('/api/player_payment.php', [PlayerController::class, 'submitTournamentPayment'], $participationGuard);
$router->post('/api/player-payment', [PlayerController::class, 'submitTournamentPayment'], $participationGuard);

// Player Completed Games Filter Routes
$router->post('/api/player_filter_complete.php', [PlayerController::class, 'filterCompletedGames'], $participationGuard);
$router->post('/api/player-filter-complete', [PlayerController::class, 'filterCompletedGames'], $participationGuard);
$router->get('/api/player_filter_complete.php', [PlayerController::class, 'filterCompletedGames'], $participationGuard);
$router->get('/api/player-filter-complete', [PlayerController::class, 'filterCompletedGames'], $participationGuard);




