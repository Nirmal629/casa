<?php

declare(strict_types=1);

use App\Controllers\HostController;
use App\Middleware\AuthGuard;

/** @var App\Core\Router $router */

$hostGuard = [AuthGuard::class, ['Host', 'Trainer']];

// Host routes protected by AuthGuard for Host & Trainer roles
$router->get('/host/host-dashboard.php', [HostController::class, 'dashboard'], $hostGuard);
$router->get('/host/dashboard', [HostController::class, 'dashboard'], $hostGuard);

$router->get('/host/host-audit-log.php', function() { require_once __DIR__ . '/../Views/host/host-audit-log.php'; }, $hostGuard);
$router->get('/host/host-audit-log', function() { require_once __DIR__ . '/../Views/host/host-audit-log.php'; }, $hostGuard);

$router->get('/host/host-club-settings.php', function() { require_once __DIR__ . '/../Views/host/host-club-settings.php'; }, $hostGuard);
$router->post('/host/host-club-settings.php', function() { require_once __DIR__ . '/../Views/host/host-club-settings.php'; }, $hostGuard);

$router->get('/host/host-account.php', function() { require_once __DIR__ . '/../Views/host/host-account.php'; }, $hostGuard);
$router->get('/host/host-account', function() { require_once __DIR__ . '/../Views/host/host-account.php'; }, $hostGuard);
$router->get('/host-account.php', function() { require_once __DIR__ . '/../Views/host/host-account.php'; }, $hostGuard);
$router->get('/host-account', function() { require_once __DIR__ . '/../Views/host/host-account.php'; }, $hostGuard);

$router->get('/host/host-complete-game.php', function() { require_once __DIR__ . '/../Views/host/host-complete-game.php'; }, $hostGuard);
$router->post('/host/host-complete-game.php', function() { require_once __DIR__ . '/../Views/host/host-complete-game.php'; }, $hostGuard);

$router->get('/host/host-creat-game.php', function() { require_once __DIR__ . '/../Views/host/host-creat-game.php'; }, $hostGuard);
$router->post('/host/host-creat-game.php', [HostController::class, 'createGame'], $hostGuard);
$router->post('/api/create-event', [HostController::class, 'createGame'], $hostGuard);

$router->get('/host/host-edit-tournament.php', function() { require_once __DIR__ . '/../Views/host/host-edit-tournament.php'; }, $hostGuard);
$router->post('/host/host-edit-tournament.php', function() { require_once __DIR__ . '/../Views/host/host-edit-tournament.php'; }, $hostGuard);

$router->get('/host/host-monthly-subscription.php', function() { require_once __DIR__ . '/../Views/host/host-monthly-subscription.php'; }, $hostGuard);
$router->post('/host/host-monthly-subscription.php', function() { require_once __DIR__ . '/../Views/host/host-monthly-subscription.php'; }, $hostGuard);

$router->get('/host/host-payment-list.php', function() { require_once __DIR__ . '/../Views/host/host-payment-list.php'; }, $hostGuard);
$router->post('/host/host-payment-list.php', function() { require_once __DIR__ . '/../Views/host/host-payment-list.php'; }, $hostGuard);

$router->get('/host/host-player-stats.php', function() { require_once __DIR__ . '/../Views/host/host-player-stats.php'; }, $hostGuard);
$router->get('/host/host-roster.php', function() { require_once __DIR__ . '/../Views/host/host-roster.php'; }, $hostGuard);

$router->get('/host/host-scheduled-game.php', function() { require_once __DIR__ . '/../Views/host/host-scheduled-game.php'; }, $hostGuard);
$router->post('/host/host-scheduled-game.php', function() { require_once __DIR__ . '/../Views/host/host-scheduled-game.php'; }, $hostGuard);

$router->get('/host/host-tournament-list.php', function() { require_once __DIR__ . '/../Views/host/host-tournament-list.php'; }, $hostGuard);

// Host Event API Routes
$router->post('/api/delete_event.php', [HostController::class, 'deleteEvent'], $hostGuard);
$router->post('/api/delete-event', [HostController::class, 'deleteEvent'], $hostGuard);
$router->post('/api/delete_event', [HostController::class, 'deleteEvent'], $hostGuard);
$router->post('/api/delete-event.php', [HostController::class, 'deleteEvent'], $hostGuard);

// Host Player Rating & Stats API Routes

$router->get('/api/get-player-stats', [HostController::class, 'getPlayerStats'], $hostGuard);
$router->post('/api/get-player-stats', [HostController::class, 'getPlayerStats'], $hostGuard);
$router->get('/api/get_player_stats.php', [HostController::class, 'getPlayerStats'], $hostGuard);
$router->post('/api/get_player_stats.php', [HostController::class, 'getPlayerStats'], $hostGuard);

