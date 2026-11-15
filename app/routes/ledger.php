<?php

declare(strict_types=1);

use App\Controllers\LedgerController;
use App\Middleware\AuthGuard;

/** @var App\Core\Router $router */

$ledgerGuard = [AuthGuard::class, ['Host', 'Trainer', 'Player']];

// ── Shared Viewer Endpoints ──────────────────────────────────────────
$router->get('/api/ledger/monthly', [LedgerController::class, 'monthly'], $ledgerGuard);
$router->get('/api/ledger/history', [LedgerController::class, 'history'], $ledgerGuard);
$router->get('/api/ledger/players', [\App\Controllers\HostController::class, 'getHostPaymentList'], $ledgerGuard);
$router->post('/api/ledger/players', [\App\Controllers\HostController::class, 'getHostPaymentList'], $ledgerGuard);


// ── Action Endpoints ─────────────────────────────────────────────────
$router->post('/api/payments', [LedgerController::class, 'createPayment'], $ledgerGuard);
$router->post('/api/payments/{id}/approve', [LedgerController::class, 'approvePayment'], $ledgerGuard);
$router->post('/api/payments/{id}/reject', [LedgerController::class, 'rejectPayment'], $ledgerGuard);
$router->post('/api/payments/{id}/reinitiate', [LedgerController::class, 'reinitiatePayment'], $ledgerGuard);
$router->post('/api/payments/{id}/reverse', [LedgerController::class, 'reversePayment'], $ledgerGuard);
$router->post('/api/payments/reverse', [LedgerController::class, 'reversePayment'], $ledgerGuard);

$router->post('/api/expenses', [LedgerController::class, 'createExpense'], $ledgerGuard);

$router->post('/api/ledger/carry', [LedgerController::class, 'carry'], $ledgerGuard);
$router->post('/api/ledger/carry/{id}/reverse', [LedgerController::class, 'reverseCarry'], $ledgerGuard);
$router->post('/api/ledger/carry/reverse', [LedgerController::class, 'reverseCarry'], $ledgerGuard);

$router->post('/api/ledger/override', [LedgerController::class, 'toggleOverride'], $ledgerGuard);

// ── Full Route Mappings for Unified Ledger & Clean API Endpoints ──────
$router->post('/api/payment_action.php', [LedgerController::class, 'paymentAction'], $ledgerGuard);
$router->post('/api/payment-action', [LedgerController::class, 'paymentAction'], $ledgerGuard);

$router->post('/api/save_payment.php', [LedgerController::class, 'createPayment'], $ledgerGuard);
$router->post('/api/save-payment', [LedgerController::class, 'createPayment'], $ledgerGuard);
$router->post('/api/player-save-payment', [LedgerController::class, 'createPayment'], $ledgerGuard);

$router->post('/api/rollback_payment.php', [LedgerController::class, 'reversePayment'], $ledgerGuard);
$router->post('/api/rollback-payment', [LedgerController::class, 'reversePayment'], $ledgerGuard);

$router->post('/api/rollback_carry_forward.php', [LedgerController::class, 'reverseCarry'], $ledgerGuard);
$router->post('/api/rollback-carry-forward', [LedgerController::class, 'reverseCarry'], $ledgerGuard);

$router->post('/api/add_expense.php', [LedgerController::class, 'createExpense'], $ledgerGuard);
$router->post('/api/add-expense', [LedgerController::class, 'createExpense'], $ledgerGuard);

$router->post('/api/carry_forward.php', [LedgerController::class, 'carry'], $ledgerGuard);
$router->post('/api/carry-forward', [LedgerController::class, 'carry'], $ledgerGuard);

$router->post('/api/toggle_override.php', [LedgerController::class, 'toggleOverride'], $ledgerGuard);
$router->post('/api/toggle-override', [LedgerController::class, 'toggleOverride'], $ledgerGuard);

$router->post('/api/view_player_ledger.php', [LedgerController::class, 'viewPlayerLedger'], $ledgerGuard);
$router->post('/api/view-player-ledger', [LedgerController::class, 'viewPlayerLedger'], $ledgerGuard);

// ── HTML Rendering Endpoints ─────────────────────────────────────────
$router->post('/api/render-player-pay-html', [LedgerController::class, 'renderPlayerPayHtml'], $ledgerGuard);
$router->post('/api/render_player_pay_html.php', [LedgerController::class, 'renderPlayerPayHtml'], $ledgerGuard);

