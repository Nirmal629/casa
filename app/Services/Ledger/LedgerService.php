<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Models\Ledger\LedgerModel;
use RuntimeException;

class LedgerService
{
    protected LedgerModel $model;

    public function __construct(?LedgerModel $model = null)
    {
        $this->model = $model ?? new LedgerModel();
    }

    /**
     * Shared monthly financial ledger viewer for both Host and Player.
     */
    public function getMonthlyLedger(
        int $hostId,
        int $playerId,
        int $year,
        int $month,
        ?string $authUserRole = null,
        ?int $authUserId = null
    ): array {
        // Enforce Authentication
        if ($authUserId === null || $authUserId <= 0) {
            throw new RuntimeException("Unauthenticated access to ledger.", 401);
        }

        $role = strtolower(trim((string) ($authUserRole ?? 'player')));

        // Role authorization and Counterpart validation
        if ($role === 'player') {
            // Player identity comes strictly from session
            if ($playerId !== $authUserId) {
                $playerId = $authUserId;
            }

            // Resolve / validate host counterpart
            if ($hostId <= 0) {
                $hostId = $this->resolveHostForPlayer($playerId);
            }

            if ($hostId <= 0 || !$this->model->isValidHost($hostId)) {
                return $this->buildEmptyResult($hostId, $playerId, $year, $month, true, 'Please select a valid Club / Host to view the monthly payment ledger.');
            }
        } elseif (in_array($role, ['host', 'trainer'], true)) {
            // Host/Trainer identity comes strictly from session
            if ($hostId !== $authUserId) {
                $hostId = $authUserId;
            }

            if ($playerId <= 0) {
                return $this->buildEmptyResult($hostId, $playerId, $year, $month, false, 'Please select a valid Player.');
            }

            $player = $this->model->getUser($playerId);
            if (!$player || strtolower((string) ($player['USERTYPE'] ?? '')) !== 'player') {
                throw new RuntimeException("Target player record not found or invalid.", 404);
            }
        } else {
            throw new RuntimeException("Unauthorized user role.", 403);
        }

        // Clamp year and month
        $year = ($year >= 2000 && $year <= 2100) ? $year : (int) date('Y');
        $month = ($month >= 1 && $month <= 12) ? $month : (int) date('n');

        $isPlayerSide = ($role === 'player');
        $nowYear = (int) date('Y');
        $nowMonth = (int) date('n');

        $isCurrentMonth = ($year === $nowYear && $month === $nowMonth);
        $isPastMonth = ($year < $nowYear || ($year === $nowYear && $month < $nowMonth));
        $isFutureMonth = ($year > $nowYear || ($year === $nowYear && $month > $nowMonth));

        // Month Locking check (locked if carried forward to next month)
        $isLocked = $this->model->isMonthLocked($hostId, $playerId, $year, $month);

        // Server-authorized session override check
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
            && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

        if ($isPastMonth && $isLocked && $isOverrideActive) {
            $isOverrideActive = false;
            unset($_SESSION['ledger_override'][$playerId][$year][$month]);
        }

        // Host subscription unlock check
        $subUnlock = false;
        $subIsCarried = false;
        $hasSubscription = false;
        $sub = $this->model->getAppliedSubscription($hostId, $playerId, $year, $month);
        if ($sub) {
            $hasSubscription = true;
            $subIsCarried = ((int) ($sub['is_carried'] ?? 0) === 1);
            if ($isPastMonth && !$subIsCarried) {
                $subUnlock = true;
            }
        }

        $isEffectivelyLocked = $isLocked && !$isOverrideActive;
        $monthState = $isEffectivelyLocked ? 'locked' : ($isCurrentMonth ? 'active' : 'pending');

        // Opening Balance from host_player_carry_forward
        $carryRecord = $this->model->getCarryRecord($hostId, $playerId, $year, $month);
        $openingBalance = 0.00;
        $openingDue = 0.00;
        $openingAdvance = 0.00;
        $openingRow = null;

        if ($carryRecord && (float) $carryRecord['opening_balance'] > 0) {
            $openingBalance = (float) $carryRecord['opening_balance'];
            $bType = strtoupper((string) ($carryRecord['balance_type'] ?? 'DUE'));

            if ($bType === 'DUE') {
                $openingDue = $openingBalance;
            } else {
                $openingAdvance = $openingBalance;
            }

            $sourceMonth = (int) ($carryRecord['source_month'] ?? 0);
            $sourceYear = (int) ($carryRecord['source_year'] ?? 0);
            $sourceMonthName = $sourceMonth > 0 ? date("F", mktime(0, 0, 0, $sourceMonth, 10)) : '';
            $carryLabel = "Carry Forward from " . $sourceMonthName . " " . $sourceYear;

            $openingRow = [
                'id' => (int) $carryRecord['id'],
                'amount' => $openingBalance,
                'balance_type' => $bType,
                'source_month' => $sourceMonth,
                'source_year' => $sourceYear,
                'label' => $carryLabel,
                'date_time' => sprintf('%04d-%02d-01 00:00', $year, $month),
                'venue' => 'Casa',
                'can_rollback' => (!$isPlayerSide && !$isLocked && !$isEffectivelyLocked),
            ];
        }

        // Fetch Games & Expenses
        $rawGames = $this->model->getGames($hostId, $playerId, $year, $month);
        $rawExpenses = $this->model->getExpenses($hostId, $playerId, $year, $month);

        $allCosts = [];
        $gameCostTotal = 0.00;
        $expenseCostTotal = 0.00;

        foreach ($rawGames as $g) {
            $price = (float) $g['price'];
            $gameCostTotal += $price;
            $dt = (string) ($g['date_time'] ?? '');
            $allCosts[] = [
                'kind' => 'game',
                'item_id' => (int) ($g['game_join_id'] ?? ($g['id'] ?? 0)),
                'game_id' => (int) ($g['game_id'] ?? ($g['id'] ?? 0)),
                'price' => $price,
                'currency' => $g['currency'] ?? 'CAD',
                'date_time' => $dt,
                'venue' => $g['venue'] ?? 'Casa',
                'cost_type' => $g['cost_type'] ?? 'Badminton Game',
                'event_date' => $g['event_date'] ?? substr($dt, 0, 10),
                'event_time' => $g['event_time'] ?? substr($dt, 11),
            ];
        }

        foreach ($rawExpenses as $e) {
            $amount = (float) $e['amount'];
            $expenseCostTotal += $amount;
            $dt = (string) ($e['date_time'] ?? '');
            $allCosts[] = [
                'kind' => 'expense',
                'item_id' => (int) ($e['id'] ?? 0),
                'game_id' => (int) ($e['game_id'] ?? (isset($e['id']) ? -$e['id'] : 0)), // negative ID for expense
                'price' => $amount,
                'currency' => $e['currency'] ?? 'CAD',
                'date_time' => $dt,
                'venue' => $e['venue'] ?? 'Casa',
                'cost_type' => $e['cost_type'] ?? 'Expense',
                'event_date' => $e['expense_date'] ?? substr($dt, 0, 10),
                'event_time' => $e['expense_time'] ?? substr($dt, 11),
            ];
        }

        // Sort all cost rows deterministically by date_time DESC
        usort($allCosts, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($b['event_date'] ?? ''), (string) ($a['event_date'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($b['event_time'] ?? ''), (string) ($a['event_time'] ?? ''));
        });

        // Pre-fetch payments mapped to these costs
        $costGameIds = array_map(fn($c) => (int) $c['game_id'], $allCosts);
        $paymentsByGameId = [];
        if (!empty($costGameIds)) {
            $itemPayments = $this->model->getPaymentsForGameIds($playerId, array_values(array_unique($costGameIds)));
            foreach ($itemPayments as $ip) {
                $gid = (int) $ip['GAME_ID'];
                $paymentsByGameId[$gid][] = $ip;
            }
        }

        // Process Cost Rows with partial payment accuracy
        $eventCosts = [];
        $costSl = 1;

        foreach ($allCosts as $cost) {
            $gid = (int) $cost['game_id'];
            $price = (float) $cost['price'];
            $relatedPayments = $paymentsByGameId[$gid] ?? [];

            $paidAmt = 0.00;
            $pendingAmt = 0.00;
            $pendingPaymentId = 0;
            $latestStatus = '';

            foreach ($relatedPayments as $rp) {
                $status = (string) ($rp['STATUS'] ?? '');
                $pAmount = (float) ($rp['AMOUNT'] ?? 0);
                $latestStatus = $status;

                if ($status === 'Y') {
                    $paidAmt += $pAmount;
                } elseif ($status === 'N') {
                    $pendingAmt += $pAmount;
                    if ($pendingPaymentId === 0) {
                        $pendingPaymentId = (int) $rp['ID'];
                    }
                }
            }

            $paidAmt = round($paidAmt, 2);
            $pendingAmt = round($pendingAmt, 2);
            $dueAmt = max(0.00, round($price - $paidAmt, 2));
            $coveredAmt = round($paidAmt + $pendingAmt, 2);

            // State determination
            $actionStatus = 'Unpaid';
            $canApprove = false;
            $canReject = false;
            $canPay = false;

            if ($isLocked) {
                $actionStatus = 'Locked';
            } elseif (!$isCurrentMonth && !$isOverrideActive) {
                $actionStatus = 'Pending Carry';
            } elseif ($price > 0 && $dueAmt <= 0.0001 && $paidAmt > 0) {
                $actionStatus = 'Paid';
            } elseif ($price > 0 && $coveredAmt >= ($price - 0.0001) && $pendingAmt > 0) {
                $actionStatus = 'Pending Approval';
                if (!$isPlayerSide) {
                    $canApprove = true;
                    $canReject = true;
                }
            } elseif ($paidAmt > 0 && $dueAmt > 0) {
                $actionStatus = 'Partially Paid';
                $canPay = true;
            } elseif ($pendingAmt > 0) {
                $actionStatus = 'Pending Approval';
                if (!$isPlayerSide) {
                    $canApprove = true;
                    $canReject = true;
                }
                $canPay = true;
            } elseif ($latestStatus === 'R') {
                $actionStatus = 'Rejected';
                $canPay = true; // Player can repay/reinitiate
            } else {
                $actionStatus = 'Unpaid';
                $canPay = true;
            }

            $eventCosts[] = [
                'sl' => $costSl++,
                'item_id' => $cost['item_id'],
                'game_id' => $gid,
                'kind' => $cost['kind'],
                'date_time' => $cost['date_time'],
                'venue' => $cost['venue'],
                'cost_type' => $cost['cost_type'],
                'price' => $price,
                'paid' => $paidAmt,
                'due' => $dueAmt,
                'pending_payment_id' => $pendingPaymentId,
                'latest_status' => $latestStatus,
                'action_status' => $actionStatus,
                'can_approve' => $canApprove,
                'can_reject' => $canReject,
                'can_pay' => $canPay,
            ];
        }

        // Fetch Payments for the Month
        $rawPayments = $this->model->getPayments($hostId, $playerId, $year, $month);
        $payments = [];
        $totalPaidPayments = 0.00;
        $totalPendingPayments = 0.00;
        $totalRejectedPayments = 0.00;
        $paySl = 1;

        foreach ($rawPayments as $p) {
            $pId = (int) $p['id'];
            $pAmount = (float) $p['amount'];
            $pStatus = (string) $p['status'];
            $pGameId = (int) $p['game_id'];

            if ($pStatus === 'Y') {
                $totalPaidPayments += $pAmount;
            } elseif ($pStatus === 'N') {
                $totalPendingPayments += $pAmount;
            } elseif ($pStatus === 'R') {
                $totalRejectedPayments += $pAmount;
            }

            $canRollback = false;
            $canApprove = false;
            $canReject = false;
            $canReinitiate = false;

            if ($pStatus === 'Y') {
                if (!$isPlayerSide && !$isEffectivelyLocked && ($isCurrentMonth || $isOverrideActive)) {
                    $canRollback = true;
                }
            } elseif ($pStatus === 'N') {
                if (!$isPlayerSide && !$isEffectivelyLocked && ($isCurrentMonth || $isOverrideActive)) {
                    $canApprove = true;
                    $canReject = true;
                }
            } elseif ($pStatus === 'R') {
                if ($isPlayerSide) {
                    $canReinitiate = true;
                }
            }

            $payments[] = [
                'sl' => $paySl++,
                'id' => $pId,
                'game_id' => $pGameId,
                'amount' => $pAmount,
                'date_time' => $p['date_time'] ?? trim(($p['payment_date'] ?? '') . ' ' . ($p['payment_time'] ?? '')),
                'venue' => $p['venue'] ?? '—',
                'payment_type' => $p['payment_type'],
                'status' => $pStatus,
                'rejection_reason' => $p['rejection_reason'],
                'prev_rejection_reason' => $p['prev_rejection_reason'],
                'is_reinitiated' => (bool) $p['is_reinitiated'],
                'details' => $p['details'],
                'message' => $p['message'],
                'can_rollback' => $canRollback,
                'can_approve' => $canApprove,
                'can_reject' => $canReject,
                'can_reinitiate' => $canReinitiate,
            ];
        }

        // Totals Calculation based on explicit accounting rules:
        // Amount due = opening DUE + game costs + expenses - approved payments - opening ADVANCE
        // Pending and rejected payments must NOT reduce the balance.
        $currentCosts = round($gameCostTotal + $expenseCostTotal, 2);
        $totalEventCost = round($openingDue + $currentCosts, 2);
        $totalPaidPayments = round($totalPaidPayments, 2);
        $totalPendingPayments = round($totalPendingPayments, 2);

        $closingBalance = round(($openingDue + $currentCosts) - $totalPaidPayments - $openingAdvance, 2);
        $amountDue = max(0.00, $closingBalance);
        $excessAdvance = ($closingBalance < 0.00) ? abs($closingBalance) : 0.00;

        // Destination month for carry forward
        $destMonth = ($month % 12) + 1;
        $destYear = ($month === 12) ? ($year + 1) : $year;

        // Check if carry is already applied to next month with the exact same amount and type
        $destCarry = $this->model->getCarryRecord($hostId, $playerId, $destYear, $destMonth);
        $isAlreadyCarried = false;
        if ($destCarry) {
            $destAmt = (float) ($destCarry['opening_balance'] ?? 0);
            $destType = strtoupper((string) ($destCarry['balance_type'] ?? 'DUE'));
            $expectedType = ($closingBalance > 0.00) ? 'DUE' : (($closingBalance < 0.00) ? 'ADVANCE' : 'DUE');
            if (abs($destAmt - abs($closingBalance)) < 0.001 && $destType === $expectedType) {
                $isAlreadyCarried = true;
            }
        }

        // Downstream month lock check: has next month already been carried forward?
        $isDownstreamLocked = $this->model->isMonthLocked($hostId, $playerId, $destYear, $destMonth);

        $isHostOrTrainer = in_array($role, ['host', 'trainer'], true);
        $carryTitle = 'Carry the closing balance to the next month.';
        if (!$isPastMonth) {
            $canCarry = false;
            $carryTitle = 'The current month cannot be carried forward until it ends.';
        } elseif ($isLocked && !$subUnlock) {
            $canCarry = false;
            $carryTitle = 'This month is locked (already carried forward).';
        } elseif ($isAlreadyCarried) {
            $canCarry = false;
            $carryTitle = 'Carry forward has already been applied for this month.';
        } elseif ($isDownstreamLocked && !$isOverrideActive) {
            $canCarry = false;
            $carryTitle = "The next month ({$destMonth}/{$destYear}) has already been carried forward and locked. Carry forward cannot be modified.";
        } else {
            $canCarry = ($isHostOrTrainer && !$isFutureMonth);
        }

        // Permissions
        $permissions = [
            'can_carry' => $canCarry,
            'carry_title' => $carryTitle,
            'is_already_carried' => $isAlreadyCarried,
            'is_downstream_locked' => $isDownstreamLocked,
            'dest_month' => $destMonth,
            'dest_year' => $destYear,
            'can_expense' => ($isHostOrTrainer && !$isEffectivelyLocked && ($isCurrentMonth || $isOverrideActive)),
            'can_pay' => (!$isEffectivelyLocked && ($isCurrentMonth || $isOverrideActive)),
            'can_override' => ($isHostOrTrainer && $isPastMonth && !$isLocked),
            'can_rollback_carry' => ($isHostOrTrainer && $openingRow !== null && !$isLocked && !$isEffectivelyLocked),
        ];

        $playerUser = $this->model->getUser($playerId);
        $playerName = $playerUser['NAME'] ?? 'Player';

        return [
            'success' => true,
            'host_id' => $hostId,
            'player_id' => $playerId,
            'player_name' => $playerName,
            'year' => $year,
            'month' => $month,
            'is_player_side' => $isPlayerSide,
            'month_state' => [
                'state' => $monthState,
                'is_current_month' => $isCurrentMonth,
                'is_past_month' => $isPastMonth,
                'is_future_month' => $isFutureMonth,
                'is_locked' => $isLocked,
                'is_override_active' => $isOverrideActive,
                'is_effectively_locked' => $isEffectivelyLocked,
                'has_subscription' => $hasSubscription,
                'sub_is_carried' => $subIsCarried,
                'sub_unlocked' => $subUnlock,
            ],
            'permissions' => $permissions,
            'venues' => $this->model->getVenues(),
            'opening_balance' => $openingRow,
            'event_costs' => $eventCosts,
            'payments' => $payments,
            'totals' => [
                'opening_due' => $openingDue,
                'opening_advance' => $openingAdvance,
                'game_costs' => round($gameCostTotal, 2),
                'expenses' => round($expenseCostTotal, 2),
                'current_costs' => $currentCosts,
                'total_event_cost' => $totalEventCost,
                'approved_payments' => $totalPaidPayments,
                'pending_payments' => $totalPendingPayments,
                'rejected_payments' => round($totalRejectedPayments, 2),
                'total_payments' => $totalPaidPayments, // Only approved reduce balance!
                'closing_balance' => $closingBalance,
                'amount_due' => $amountDue,
                'excess_advance' => $excessAdvance,
            ],
        ];
    }

