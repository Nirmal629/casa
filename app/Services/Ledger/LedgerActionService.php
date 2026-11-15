<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Models\Ledger\LedgerModel;
use RuntimeException;

class LedgerActionService
{
    protected LedgerModel $model;
    protected LedgerService $ledgerService;

    public function __construct(?LedgerModel $model = null, ?LedgerService $ledgerService = null)
    {
        $this->model = $model ?? new LedgerModel();
        $this->ledgerService = $ledgerService ?? new LedgerService($this->model);
    }

    /**
     * Record a payment (submitted by player or recorded directly by host).
     */
    public function recordPayment(array $data, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated payment request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        $isPlayer = ($role === 'player');

        $playerId = $isPlayer ? $authUserId : (int) ($data['user_id'] ?? ($data['player_id'] ?? 0));
        $hostId = $isPlayer ? (int) ($data['host_id'] ?? 0) : $authUserId;
        $gameId = (int) ($data['game_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);
        $paymentType = trim((string) ($data['payment_type'] ?? 'Interac'));
        $details = trim((string) ($data['details'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($playerId <= 0 || $amount <= 0.0001) {
            throw new RuntimeException("Invalid payment parameters. Amount must be greater than zero.", 400);
        }

        if ($paymentType === '') {
            $paymentType = 'Interac';
        }

        // Validate counterpart
        if ($isPlayer) {
            if ($hostId <= 0) {
                $hostId = (int) ($_SESSION['mapped_host_id'] ?? 0);
            }
            if ($hostId <= 0 || !$this->model->isValidHost($hostId)) {
                throw new RuntimeException("A valid host/club is required to submit payment.", 400);
            }
        } else {
            $playerUser = $this->model->getUser($playerId);
            if (!$playerUser || strtolower((string) ($playerUser['USERTYPE'] ?? '')) !== 'player') {
                throw new RuntimeException("Invalid player selected for payment.", 400);
            }
            if (!$this->model->isPlayerAssociatedWithHost($playerId, $hostId)) {
                throw new RuntimeException("The selected player is not associated with your club.", 403);
            }
        }

        // Validate game or expense ownership if payment is attached to a specific item
        if ($gameId > 0) {
            if (!$this->model->isValidGameForPlayerAndHost($gameId, $playerId, $hostId)) {
                throw new RuntimeException("Invalid game selected: this event is not associated with the specified host and player.", 400);
            }
        } elseif ($gameId < 0) {
            $expenseId = abs($gameId);
            if (!$this->model->isValidExpenseForPlayerAndHost($expenseId, $playerId, $hostId)) {
                throw new RuntimeException("Invalid expense selected: this expense is not associated with the specified host and player.", 400);
            }
        }

        // Date & Ledger month handling
        $datetimeStr = trim((string) ($data['payment_datetime'] ?? ''));
        $dateStr = trim((string) ($data['payment_date'] ?? ''));
        $timeStr = trim((string) ($data['payment_time'] ?? ''));

        if ($datetimeStr !== '') {
            $payTimestamp = strtotime($datetimeStr);
            if ($payTimestamp === false) {
                throw new RuntimeException("Invalid payment date and time format.", 400);
            }
            $paymentDate = date('Y-m-d', $payTimestamp);
            $paymentTime = date('H:i:s', $payTimestamp);
        } elseif ($dateStr !== '') {
            $payTimestamp = strtotime($dateStr . ($timeStr !== '' ? ' ' . $timeStr : ' 12:00:00'));
            if ($payTimestamp === false) {
                throw new RuntimeException("Invalid payment date format.", 400);
            }
            $paymentDate = date('Y-m-d', $payTimestamp);
            $paymentTime = $timeStr !== '' ? date('H:i:s', $payTimestamp) : date('H:i:s');
        } else {
            $paymentDate = date('Y-m-d');
            $paymentTime = date('H:i:s');
        }

        $payYear = (int) date('Y', strtotime($paymentDate));
        $payMonth = (int) date('n', strtotime($paymentDate));

        // Enforce payment date strictly matches requested ledger period if specified
        if (isset($data['year']) && (int) $data['year'] !== $payYear) {
            throw new RuntimeException("Payment date year ({$payYear}) does not match the selected ledger year ({$data['year']}).", 400);
        }
        if (isset($data['month']) && (int) $data['month'] !== $payMonth) {
            throw new RuntimeException("Payment date month ({$payMonth}) does not match the selected ledger month ({$data['month']}).", 400);
        }

        $year = $payYear;
        $month = $payMonth;

        // State & reviewer
        $status = $isPlayer ? 'N' : 'Y'; // Host-recorded payment is approved directly
        $reviewedBy = $isPlayer ? null : $hostId;
        $reviewedAt = $isPlayer ? null : date('Y-m-d H:i:s');

        // Transactional mutation + audit
        $this->model->beginTransaction();
        try {
            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Month locking & server-authorized override enforcement (verified under player lock)
            $isLocked = $this->model->isMonthLocked($hostId, $playerId, $year, $month);

            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
                && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

            if ($isLocked && !$isOverrideActive) {
                throw new RuntimeException("This month has already been carried forward and is locked.", 400);
            }

            $nowYear = (int) date('Y');
            $nowMonth = (int) date('n');

            if (!$isOverrideActive && ($year !== $nowYear || $month !== $nowMonth)) {
                throw new RuntimeException("Payments can only be added for the current month. Previous months are read-only.", 400);
            }
            $paymentId = $this->model->insertPayment(
                $playerId,
                $gameId,
                $amount,
                $paymentDate,
                $paymentTime,
                $paymentType,
                $details,
                $status,
                $reviewedBy,
                $reviewedAt,
                $message,
                $hostId
            );

            if ($paymentId <= 0) {
                throw new RuntimeException("Failed to insert payment record into database.");
            }

            $actType = $isPlayer ? 'SUBMIT_PAYMENT' : 'RECORD_PAYMENT';
            $desc = $isPlayer
                ? "Submitted payment of $" . number_format($amount, 2) . " via $paymentType for approval."
                : "Recorded direct payment of $" . number_format($amount, 2) . " via $paymentType for player ID $playerId.";

            $auditId = $this->model->logAudit(
                $playerId,
                $actType,
                $desc,
                $hostId,
                $gameId > 0 ? $gameId : null,
                [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'year' => $year,
                    'month' => $month,
                ]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. Financial mutation rolled back.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => $isPlayer ? 'Payment request submitted for approval.' : 'Payment added successfully.',
                'payment_id' => $paymentId,
                'host_id' => $hostId,
                'player_id' => $playerId,
                'year' => $year,
                'month' => $month,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Verify that the authenticated Host owns or is associated with the payment record.
     */
    protected function verifyHostPaymentOwnership(array $payment, int $authUserId): int
    {
        $paymentHostId = $this->model->resolvePaymentHostId($payment);
        $playerId = (int) ($payment['USER_ID'] ?? 0);

        if ($paymentHostId > 0) {
            if ($paymentHostId !== $authUserId) {
                throw new RuntimeException("Unauthorized. You do not have ownership of this payment record.", 403);
            }
            return $paymentHostId;
        }

        // If host was not explicitly assigned, verify that player is associated with this host
        if ($playerId > 0 && !$this->model->isPlayerAssociatedWithHost($playerId, $authUserId)) {
            throw new RuntimeException("Unauthorized. Player is not associated with your host account.", 403);
        }

        return $authUserId;
    }

    /**
     * Approve a pending payment.
     */
    public function approvePayment(int $paymentId, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Only Hosts and Trainers can approve payments.", 403);
        }

        if ($paymentId <= 0) {
            throw new RuntimeException("Invalid payment ID.", 400);
        }

        $this->model->beginTransaction();
        try {
            // Row lock
            $payment = $this->model->getPaymentForUpdate($paymentId);
            if (!$payment) {
                throw new RuntimeException("Payment record not found.", 404);
            }

            // Verify host ownership
            $actualHostId = $this->verifyHostPaymentOwnership($payment, $authUserId);

            $playerId = (int) $payment['USER_ID'];
            $gameId = (int) $payment['GAME_ID'];

            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Enforce month locking under player lock based on actual payment host and effective month
            $effDate = $this->model->getPaymentEffectiveDate($payment);
            $year = (int) date('Y', strtotime($effDate));
            $month = (int) date('n', strtotime($effDate));

            $isLocked = $this->model->isMonthLocked($actualHostId, $playerId, $year, $month);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
                && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

            if ($isLocked && !$isOverrideActive) {
                throw new RuntimeException("This month has already been carried forward and is locked. Cannot approve payment.", 400);
            }

            $currentStatus = (string) ($payment['STATUS'] ?? '');
            if ($currentStatus !== 'N') {
                throw new RuntimeException("Cannot approve payment: current status is '{$currentStatus}'. Only pending payments can be approved.", 409);
            }

            // Conditional update
            $affected = $this->model->approvePayment($paymentId, $authUserId);
            if ($affected !== 1) {
                throw new RuntimeException("Failed to approve payment: concurrent state conflict.", 409);
            }

            // Audit
            $auditId = $this->model->logAudit(
                $playerId,
                'APPROVE_PAYMENT',
                "Approved payment ID $paymentId for player ID $playerId",
                $authUserId,
                $gameId > 0 ? $gameId : null,
                ['payment_id' => $paymentId]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. State change rolled back.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Payment approved successfully.',
                'payment_id' => $paymentId,
                'player_id' => $playerId,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Reject a pending payment.
     */
    public function rejectPayment(int $paymentId, string $reason, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Only Hosts and Trainers can reject payments.", 403);
        }

        if ($paymentId <= 0) {
            throw new RuntimeException("Invalid payment ID.", 400);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException("Please provide a rejection reason.", 400);
        }

        $this->model->beginTransaction();
        try {
            // Row lock
            $payment = $this->model->getPaymentForUpdate($paymentId);
            if (!$payment) {
                throw new RuntimeException("Payment record not found.", 404);
            }

            // Verify host ownership
            $actualHostId = $this->verifyHostPaymentOwnership($payment, $authUserId);

            $playerId = (int) $payment['USER_ID'];
            $gameId = (int) $payment['GAME_ID'];

            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Enforce month locking under player lock based on actual payment host and effective month
            $effDate = $this->model->getPaymentEffectiveDate($payment);
            $year = (int) date('Y', strtotime($effDate));
            $month = (int) date('n', strtotime($effDate));

            $isLocked = $this->model->isMonthLocked($actualHostId, $playerId, $year, $month);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
                && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

            if ($isLocked && !$isOverrideActive) {
                throw new RuntimeException("This month has already been carried forward and is locked. Cannot reject payment.", 400);
            }

            $currentStatus = (string) ($payment['STATUS'] ?? '');
            if ($currentStatus !== 'N') {
                throw new RuntimeException("Cannot reject payment: current status is '{$currentStatus}'. Only pending payments can be rejected.", 409);
            }

            // Conditional update
            $affected = $this->model->rejectPayment($paymentId, $authUserId, $reason);
            if ($affected !== 1) {
                throw new RuntimeException("Failed to reject payment: concurrent state conflict.", 409);
            }

            // Audit
            $auditId = $this->model->logAudit(
                $playerId,
                'REJECT_PAYMENT',
                "Rejected payment ID $paymentId for player ID $playerId (Reason: $reason)",
                $authUserId,
                $gameId > 0 ? $gameId : null,
                ['payment_id' => $paymentId, 'reason' => $reason]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. State change rolled back.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Payment rejected successfully.',
                'payment_id' => $paymentId,
                'player_id' => $playerId,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Re-initiate a rejected payment (Player action).
     */
    public function reinitiatePayment(int $paymentId, string $details, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        if ($paymentId <= 0) {
            throw new RuntimeException("Invalid payment ID.", 400);
        }

        $role = strtolower(trim($authUserRole));

        $this->model->beginTransaction();
        try {
            // Row lock
            $payment = $this->model->getPaymentForUpdate($paymentId);
            if (!$payment) {
                throw new RuntimeException("Payment record not found.", 404);
            }

            $ownerId = (int) ($payment['USER_ID'] ?? 0);
            if ($role === 'player' && $ownerId !== $authUserId) {
                throw new RuntimeException("Unauthorized. You can only re-initiate your own payment.", 403);
            }

            $actualHostId = $this->model->resolvePaymentHostId($payment);
            if ($actualHostId <= 0) {
                $actualHostId = (int) ($payment['REVIEWED_BY'] ?? ($_SESSION['mapped_host_id'] ?? 0));
            }

            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($ownerId);

            $effDate = $this->model->getPaymentEffectiveDate($payment);
            $year = (int) date('Y', strtotime($effDate));
            $month = (int) date('n', strtotime($effDate));

            $isLocked = $this->model->isMonthLocked($actualHostId, $ownerId, $year, $month);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$ownerId][$year][$month])
                && $_SESSION['ledger_override'][$ownerId][$year][$month] === true;

            if ($isLocked && !$isOverrideActive) {
                throw new RuntimeException("This month has already been carried forward and is locked. Cannot re-initiate payment.", 400);
            }

            $currentStatus = (string) ($payment['STATUS'] ?? '');
            if ($currentStatus !== 'R') {
                throw new RuntimeException("Only rejected payments can be re-initiated. Current status is '{$currentStatus}'.", 409);
            }

            $gameId = (int) ($payment['GAME_ID'] ?? 0);
            $hostId = (int) ($payment['REVIEWED_BY'] ?? 0);

            // Conditional update: R -> N, IS_REINITIATED = 1
            $affected = $this->model->reinitiatePayment($paymentId, $ownerId, $details !== '' ? $details : null);
            if ($affected !== 1) {
                throw new RuntimeException("Failed to re-initiate payment: concurrent conflict.", 409);
            }

            // Audit
            $prevReason = (string) ($payment['REJECTION_REASON'] ?? '');
            $logDesc = "Re-initiated payment ID $paymentId";
            if ($details !== '') {
                $logDesc .= " (Player Reason: $details)";
            }
            if ($prevReason !== '') {
                $logDesc .= " (Previous Rejection: $prevReason)";
            }

            $auditId = $this->model->logAudit(
                $ownerId,
                'REINITIATE_PAYMENT',
                $logDesc,
                $hostId > 0 ? $hostId : null,
                $gameId > 0 ? $gameId : null,
                [
                    'payment_id' => $paymentId,
                    'details' => $details,
                    'prev_rejection_reason' => $prevReason,
                ]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. State change rolled back.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Payment re-initiated successfully and submitted for approval.',
                'payment_id' => $paymentId,
                'player_id' => $ownerId,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Reverse / roll back a payment (Host action).
     */
    public function reversePayment(int $paymentId, int $authUserId, string $authUserRole, array $context = []): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Only Hosts and Trainers can reverse payments.", 403);
        }

        if ($paymentId <= 0) {
            throw new RuntimeException("Invalid payment ID.", 400);
        }

        $this->model->beginTransaction();
        try {
            // Row lock
            $payment = $this->model->getPaymentForUpdate($paymentId);
            if (!$payment) {
                throw new RuntimeException("Payment record not found.", 404);
            }

            // Verify host ownership
            $actualHostId = $this->verifyHostPaymentOwnership($payment, $authUserId);

            $playerId = (int) $payment['USER_ID'];
            $gameId = (int) $payment['GAME_ID'];
            $amount = (float) $payment['AMOUNT'];
            $status = (string) $payment['STATUS'];

            if ($status !== 'Y') {
                throw new RuntimeException("Only approved (Paid) payments can be rolled back. Current status is '{$status}'.", 409);
            }

            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Determine effective payment date and month for lock checking
            $effDate = $this->model->getPaymentEffectiveDate($payment);
            $year = (int) date('Y', strtotime($effDate));
            $month = (int) date('n', strtotime($effDate));

            // Enforce month locking under player lock based on actual payment host and effective month
            $isLocked = $this->model->isMonthLocked($actualHostId, $playerId, $year, $month);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
                && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

            if ($isLocked && !$isOverrideActive) {
                throw new RuntimeException("This month has already been carried forward and is locked. Cannot rollback payment.", 400);
            }

            $affected = $this->model->deletePayment($paymentId);
            if ($affected !== 1) {
                throw new RuntimeException("Failed to reverse payment record.", 409);
            }

            // Audit
            $auditId = $this->model->logAudit(
                $playerId,
                'ROLLBACK_PAYMENT',
                "Rolled back payment ID $paymentId of $" . number_format($amount, 2) . " (Player ID: $playerId)",
                $authUserId,
                $gameId > 0 ? $gameId : null,
                [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'year' => $year,
                    'month' => $month,
                ]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. Rollback mutation reversed.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Payment rolled back successfully.',
                'payment_id' => $paymentId,
                'player_id' => $playerId,
                'year' => $year,
                'month' => $month,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Create an extra expense.
     */
    public function createExpense(array $data, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Only Hosts and Trainers can add expenses.", 403);
        }

        $playerId = (int) ($data['user_id'] ?? ($data['player_id'] ?? 0));
        $hostId = $authUserId;
        $venue = trim((string) ($data['venue'] ?? 'Casa'));
        $type = trim((string) ($data['type'] ?? 'Expense'));
        $amount = (float) ($data['amount'] ?? 0);
        $datetimeStr = trim((string) ($data['expense_datetime'] ?? ''));

        if ($playerId <= 0 || $amount <= 0.0001 || $datetimeStr === '') {
            throw new RuntimeException("Please fill all required expense fields with valid values.", 400);
        }

        $playerUser = $this->model->getUser($playerId);
        if (!$playerUser || strtolower((string) ($playerUser['USERTYPE'] ?? '')) !== 'player') {
            throw new RuntimeException("Invalid player selected for expense.", 400);
        }

        if (!$this->model->isPlayerAssociatedWithHost($playerId, $hostId)) {
            throw new RuntimeException("The selected player is not associated with your club.", 403);
        }

        $expTimestamp = strtotime($datetimeStr);
        if ($expTimestamp === false) {
            throw new RuntimeException("Invalid expense date and time format.", 400);
        }
        $expenseDate = date('Y-m-d', $expTimestamp);
        $expenseTime = date('H:i:s', $expTimestamp);

        $year = (int) ($data['year'] ?? (int) date('Y', $expTimestamp));
        $month = (int) ($data['month'] ?? (int) date('n', $expTimestamp));

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new RuntimeException("Invalid year or month for expense.", 400);
        }

        $expYear = (int) date('Y', $expTimestamp);
        $expMonth = (int) date('n', $expTimestamp);
        if ($expYear !== $year || $expMonth !== $month) {
            throw new RuntimeException("The expense date must belong to the selected ledger month.", 400);
        }

        $this->model->beginTransaction();
        try {
            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Month locking & override check (verified under player lock)
            $isLocked = $this->model->isMonthLocked($hostId, $playerId, $year, $month);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
                && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

            if ($isLocked && !$isOverrideActive) {
                throw new RuntimeException("This month has already been carried forward and is locked.", 400);
            }

            $nowYear = (int) date('Y');
            $nowMonth = (int) date('n');
            if (!$isOverrideActive && ($year !== $nowYear || $month !== $nowMonth)) {
                throw new RuntimeException("Expenses can only be added for the current month. Previous months are read-only.", 400);
            }

            $expenseId = $this->model->insertExpense(
                $playerId,
                $hostId,
                $venue,
                $type !== '' ? $type : 'Expense',
                $amount,
                $expenseDate,
                $expenseTime
            );

            if ($expenseId <= 0) {
                throw new RuntimeException("Failed to insert expense into database.");
            }

            // Audit
            $auditId = $this->model->logAudit(
                $playerId,
                'ADD_EXPENSE',
                "Added expense of $" . number_format($amount, 2) . " ($type) for player ID $playerId",
                $hostId,
                null,
                [
                    'expense_id' => $expenseId,
                    'amount' => $amount,
                    'venue' => $venue,
                    'type' => $type,
                    'year' => $year,
                    'month' => $month,
                ]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. Expense addition rolled back.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Expense added successfully.',
                'expense_id' => $expenseId,
                'host_id' => $hostId,
                'player_id' => $playerId,
                'year' => $year,
                'month' => $month,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Carry forward the closing balance of a past month to the next month.
     */
    public function carryForward(int $playerId, int $sourceYear, int $sourceMonth, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Only Hosts and Trainers can carry forward balances.", 403);
        }

        if ($playerId <= 0) {
            throw new RuntimeException("Invalid player ID for carry forward.", 400);
        }

        if ($sourceYear < 2000 || $sourceYear > 2100 || $sourceMonth < 1 || $sourceMonth > 12) {
            throw new RuntimeException("Invalid year or month for carry forward.", 400);
        }

        // Only past/finished months can be carried forward
        $nowYear = (int) date('Y');
        $nowMonth = (int) date('n');
        if ($sourceYear > $nowYear || ($sourceYear === $nowYear && $sourceMonth >= $nowMonth)) {
            throw new RuntimeException("The current month cannot be carried forward until it ends.", 400);
        }

        $hostId = $authUserId;

        $destMonth = ($sourceMonth % 12) + 1;
        $destYear = ($sourceMonth === 12) ? ($sourceYear + 1) : $sourceYear;

        $this->model->beginTransaction();
        try {
            // Shared row-level lock on the player to serialize concurrent financial mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Row-lock existing destination carry record if present
            $existing = $this->model->getCarryRecordForUpdate($hostId, $playerId, $destYear, $destMonth);

            // Calculate closing balance within the protected transactional snapshot
            $ledger = $this->ledgerService->getMonthlyLedger($hostId, $playerId, $sourceYear, $sourceMonth, $authUserRole, $authUserId);
            $closingBalance = (float) ($ledger['totals']['closing_balance'] ?? 0.00);

            $targetAmount = abs($closingBalance);
            $targetType = ($closingBalance > 0.00) ? 'DUE' : (($closingBalance < 0.00) ? 'ADVANCE' : 'DUE');

            // If already carried with the exact same amount and type, return cleanly without error
            if ($existing) {
                $exAmt = (float) $existing['opening_balance'];
                $exType = (string) $existing['balance_type'];
                if (abs($exAmt - $targetAmount) < 0.001 && $exType === $targetType) {
                    $this->model->rollBack();
                    return [
                        'success' => true,
                        'message' => 'Carry forward has already been applied for this month.',
                        'already_applied' => true,
                        'host_id' => $hostId,
                        'player_id' => $playerId,
                        'year' => $sourceYear,
                        'month' => $sourceMonth,
                    ];
                }
            }

            // Downstream month lock check: if next month was already carried forward, block modifying this carry
            $isDownstreamLocked = $this->model->isMonthLocked($hostId, $playerId, $destYear, $destMonth);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$sourceYear][$sourceMonth])
                && $_SESSION['ledger_override'][$playerId][$sourceYear][$sourceMonth] === true;

            if ($isDownstreamLocked && !$isOverrideActive) {
                throw new RuntimeException("The next month ({$destMonth}/{$destYear}) has already been carried forward and locked. Carry forward cannot be modified.", 400);
            }
            $downstreamLocked = $isDownstreamLocked ? 1 : 0;

            // Upsert into host_player_carry_forward
            $carryId = $this->model->upsertCarryRecord(
                $hostId,
                $playerId,
                $destMonth,
                $destYear,
                $sourceMonth,
                $sourceYear,
                $targetAmount,
                $targetType,
                $downstreamLocked
            );

            if ($carryId <= 0) {
                throw new RuntimeException("Failed to update carry forward record.");
            }

            // If source month has applied host subscription, set is_carried = 1
            $this->model->updateSubscriptionCarried($hostId, $playerId, $sourceYear, $sourceMonth, 1);

            // Append-only history record
            $monthName = date("F", mktime(0, 0, 0, $sourceMonth, 10));
            $remarks = ($closingBalance == 0.00)
                ? 'Carry balance reset to $0.00'
                : "Carried from {$monthName} {$sourceYear} to {$destMonth}/{$destYear}";

            $actionType = $existing ? 'UPDATE' : 'CREATE';
            $historyId = $this->model->insertCarryHistory(
                $carryId,
                $hostId,
                $playerId,
                $sourceMonth,
                $sourceYear,
                $destMonth,
                $destYear,
                $targetAmount,
                $targetType,
                $actionType,
                $remarks,
                $hostId
            );

            if ($historyId <= 0) {
                throw new RuntimeException("Failed to append carry history entry.");
            }

            // Audit
            $auditId = $this->model->logAudit(
                $playerId,
                'CARRY_FORWARD',
                "Carried forward closing balance of $" . number_format($targetAmount, 2) . " ($targetType) from $sourceMonth/$sourceYear to $destMonth/$destYear",
                $hostId,
                null,
                [
                    'carry_id' => $carryId,
                    'amount' => $targetAmount,
                    'type' => $targetType,
                    'source_year' => $sourceYear,
                    'source_month' => $sourceMonth,
                    'dest_year' => $destYear,
                    'dest_month' => $destMonth,
                ]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. Carry forward rolled back.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Balance carried forward successfully.',
                'carry_id' => $carryId,
                'host_id' => $hostId,
                'player_id' => $playerId,
                'year' => $sourceYear,
                'month' => $sourceMonth,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Reverse / roll back a carry forward operation.
     */
    public function reverseCarry(int $playerId, int $year, int $month, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Only Hosts and Trainers can roll back carry forward.", 403);
        }

        if ($playerId <= 0) {
            throw new RuntimeException("Invalid player ID for carry rollback.", 400);
        }

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new RuntimeException("Invalid year or month for carry rollback.", 400);
        }

        $hostId = $authUserId;

        $this->model->beginTransaction();
        try {
            // Uniform player row lock to serialize against carry forward and other mutations
            $this->model->lockPlayerForUpdate($playerId);

            // Re-verify downstream month lock under player lock
            $isDownstreamLocked = $this->model->isMonthLocked($hostId, $playerId, $year, $month);
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $isOverrideActive = isset($_SESSION['ledger_override'][$playerId][$year][$month])
                && $_SESSION['ledger_override'][$playerId][$year][$month] === true;

            if ($isDownstreamLocked && !$isOverrideActive) {
                throw new RuntimeException("This month is locked and cannot be rolled back.", 400);
            }

            $existing = $this->model->getCarryRecordForUpdate($hostId, $playerId, $year, $month);
            if (!$existing) {
                throw new RuntimeException("Carry forward record not found for this month.", 404);
            }

            $carryId = (int) $existing['id'];
            $openingBalance = (float) $existing['opening_balance'];
            $balanceType = (string) $existing['balance_type'];
            $sourceMonth = (int) $existing['source_month'];
            $sourceYear = (int) $existing['source_year'];

            $affected = $this->model->deleteCarryRecord($hostId, $playerId, $month, $year);
            if ($affected !== 1) {
                throw new RuntimeException("Failed to delete carry forward record.", 409);
            }

            // Reset subscription carry flag in source month if applicable
            $this->model->updateSubscriptionCarried($hostId, $playerId, $sourceYear, $sourceMonth, 0);

            // History append
            $sourceMonthName = date("F", mktime(0, 0, 0, $sourceMonth, 10));
            $remarks = "Rolled back carry forward from {$sourceMonthName} {$sourceYear} to {$month}/{$year}";
            $historyId = $this->model->insertCarryHistory(
                $carryId,
                $hostId,
                $playerId,
                $sourceMonth,
                $sourceYear,
                $month,
                $year,
                $openingBalance,
                $balanceType,
                'UPDATE', // Safe enum
                $remarks,
                $hostId
            );

            if ($historyId <= 0) {
                throw new RuntimeException("Failed to append carry history rollback entry.");
            }

            // Audit
            $auditId = $this->model->logAudit(
                $playerId,
                'ROLLBACK_CARRY_FORWARD',
                $remarks,
                $hostId,
                null,
                [
                    'carry_id' => $carryId,
                    'year' => $year,
                    'month' => $month,
                ]
            );

            if ($auditId <= 0) {
                throw new RuntimeException("Audit logging failed. Carry rollback reversed.");
            }

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Carry forward rolled back successfully.',
                'host_id' => $hostId,
                'player_id' => $playerId,
                'year' => $year,
                'month' => $month,
            ];
        } catch (\Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }
    }

    /**
     * Toggle month override lock (Host action only).
     */
    public function toggleOverride(int $playerId, int $year, int $month, bool $state, int $authUserId, string $authUserRole): array
    {
        if ($authUserId <= 0) {
            throw new RuntimeException("Unauthenticated request.", 401);
        }

        $role = strtolower(trim($authUserRole));
        if (!in_array($role, ['host', 'trainer'], true)) {
            throw new RuntimeException("Unauthorized. Players cannot toggle override locks.", 403);
        }

        if ($playerId <= 0) {
            throw new RuntimeException("Invalid player ID for override toggle.", 400);
        }

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new RuntimeException("Invalid year or month for override toggle.", 400);
        }

        $playerUser = $this->model->getUser($playerId);
        if (!$playerUser || strtolower((string) ($playerUser['USERTYPE'] ?? '')) !== 'player') {
            throw new RuntimeException("Invalid player selected for override toggle.", 400);
        }

        if (!$this->model->isPlayerAssociatedWithHost($playerId, $authUserId)) {
            throw new RuntimeException("The selected player is not associated with your club.", 403);
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if ($state) {
            $_SESSION['ledger_override'][$playerId][$year][$month] = true;
        } else {
            unset($_SESSION['ledger_override'][$playerId][$year][$month]);
        }

        // Record audit trail for override state toggle
        $actType = $state ? 'OVERRIDE_UNLOCKED' : 'OVERRIDE_LOCKED';
        $monthName = date("F", mktime(0, 0, 0, $month, 10));
        $desc = $state
            ? "Unlocked ledger month override for {$monthName} {$year} (Player ID: {$playerId})."
            : "Restored ledger month lock for {$monthName} {$year} (Player ID: {$playerId}).";

        $auditId = $this->model->logAudit(
            $playerId,
            $actType,
            $desc,
            $authUserId,
            null,
            [
                'player_id' => $playerId,
                'host_id' => $authUserId,
                'year' => $year,
                'month' => $month,
                'override_state' => $state ? 'on' : 'off',
                'performed_by' => $authUserId,
                'role' => $authUserRole,
            ]
        );

        if ($auditId <= 0) {
            // Revert session override mutation if audit logging fails
            if ($state) {
                unset($_SESSION['ledger_override'][$playerId][$year][$month]);
            } else {
                $_SESSION['ledger_override'][$playerId][$year][$month] = true;
            }
            throw new RuntimeException("Audit logging failed. Override toggle canceled.");
        }

        return [
            'success' => true,
            'state' => $state ? 'on' : 'off',
            'player_id' => $playerId,
            'year' => $year,
            'month' => $month,
            'audit_id' => $auditId,
        ];
    }
}
