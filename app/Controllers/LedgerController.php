<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\LedgerActionService;
use App\Services\Ledger\LedgerPresenter;
use RuntimeException;
use Throwable;

class LedgerController extends Controller
{
    protected LedgerService $ledgerService;
    protected LedgerActionService $actionService;

    public function __construct(
        ?LedgerService $ledgerService = null,
        ?LedgerActionService $actionService = null
    ) {
        parent::__construct();
        $this->ledgerService = $ledgerService ?? new LedgerService();
        $this->actionService = $actionService ?? new LedgerActionService(null, $this->ledgerService);
    }

    private function getAuthContext(): array
    {
        $userId = Session::getCurrentUserId();
        $role = Session::getCurrentUserType() ?? 'Player';

        if ($userId === null || $userId <= 0) {
            throw new RuntimeException("Authentication required.", 401);
        }

        return [$userId, strtolower(trim((string) $role))];
    }

    /**
     * Safely attach refreshed monthly ledger HTML and state to action responses.
     * Rendering failures are logged but never convert a committed database action into an error.
     */
    private function attachRefreshedLedger(array &$res, int $hostId, int $playerId, int $year, int $month, string $role, int $authUserId, bool $isPlayer = false): void
    {
        try {
            if ($hostId > 0 && $playerId > 0 && $year > 0 && $month > 0) {
                $updatedLedger = $this->ledgerService->getMonthlyLedger($hostId, $playerId, $year, $month, $role, $authUserId);
                $updatedLedger['is_player_side'] = $isPlayer;
                // Preserve Host modal filters when replacing the ledger after an action.
                $bodyHtml = LedgerPresenter::renderMonthlyHtml($updatedLedger);
                $res['html'] = $isPlayer
                    ? $bodyHtml
                    : LedgerPresenter::renderFilterBar($playerId, $year, $month) . $bodyHtml;
                $res['ledger'] = $updatedLedger;
            }
        } catch (Throwable $renderEx) {
            error_log('[LedgerController Refresh Render Warning] ' . $renderEx->getMessage());
            $res['html'] = null;
            $res['render_warning'] = 'Operation completed successfully, but refreshed ledger markup could not be generated.';
        }
    }