$router->post('/api/save_rating_by_host.php', [HostController::class, 'saveRatingByHost'], $hostGuard);
$router->post('/api/save-rating-by-host', [HostController::class, 'saveRatingByHost'], $hostGuard);
$router->post('/host/api/save_rating_by_host.php', [HostController::class, 'saveRatingByHost'], $hostGuard);
$router->post('/host/api/save-rating-by-host', [HostController::class, 'saveRatingByHost'], $hostGuard);

// Shared Guard for Host, Trainer, and Player
$gamePlayersGuard = [AuthGuard::class, ['Host', 'Trainer', 'Player']];

// Host & Player Detailed Financial Ledger API Routes
$router->get('/api/get-player-detailed-ledger', [HostController::class, 'getPlayerDetailedLedger'], $gamePlayersGuard);
$router->post('/api/get-player-detailed-ledger', [HostController::class, 'getPlayerDetailedLedger'], $gamePlayersGuard);
$router->get('/api/get_player_detailed_ledger.php', [HostController::class, 'getPlayerDetailedLedger'], $gamePlayersGuard);
$router->post('/api/get_player_detailed_ledger.php', [HostController::class, 'getPlayerDetailedLedger'], $gamePlayersGuard);

// Host Payment List Table API Routes
$router->post('/api/host/payment-list', [HostController::class, 'getHostPaymentList'], $hostGuard);
$router->get('/api/host/payment-list', [HostController::class, 'getHostPaymentList'], $hostGuard);
$router->post('/api/filter_host_payment.php', [HostController::class, 'getHostPaymentList'], $hostGuard);
$router->get('/api/filter_host_payment.php', [HostController::class, 'getHostPaymentList'], $hostGuard);
$router->post('/api/filter-host-payment', [HostController::class, 'getHostPaymentList'], $hostGuard);
$router->get('/api/filter-host-payment', [HostController::class, 'getHostPaymentList'], $hostGuard);

// Player Premium Toggle API Routes
$router->post('/api/update_player_premium.php', [HostController::class, 'updatePlayerPremium'], $hostGuard);
$router->post('/api/update-player-premium', [HostController::class, 'updatePlayerPremium'], $hostGuard);


// Search Game Players API (Handles both "Joined Players" and "Add Players" for Hosts, Trainers, and Players)
$router->post('/api/search_game_players.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->post('/api/search-game-players', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->post('/api/search_game_players', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->get('/api/search_game_players.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->get('/api/search-game-players', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->get('/api/search_game_players', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->post('/api/view_joined_all.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->post('/api/view_joined_all_default.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->get('/api/view_joined_all.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->get('/api/view_joined_all_default.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->post('/api/view_player.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);
$router->get('/api/view_player.php', [HostController::class, 'searchGamePlayers'], $gamePlayersGuard);

// Host & Player Schedule Filter Routes
$scheduleFilterGuard = [AuthGuard::class, ['Host', 'Trainer', 'Player']];
$router->post('/api/filter_schedule.php', [HostController::class, 'filterSchedule'], $scheduleFilterGuard);
$router->post('/api/filter-schedule', [HostController::class, 'filterSchedule'], $scheduleFilterGuard);
$router->get('/api/filter_schedule.php', [HostController::class, 'filterSchedule'], $scheduleFilterGuard);
$router->get('/api/filter-schedule', [HostController::class, 'filterSchedule'], $scheduleFilterGuard);

// Host Player Invitation, Confirm, and Price APIs
$router->post('/api/save_invitation.php', [HostController::class, 'saveInvitation'], $hostGuard);
$router->post('/api/save-invitation', [HostController::class, 'saveInvitation'], $hostGuard);
$router->post('/api/save_confirm.php', [HostController::class, 'saveConfirm'], $hostGuard);
$router->post('/api/save-confirm', [HostController::class, 'saveConfirm'], $hostGuard);
$router->post('/api/update_price.php', [HostController::class, 'updatePrice'], $hostGuard);
$router->post('/api/update-price', [HostController::class, 'updatePrice'], $hostGuard);
$router->post('/api/update-automation.php', [HostController::class, 'updateAutomation'], $hostGuard);
$router->post('/api/update-automation', [HostController::class, 'updateAutomation'], $hostGuard);
$router->post('/api/update_automation.php', [HostController::class, 'updateAutomation'], $hostGuard);
$router->post('/api/update_automation', [HostController::class, 'updateAutomation'], $hostGuard);

// Host Event Subscription / Scheduled Game Creation API
$router->post('/api/create_event_sub.php', [HostController::class, 'createEventSub'], $hostGuard);
$router->post('/api/create-event-sub', [HostController::class, 'createEventSub'], $hostGuard);
$router->post('/api/create_event_sub', [HostController::class, 'createEventSub'], $hostGuard);