    // Single universal summary calculation used for stats lists, cards, and legacy wrappers.
    
    public function calculateSummary(int $userId, int $hostId, int $year = 0, int $month = 0): array
    {
        if ($hostId <= 0 || $userId <= 0) {
            return [
                'games' => 0,
                'totalExpense' => 0.00,
                'totalPaid' => 0.00,
                'totalDue' => 0.00,
                'balance' => 0.00,
                'openingDue' => 0.00,
                'openingAdvance' => 0.00,
            ];
        }

        if ($year > 0 && $month > 0) {
            $ledger = $this->getMonthlyLedger($hostId, $userId, $year, $month, 'host', $hostId);
            $totals = $ledger['totals'] ?? [];
            $gameCount = count(array_filter($ledger['event_costs'] ?? [], fn($c) => ($c['kind'] ?? '') === 'game'));

            return [
                'games' => $gameCount,
                'totalExpense' => (float) ($totals['total_event_cost'] ?? 0.00),
                'totalPaid' => (float) ($totals['approved_payments'] ?? 0.00),
                'totalDue' => (float) ($totals['amount_due'] ?? 0.00),
                'balance' => (float) ($totals['closing_balance'] ?? 0.00),
                'openingDue' => (float) ($totals['opening_due'] ?? 0.00),
                'openingAdvance' => (float) ($totals['opening_advance'] ?? 0.00),
            ];
        }

        // All-time summary: aggregate games, expenses, payments, earliest opening balance
        $games = $this->model->getGames($hostId, $userId);
        $expenses = $this->model->getExpenses($hostId, $userId);
        $payments = $this->model->getPayments($hostId, $userId);

        $gameCostTotal = array_sum(array_column($games, 'price'));
        $expenseCostTotal = array_sum(array_column($expenses, 'amount'));

        $approvedPaid = 0.00;
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'Y') {
                $approvedPaid += (float) ($p['amount'] ?? 0);
            }
        }

        // Find earliest transaction to fetch opening carry if any
        $openingDue = 0.00;
        $openingAdvance = 0.00;

        $earliestYear = 9999;
        $earliestMonth = 12;

        foreach (array_merge($games, $expenses, $payments) as $row) {
            $dt = $row['effective_date'] ?? ($row['event_date'] ?? ($row['expense_date'] ?? ($row['payment_date'] ?? '')));
            if (!empty($dt)) {
                $y = (int) date('Y', strtotime($dt));
                $m = (int) date('n', strtotime($dt));
                if ($y < $earliestYear || ($y === $earliestYear && $m < $earliestMonth)) {
                    $earliestYear = $y;
                    $earliestMonth = $m;
                }
            }
        }

        if ($earliestYear !== 9999) {
            $carry = $this->model->getCarryRecord($hostId, $userId, $earliestYear, $earliestMonth);
            if ($carry && (float) $carry['opening_balance'] > 0) {
                if (strtoupper((string) $carry['balance_type']) === 'DUE') {
                    $openingDue = (float) $carry['opening_balance'];
                } else {
                    $openingAdvance = (float) $carry['opening_balance'];
                }
            }
        }

        $totalExpense = round($openingDue + $gameCostTotal + $expenseCostTotal, 2);
        $totalPaid = round($approvedPaid, 2);
        $balance = round($totalExpense - $totalPaid - $openingAdvance, 2);
        $totalDue = max(0.00, $balance);

        return [
            'games' => count($games),
            'totalExpense' => $totalExpense,
            'totalPaid' => $totalPaid,
            'totalDue' => $totalDue,
            'balance' => $balance,
            'openingDue' => round($openingDue, 2),
            'openingAdvance' => round($openingAdvance, 2),
        ];
    }

    //Scoped, paginated historical transactions with deterministic ordering and bounded page size.
    
    public function getHistory(
        int $hostId,
        int $playerId,
        int $page = 1,
        int $pageSize = 20,
        ?string $authUserRole = null,
        ?int $authUserId = null
    ): array {
        if ($authUserId === null || $authUserId <= 0) {
            throw new RuntimeException("Unauthenticated access to ledger history.", 401);
        }

        $role = strtolower(trim((string) ($authUserRole ?? 'player')));

        if ($role === 'player') {
            if ($playerId !== $authUserId) {
                $playerId = $authUserId;
            }
            if ($hostId <= 0) {
                $hostId = $this->resolveHostForPlayer($playerId);
            }
        } elseif (in_array($role, ['host', 'trainer'], true)) {
            if ($hostId !== $authUserId) {
                $hostId = $authUserId;
            }
        }

        $boundedPageSize = max(5, min(100, $pageSize));
        $historyData = $this->model->getFullLedgerData($hostId, $playerId, $page, $boundedPageSize);
        $historyData['pagination'] = \App\Helpers\Pagination::make($historyData['total_items'], $historyData['page'], $historyData['pageSize'])->toArray();

        return $historyData;
    }

    private function resolveHostForPlayer(int $playerId): int
    {
        if ($playerId <= 0) {
            return 0;
        }

        $mapped = (int) ($_SESSION['mapped_host_id'] ?? 0);
        if ($mapped > 0 && $this->model->isValidHost($mapped)) {
            return $mapped;
        }

        // Fallback: check auditResolveHostId if helper exists
        $baseDir = dirname(__DIR__, 3);
        if (file_exists($baseDir . '/app/Helpers/audit.php')) {
            require_once $baseDir . '/app/Helpers/audit.php';
        }
        if (function_exists('auditResolveHostId')) {
            $conn = \App\Config\Database::getMysqli();
            $resolved = auditResolveHostId($conn, $playerId);
            if ($resolved > 0 && $this->model->isValidHost($resolved)) {
                return $resolved;
            }
        }

        return 0;
    }

    private function buildEmptyResult(
        int $hostId,
        int $playerId,
        int $year,
        int $month,
        bool $isPlayerSide,
        string $message
    ): array {
        return [
            'success' => false,
            'message' => $message,
            'host_id' => $hostId,
            'player_id' => $playerId,
            'player_name' => 'Player',
            'year' => $year,
            'month' => $month,
            'is_player_side' => $isPlayerSide,
            'month_state' => [
                'state' => 'active',
                'is_current_month' => true,
                'is_past_month' => false,
                'is_future_month' => false,
                'is_locked' => false,
                'is_override_active' => false,
                'is_effectively_locked' => false,
                'has_subscription' => false,
                'sub_is_carried' => false,
                'sub_unlocked' => false,
            ],
            'permissions' => [
                'can_carry' => false,
                'can_expense' => false,
                'can_pay' => false,
                'can_override' => false,
                'can_rollback_carry' => false,
            ],
            'venues' => ['Casa'],
            'opening_balance' => null,
            'event_costs' => [],
            'payments' => [],
            'totals' => [
                'opening_due' => 0.00,
                'opening_advance' => 0.00,
                'game_costs' => 0.00,
                'expenses' => 0.00,
                'current_costs' => 0.00,
                'total_event_cost' => 0.00,
                'approved_payments' => 0.00,
                'pending_payments' => 0.00,
                'rejected_payments' => 0.00,
                'total_payments' => 0.00,
                'closing_balance' => 0.00,
                'amount_due' => 0.00,
                'excess_advance' => 0.00,
            ],
        ];
    }
}