    /**
     * GET /api/ledger/monthly
     */
    public function monthly(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $isPlayer = ($authUserRole === 'player') || ((int) $this->request->get('is_player_side', 0) === 1);
            $hostId = (int) $this->request->get('host_id', 0);
            $playerId = (int) $this->request->get('player_id', $this->request->get('user_id', 0));
            if ($isPlayer && $playerId <= 0) {
                $playerId = $authUserId;
            }
            if ($isPlayer && $hostId <= 0) {
                $hostId = (int) ($_SESSION['mapped_host_id'] ?? 0);
            }

            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));

            $ledger = $this->ledgerService->getMonthlyLedger($hostId, $playerId, $year, $month, $authUserRole, $authUserId);
            $ledger['is_player_side'] = $isPlayer;
            $bodyHtml = LedgerPresenter::renderMonthlyHtml($ledger);

            $ledger['html'] = $isPlayer ? $bodyHtml : (LedgerPresenter::renderFilterBar($playerId, $year, $month) . $bodyHtml);

            $this->json([
                'success' => true,
                'data' => $ledger,
                'html' => $ledger['html'],
            ], 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::monthly Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    // GET /api/ledger/history
    public function history(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $hostId = (int) $this->request->get('host_id', 0);
            $playerId = (int) $this->request->get('player_id', $this->request->get('user_id', 0));
            $page = max(1, (int) $this->request->get('page', 1));
            $pageSize = max(5, min(100, (int) $this->request->get('pageSize', 20)));

            $history = $this->ledgerService->getHistory($hostId, $playerId, $page, $pageSize, $authUserRole, $authUserId);
            $history['html'] = LedgerPresenter::renderHistoryHtml($history);

            $this->json([
                'success' => true,
                'data' => $history,
                'html' => $history['html'],
            ], 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::history Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/payments
     */
    public function createPayment(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $input = $this->request->all();
            $rawInput = @file_get_contents('php://input');
            if (!empty($rawInput)) {
                $json = @json_decode($rawInput, true);
                if (is_array($json)) {
                    $input = array_merge($input, $json);
                }
            }

            $res = $this->actionService->recordPayment($input, $authUserId, $authUserRole);

            // Fetch refreshed monthly ledger & HTML safely
            $targetHost = (int) ($res['host_id'] ?? 0);
            $targetPlayer = (int) ($res['player_id'] ?? 0);
            $targetYear = (int) ($res['year'] ?? (int) date('Y'));
            $targetMonth = (int) ($res['month'] ?? (int) date('n'));
            $isPlayer = ($authUserRole === 'player');

            $this->attachRefreshedLedger($res, $targetHost, $targetPlayer, $targetYear, $targetMonth, $authUserRole, $authUserId, $isPlayer);

            $this->json($res, 201);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::createPayment Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/payments/{id}/approve
     */
    public function approvePayment(string|int $id = 0): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $paymentId = (int) ($id > 0 ? $id : ($this->request->get('payment_id', 0)));
            if ($paymentId <= 0) {
                throw new RuntimeException("Invalid payment ID.", 400);
            }

            $res = $this->actionService->approvePayment($paymentId, $authUserId, $authUserRole);

            // Period context for refreshing HTML safely
            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));
            $playerId = (int) ($res['player_id'] ?? $this->request->get('user_id', 0));
            $hostId = $authUserId;

            $this->attachRefreshedLedger($res, $hostId, $playerId, $year, $month, $authUserRole, $authUserId);

            $this->json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::approvePayment Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/payments/{id}/reject
     */
    public function rejectPayment(string|int $id = 0): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $paymentId = (int) ($id > 0 ? $id : ($this->request->get('payment_id', 0)));
            if ($paymentId <= 0) {
                throw new RuntimeException("Invalid payment ID.", 400);
            }

            $reason = trim((string) $this->request->get('reason', ''));
            if ($reason === '') {
                $rawInput = @file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $json = @json_decode($rawInput, true);
                    if (is_array($json) && !empty($json['reason'])) {
                        $reason = trim((string) $json['reason']);
                    }
                }
            }

            $res = $this->actionService->rejectPayment($paymentId, $reason, $authUserId, $authUserRole);

            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));
            $playerId = (int) ($res['player_id'] ?? $this->request->get('user_id', 0));
            $hostId = $authUserId;

            $this->attachRefreshedLedger($res, $hostId, $playerId, $year, $month, $authUserRole, $authUserId);

            $this->json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::rejectPayment Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/payments/{id}/reinitiate
     */
    public function reinitiatePayment(string|int $id = 0): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $paymentId = (int) ($id > 0 ? $id : ($this->request->get('payment_id', 0)));
            if ($paymentId <= 0) {
                throw new RuntimeException("Invalid payment ID.", 400);
            }

            $details = trim((string) $this->request->get('details', ''));
            $res = $this->actionService->reinitiatePayment($paymentId, $details, $authUserId, $authUserRole);

            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));
            $hostId = (int) $this->request->get('host_id', 0);
            $playerId = (int) ($res['player_id'] ?? $authUserId);
            $isPlayer = ($authUserRole === 'player');

            $this->attachRefreshedLedger($res, $hostId, $playerId, $year, $month, $authUserRole, $authUserId, $isPlayer);

            $this->json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::reinitiatePayment Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/payments/{id}/reverse
     */
    public function reversePayment(string|int $id = 0): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $paymentId = (int) ($id > 0 ? $id : ($this->request->get('payment_id', 0)));
            if ($paymentId <= 0) {
                throw new RuntimeException("Invalid payment ID.", 400);
            }

            $context = [
                'year' => (int) $this->request->get('year', 0),
                'month' => (int) $this->request->get('month', 0),
            ];

            $res = $this->actionService->reversePayment($paymentId, $authUserId, $authUserRole, $context);

            $year = (int) ($res['year'] ?? (int) date('Y'));
            $month = (int) ($res['month'] ?? (int) date('n'));
            $playerId = (int) ($res['player_id'] ?? 0);
            $hostId = $authUserId;

            $this->attachRefreshedLedger($res, $hostId, $playerId, $year, $month, $authUserRole, $authUserId);

            $this->json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::reversePayment Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/expenses
     */
    public function createExpense(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $input = $this->request->all();
            $rawInput = @file_get_contents('php://input');
            if (!empty($rawInput)) {
                $json = @json_decode($rawInput, true);
                if (is_array($json)) {
                    $input = array_merge($input, $json);
                }
            }

            $res = $this->actionService->createExpense($input, $authUserId, $authUserRole);

            $targetHost = $authUserId;
            $targetPlayer = (int) ($res['player_id'] ?? 0);
            $targetYear = (int) ($res['year'] ?? (int) date('Y'));
            $targetMonth = (int) ($res['month'] ?? (int) date('n'));

            $this->attachRefreshedLedger($res, $targetHost, $targetPlayer, $targetYear, $targetMonth, $authUserRole, $authUserId);

            $this->json($res, 201);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::createExpense Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/ledger/carry
     */
    public function carry(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $playerId = (int) $this->request->get('user_id', $this->request->get('player_id', 0));
            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));

            $res = $this->actionService->carryForward($playerId, $year, $month, $authUserId, $authUserRole);

            $this->attachRefreshedLedger($res, $authUserId, $playerId, $year, $month, $authUserRole, $authUserId);

            $this->json($res, $res['success'] ? 200 : 409);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::carry Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/ledger/carry/{id}/reverse and POST /api/ledger/carry/reverse
     */
    public function reverseCarry(string|int $id = 0): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $playerId = (int) $this->request->get('user_id', $this->request->get('player_id', 0));
            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));

            $res = $this->actionService->reverseCarry($playerId, $year, $month, $authUserId, $authUserRole);

            $this->attachRefreshedLedger($res, $authUserId, $playerId, $year, $month, $authUserRole, $authUserId);

            $this->json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::reverseCarry Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * POST /api/ledger/override
     */
    public function toggleOverride(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $playerId = (int) $this->request->get('user_id', $this->request->get('player_id', 0));
            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));
            $stateParam = $this->request->get('state', 'off');
            $state = in_array(strtolower((string) $stateParam), ['on', '1', 'true'], true);

            $res = $this->actionService->toggleOverride($playerId, $year, $month, $state, $authUserId, $authUserRole);

            $isPlayer = ($authUserRole === 'player');
            $this->attachRefreshedLedger($res, $authUserId, $playerId, $year, $month, $authUserRole, $authUserId, $isPlayer);

            $this->json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::toggleOverride Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * Unified payment action endpoint (handles approve, reject, reinitiate, pay, expense, rollback, carry).
     * POST /api/payment_action.php & POST /api/payment-action
     */
    public function paymentAction(): void
    {
        try {
            [$authUserId, $authUserRole] = $this->getAuthContext();

            $action = trim((string) ($this->request->get('action', '')));
            $playerId = (int) $this->request->get('user_id', $this->request->get('player_id', 0));
            $gameId = (int) $this->request->get('game_id', 0);
            $paymentId = (int) $this->request->get('payment_id', 0);
            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));
            $hostId = (int) $this->request->get('host_id', $this->request->get('host', 0));

            $response = ['success' => false, 'message' => '', 'html' => ''];

            if ($action === 'approve') {
                if ($paymentId <= 0 && $gameId > 0 && $playerId > 0) {
                    $model = new \App\Models\Ledger\LedgerModel();
                    $pending = $model->findPendingPaymentByGame($playerId, $gameId);
                    if ($pending) {
                        $paymentId = (int) $pending['ID'];
                    }
                }
                if ($paymentId <= 0) {
                    throw new RuntimeException("Invalid payment ID for approval.", 400);
                }
                $result = $this->actionService->approvePayment($paymentId, $authUserId, $authUserRole);
                $response['success'] = true;
                $response['message'] = $result['message'];
                $playerId = (int) ($result['player_id'] ?? $playerId);
            } elseif ($action === 'reject') {
                if ($paymentId <= 0 && $gameId > 0 && $playerId > 0) {
                    $model = new \App\Models\Ledger\LedgerModel();
                    $pending = $model->findPendingPaymentByGame($playerId, $gameId);
                    if ($pending) {
                        $paymentId = (int) $pending['ID'];
                    }
                }
                if ($paymentId <= 0) {
                    throw new RuntimeException("Invalid payment ID for rejection.", 400);
                }
                $reason = trim((string) $this->request->get('reason', ''));
                $result = $this->actionService->rejectPayment($paymentId, $reason, $authUserId, $authUserRole);
                $response['success'] = true;
                $response['message'] = $result['message'];
                $playerId = (int) ($result['player_id'] ?? $playerId);
            } elseif ($action === 'reinitiate') {
                if ($paymentId <= 0) {
                    throw new RuntimeException("Invalid payment ID for re-initiation.", 400);
                }
                $details = trim((string) $this->request->get('details', ''));
                $result = $this->actionService->reinitiatePayment($paymentId, $details, $authUserId, $authUserRole);
                $response['success'] = true;
                $response['message'] = $result['message'];
                $playerId = (int) ($result['player_id'] ?? $playerId);
            } elseif ($action === 'pay') {
                $input = $this->request->all();
                $result = $this->actionService->recordPayment($input, $authUserId, $authUserRole);
                $response['success'] = true;
                $response['message'] = $result['message'];
                $hostId = (int) ($result['host_id'] ?? $hostId);
                $playerId = (int) ($result['player_id'] ?? $playerId);
            } elseif ($action === 'expense') {
                $input = $this->request->all();
                $result = $this->actionService->createExpense($input, $authUserId, $authUserRole);
                $response['success'] = true;
                $response['message'] = $result['message'];
                $playerId = (int) ($result['player_id'] ?? $playerId);
            } elseif ($action === 'rollback' || $action === 'rollback_payment') {
                $result = $this->actionService->reversePayment($paymentId, $authUserId, $authUserRole);
                $response['success'] = true;
                $response['message'] = $result['message'];
                $playerId = (int) ($result['player_id'] ?? $playerId);
            } elseif ($action === 'carry' || $action === 'carry_forward') {
                $result = $this->actionService->carryForward($playerId, $year, $month, $authUserId, $authUserRole);
                $response['success'] = $result['success'];
                $response['message'] = $result['message'];
            } elseif ($action === 'rollback_carry') {
                $result = $this->actionService->reverseCarry($playerId, $year, $month, $authUserId, $authUserRole);
                $response['success'] = $result['success'];
                $response['message'] = $result['message'];
            } elseif ($action === 'override') {
                $stateParam = $this->request->get('state', 'off');
                $state = in_array(strtolower((string) $stateParam), ['on', '1', 'true'], true);
                $result = $this->actionService->toggleOverride($playerId, $year, $month, $state, $authUserId, $authUserRole);
                $response['success'] = $result['success'];
                $response['message'] = $result['message'];
            } else {
                throw new RuntimeException("Unsupported payment action: '$action'", 400);
            }

            // Always fetch and render the updated ledger HTML safely using shared calculation engine
            $targetHost = $hostId > 0 ? $hostId : $authUserId;
            $isPlayer = ($authUserRole === 'player');
            $this->attachRefreshedLedger($response, $targetHost, $playerId, $year, $month, $authUserRole, $authUserId, $isPlayer);

            $this->json($response, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 599 ? (int) $e->getCode() : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log('[LedgerController::paymentAction Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An unexpected internal error occurred.'], 500);
        }
    }

    /**
     * View player detailed ledger endpoint (supports both JSON and HTML view for modals).
     * POST|GET /api/view_player_ledger.php & /api/view-player-ledger
     */
    public function viewPlayerLedger(): void
    {
        try {
            $authUserId = (int) (Session::getCurrentUserId() ?: ($_SESSION['user_id'] ?? 0));
            $authUserRole = strtolower(trim((string) (Session::getCurrentUserType() ?: ($_SESSION['usertype'] ?? 'Host'))));

            $userId = (int) $this->request->get('user_id', $this->request->get('player_id', 0));
            $year = (int) $this->request->get('year', 0);
            $month = (int) $this->request->get('month', 0);
            $page = max(1, (int) $this->request->get('page', 1));
            $pageSize = max(5, min(100, (int) $this->request->get('pageSize', $this->request->get('per_page', 20))));
            $isFull = !empty($this->request->get('full_ledger'));

            if (!$isFull && ($year <= 0 || $month <= 0)) {
                if ($year <= 0) $year = (int) date('Y');
                if ($month <= 0) $month = (int) date('n');
            }

            $hostId = (int) $this->request->get('host_id', (in_array($authUserRole, ['host', 'trainer', 'admin'], true) ? $authUserId : 0));

            // JSON format check
            if ($this->request->get('format') === 'json' || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
                if ($isFull || ($year <= 0 && $month <= 0)) {
                    $this->history();
                    return;
                }
                $this->monthly();
                return;
            }

            $filterBar = LedgerPresenter::renderFilterBar($userId, $year, $month);

            if ($userId <= 0) {
                echo $filterBar . '<p class="text-center text-muted py-2" style="font-size:0.8rem; margin:0;">No Record(s)</p>';
                return;
            }

            if ($year > 0 && $month > 0 && !$isFull) {
                $ledger = $this->ledgerService->getMonthlyLedger($hostId, $userId, $year, $month, $authUserRole, $authUserId);
                echo $filterBar . LedgerPresenter::renderMonthlyHtml($ledger);
                return;
            }

            // Full Ledger History via shared LedgerService
            $history = $this->ledgerService->getHistory($hostId, $userId, $page, $pageSize, $authUserRole, $authUserId);
            echo LedgerPresenter::renderHistoryHtml($history);
        } catch (Throwable $e) {
            error_log('[LedgerController::viewPlayerLedger Error] ' . $e->getMessage());
            echo '<p class="text-center text-danger py-4">Error loading ledger data.</p>';
        }
    }

    /**
     * Render player pay monthly HTML endpoint.
     * POST|GET /api/render_player_pay_html.php & /api/render-player-pay-html
     */
    public function renderPlayerPayHtml(): void
    {
        try {
            $authUserId = (int) (Session::getCurrentUserId() ?: ($_SESSION['user_id'] ?? 0));
            $authUserRole = strtolower(trim((string) (Session::getCurrentUserType() ?: ($_SESSION['usertype'] ?? 'Host'))));

            $userId = (int) $this->request->get('user_id', $this->request->get('player_id', 0));
            $year = (int) $this->request->get('year', (int) date('Y'));
            $month = (int) $this->request->get('month', (int) date('n'));
            $hostId = (int) $this->request->get('host_id', (in_array($authUserRole, ['host', 'trainer', 'admin'], true) ? $authUserId : 0));
            $isPlayerSide = (bool) $this->request->get('is_player_side', ($authUserRole === 'player'));

            $ledger = $this->ledgerService->getMonthlyLedger($hostId, $userId, $year, $month, $authUserRole, $authUserId);
            $ledger['is_player_side'] = $isPlayerSide;

            $html = LedgerPresenter::renderMonthlyHtml($ledger);

            if ($this->request->get('format') === 'json') {
                $this->json(['success' => true, 'html' => $html, 'data' => $ledger]);
                return;
            }

            echo $html;
        } catch (Throwable $e) {
            error_log('[LedgerController::renderPlayerPayHtml Error] ' . $e->getMessage());
            echo '<p class="text-center text-danger py-4">Error loading ledger HTML.</p>';
        }
    }
}

