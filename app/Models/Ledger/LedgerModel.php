<?php

declare(strict_types=1);

namespace App\Models\Ledger;

use App\Core\Model;
use PDO;
use PDOException;

class LedgerModel extends Model
{
    protected string $table = 'ca_payment';
    protected string $primaryKey = 'ID';

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }

    public function getUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM ca_users WHERE ID = ? AND DEL_STATUS = 'N' LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function isValidHost(int $hostId): bool
    {
        if ($hostId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare("SELECT 1 FROM ca_users WHERE ID = ? AND LOWER(USERTYPE) IN ('host', 'trainer') AND DEL_STATUS = 'N' LIMIT 1");
        $stmt->execute([$hostId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmtClub = $this->db->prepare("SELECT 1 FROM ca_clubs WHERE host_id = ? LIMIT 1");
        $stmtClub->execute([$hostId]);
        return (bool) $stmtClub->fetchColumn();
    }

    public function isPlayerAssociatedWithHost(int $playerId, int $hostId): bool
    {
        if ($playerId <= 0 || $hostId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT 1 FROM ca_gamejoin WHERE USER_ID = ? AND HOST_ID = ? LIMIT 1");
        $stmt->execute([$playerId, $hostId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmtExp = $this->db->prepare("SELECT 1 FROM ca_expense WHERE USER_ID = ? AND HOST_ID = ? LIMIT 1");
        $stmtExp->execute([$playerId, $hostId]);
        if ($stmtExp->fetchColumn()) {
            return true;
        }

        $stmtSub = $this->db->prepare("SELECT 1 FROM ca_host_subscription WHERE player_id = ? AND host_id = ? LIMIT 1");
        $stmtSub->execute([$playerId, $hostId]);
        if ($stmtSub->fetchColumn()) {
            return true;
        }

        $stmtCarry = $this->db->prepare("SELECT 1 FROM host_player_carry_forward WHERE player_id = ? AND host_id = ? LIMIT 1");
        $stmtCarry->execute([$playerId, $hostId]);
        if ($stmtCarry->fetchColumn()) {
            return true;
        }

        $stmtPay = $this->db->prepare("SELECT 1 FROM ca_payment WHERE USER_ID = ? AND (HOST_ID = ? OR REVIEWED_BY = ?) LIMIT 1");
        $stmtPay->execute([$playerId, $hostId, $hostId]);
        return (bool) $stmtPay->fetchColumn();
    }

    /**
     * Check if a game/event exists and belongs to the given host and player.
     */
    public function isValidGameForPlayerAndHost(int $gameId, int $playerId, int $hostId): bool
    {
        if ($gameId <= 0 || $playerId <= 0 || $hostId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 1 
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.GAME_ID = ? AND cg.USER_ID = ? AND (cg.HOST_ID = ? OR ce.HOST_ID = ?)
            LIMIT 1
        ");
        $stmt->execute([$gameId, $playerId, $hostId, $hostId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmtEvent = $this->db->prepare("SELECT 1 FROM ca_events WHERE ID = ? AND HOST_ID = ? LIMIT 1");
        $stmtEvent->execute([$gameId, $hostId]);
        return (bool) $stmtEvent->fetchColumn();
    }

    /**
     * Check if an extra expense exists and belongs to the given host and player.
     */
    public function isValidExpenseForPlayerAndHost(int $expenseId, int $playerId, int $hostId): bool
    {
        if ($expenseId <= 0 || $playerId <= 0 || $hostId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 1 FROM ca_expense
            WHERE ID = ? AND USER_ID = ? AND HOST_ID = ?
            LIMIT 1
        ");
        $stmt->execute([$expenseId, $playerId, $hostId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Acquire exclusive row-level lock on the player's account to serialize financial mutations.
     */
    public function lockPlayerForUpdate(int $playerId): void
    {
        if ($playerId > 0) {
            $stmt = $this->db->prepare("SELECT ID FROM ca_users WHERE ID = ? FOR UPDATE");
            $stmt->execute([$playerId]);
        }
    }

    public function getVenues(): array
    {
        try {
            $stmt = $this->db->query("SELECT DISTINCT EVENT_VENUE FROM ca_events WHERE EVENT_VENUE IS NOT NULL AND EVENT_VENUE != '' ORDER BY EVENT_VENUE");
            $list = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            return !empty($list) ? $list : ['Casa'];
        } catch (\Throwable $e) {
            return ['Casa'];
        }
    }

    public function getCarryRecord(int $hostId, int $playerId, int $year, int $month): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM host_player_carry_forward
            WHERE host_id = ? AND player_id = ? AND carry_year = ? AND carry_month = ?
            LIMIT 1
        ");
        $stmt->execute([$hostId, $playerId, $year, $month]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getCarryRecordForUpdate(int $hostId, int $playerId, int $year, int $month): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM host_player_carry_forward
            WHERE host_id = ? AND player_id = ? AND carry_year = ? AND carry_month = ?
            LIMIT 1 FOR UPDATE
        ");
        $stmt->execute([$hostId, $playerId, $year, $month]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getCarryRecordById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM host_player_carry_forward WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPendingPaymentByGame(int $userId, int $gameId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ca_payment
            WHERE USER_ID = ? AND GAME_ID = ? AND STATUS = 'N'
            ORDER BY ID DESC LIMIT 1
        ");
        $stmt->execute([$userId, $gameId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function isMonthLocked(int $hostId, int $playerId, int $year, int $month): bool
    {
        $nextMonth = ($month % 12) + 1;
        $nextYear = ($month === 12) ? ($year + 1) : $year;

        $stmt = $this->db->prepare("
            SELECT 1 FROM host_player_carry_forward
            WHERE host_id = ? AND player_id = ? AND carry_year = ? AND carry_month = ?
            LIMIT 1
        ");
        $stmt->execute([$hostId, $playerId, $nextYear, $nextMonth]);
        return (bool) $stmt->fetchColumn();
    }

    public function getAppliedSubscription(int $hostId, int $playerId, int $year, int $month): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ca_host_subscription
            WHERE host_id = ? AND player_id = ? AND sub_year = ? AND sub_month = ? AND status = 'APPLIED'
            LIMIT 1
        ");
        $stmt->execute([$hostId, $playerId, $year, $month]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getGames(int $hostId, int $playerId, ?int $year = null, ?int $month = null): array
    {
        $sql = "
            SELECT 
                cg.ID AS game_join_id,
                cg.GAME_ID AS game_id,
                cg.PRICE AS price,
                cg.CURRENCY AS currency,
                ce.EVENT_DATE AS event_date,
                ce.EVENT_TIME AS event_time,
                ce.EVENT_VENUE AS venue,
                COALESCE(ce.EVENT_CATEGORY, 'Badminton Game') AS cost_type,
                DATE_FORMAT(CONCAT(ce.EVENT_DATE, ' ', COALESCE(ce.EVENT_TIME, '00:00:00')), '%Y-%m-%d %H:%i') AS date_time
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = :player_id
              AND cg.HOST_ID = :host_id
              AND cg.STATUS = 'Y'
              AND (ce.STATUS IS NULL OR (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C'))
              AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
        ";

        $params = [
            ':player_id' => $playerId,
            ':host_id' => $hostId,
        ];

        if ($year !== null && $month !== null && $year > 0 && $month > 0) {
            $sql .= " AND MONTH(ce.EVENT_DATE) = :month AND YEAR(ce.EVENT_DATE) = :year";
            $params[':month'] = $month;
            $params[':year'] = $year;
        }

        $sql .= " ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getExpenses(int $hostId, int $playerId, ?int $year = null, ?int $month = null): array
    {
        $sql = "
            SELECT 
                ex.ID AS id,
                -ex.ID AS game_id,
                ex.AMOUNT AS amount,
                ex.CURRENCY AS currency,
                ex.VENUE AS venue,
                COALESCE(ex.TYPE, 'Expense') AS cost_type,
                ex.EXPENSE_DATE AS expense_date,
                ex.EXPENSE_TIME AS expense_time,
                DATE_FORMAT(CONCAT(ex.EXPENSE_DATE, ' ', COALESCE(ex.EXPENSE_TIME, '00:00:00')), '%Y-%m-%d %H:%i') AS date_time
            FROM ca_expense ex
            WHERE ex.USER_ID = :player_id
              AND ex.HOST_ID = :host_id
              AND ex.STATUS = 'Y'
              AND NOT (ex.TYPE = 'PreviousDue' OR ex.TYPE LIKE 'Carry Forward from %')
        ";

        $params = [
            ':player_id' => $playerId,
            ':host_id' => $hostId,
        ];

        if ($year !== null && $month !== null && $year > 0 && $month > 0) {
            $sql .= " AND MONTH(ex.EXPENSE_DATE) = :month AND YEAR(ex.EXPENSE_DATE) = :year";
            $params[':month'] = $month;
            $params[':year'] = $year;
        }

        $sql .= " ORDER BY ex.EXPENSE_DATE DESC, ex.EXPENSE_TIME DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPayments(int $hostId, int $playerId, ?int $year = null, ?int $month = null): array
    {
        $sql = "
            SELECT 
                p.ID AS id,
                p.USER_ID AS user_id,
                p.GAME_ID AS game_id,
                p.AMOUNT AS amount,
                p.PAYMENT_DATE AS payment_date,
                p.PAYMENT_TIME AS payment_time,
                p.PAYMENT_TYPE AS payment_type,
                p.DETAILS AS details,
                p.MESSAGE AS message,
                p.STATUS AS status,
                p.REJECTION_REASON AS rejection_reason,
                p.PREV_REJECTION_REASON AS prev_rejection_reason,
                p.IS_REINITIATED AS is_reinitiated,
                p.REVIEWED_BY AS reviewed_by,
                p.REVIEWED_AT AS reviewed_at,
                COALESCE(e.EVENT_VENUE, ex.VENUE, p.DETAILS, 'Casa') AS venue,
                DATE_FORMAT(CONCAT(p.PAYMENT_DATE, ' ', COALESCE(p.PAYMENT_TIME, '00:00:00')), '%Y-%m-%d %H:%i') AS date_time,
                COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE) AS effective_date
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = :player_id
              AND (
                  (p.GAME_ID > 0 AND (COALESCE(p.HOST_ID, e.HOST_ID) = :host_id1 OR :host_id2 = 0))
                  OR (p.GAME_ID < 0 AND (COALESCE(p.HOST_ID, ex.HOST_ID) = :host_id3 OR :host_id4 = 0))
                  OR (p.GAME_ID = 0 AND (COALESCE(p.HOST_ID, p.REVIEWED_BY) = :host_id5 OR :host_id6 = 0))
              )
              AND NOT (
                  (p.GAME_ID = 0 AND (p.PAYMENT_TYPE = 'Carry' OR p.PAYMENT_TYPE LIKE 'Carry Forward from %'))
                  OR (p.GAME_ID > 0 AND EXISTS (
                      SELECT 1 FROM ca_events e2 WHERE e2.ID = p.GAME_ID AND (e2.EVENT_CATEGORY = 'PreviousDue' OR e2.EVENT_CATEGORY LIKE 'Carry Forward from %')
                  ))
                  OR (p.GAME_ID < 0 AND EXISTS (
                      SELECT 1 FROM ca_expense ex2 WHERE ex2.ID = -p.GAME_ID AND (ex2.TYPE = 'PreviousDue' OR ex2.TYPE LIKE 'Carry Forward from %')
                  ))
              )
        ";

        $params = [
            ':player_id' => $playerId,
            ':host_id1' => $hostId,
            ':host_id2' => $hostId,
            ':host_id3' => $hostId,
            ':host_id4' => $hostId,
            ':host_id5' => $hostId,
            ':host_id6' => $hostId,
        ];

        if ($year !== null && $month !== null && $year > 0 && $month > 0) {
            $sql .= " AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :month
                      AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :year";
            $params[':month'] = $month;
            $params[':year'] = $year;
        }

        $sql .= " ORDER BY p.PAYMENT_DATE DESC, p.PAYMENT_TIME DESC, p.ID DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPaymentsForGameIds(int $playerId, array $gameIds): array
    {
        if (empty($gameIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($gameIds), '?'));
        $sql = "
            SELECT 
                p.ID,
                p.GAME_ID,
                p.AMOUNT,
                p.STATUS,
                p.PAYMENT_DATE,
                p.PAYMENT_TIME,
                p.REJECTION_REASON,
                p.PREV_REJECTION_REASON,
                p.IS_REINITIATED
            FROM ca_payment p
            WHERE p.USER_ID = ? AND p.GAME_ID IN ($placeholders)
            ORDER BY p.ID ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$playerId], $gameIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPaymentForUpdate(int $paymentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM ca_payment WHERE ID = ? FOR UPDATE");
        $stmt->execute([$paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function resolvePaymentHostId(array $payment): int
    {
        if (!empty($payment['HOST_ID']) && (int) $payment['HOST_ID'] > 0) {
            return (int) $payment['HOST_ID'];
        }

        $gameId = (int) ($payment['GAME_ID'] ?? 0);
        if ($gameId > 0) {
            $stmt = $this->db->prepare("SELECT HOST_ID FROM ca_events WHERE ID = ? LIMIT 1");
            $stmt->execute([$gameId]);
            $hId = (int) ($stmt->fetchColumn() ?: 0);
            if ($hId > 0) {
                return $hId;
            }
        } elseif ($gameId < 0) {
            $stmt = $this->db->prepare("SELECT HOST_ID FROM ca_expense WHERE ID = ? LIMIT 1");
            $stmt->execute([abs($gameId)]);
            $hId = (int) ($stmt->fetchColumn() ?: 0);
            if ($hId > 0) {
                return $hId;
            }
        }

        return (int) ($payment['REVIEWED_BY'] ?? 0);
    }

    public function getPaymentEffectiveDate(array $payment): string
    {
        $gameId = (int) ($payment['GAME_ID'] ?? 0);
        if ($gameId > 0) {
            $stmt = $this->db->prepare("SELECT EVENT_DATE FROM ca_events WHERE ID = ? LIMIT 1");
            $stmt->execute([$gameId]);
            $date = $stmt->fetchColumn();
            if (!empty($date)) {
                return (string) $date;
            }
        } elseif ($gameId < 0) {
            $stmt = $this->db->prepare("SELECT EXPENSE_DATE FROM ca_expense WHERE ID = ? LIMIT 1");
            $stmt->execute([abs($gameId)]);
            $date = $stmt->fetchColumn();
            if (!empty($date)) {
                return (string) $date;
            }
        }

        return (string) ($payment['PAYMENT_DATE'] ?? date('Y-m-d'));
    }

    public function insertPayment(
        int $userId,
        int $gameId,
        float $amount,
        string $paymentDate,
        string $paymentTime,
        string $paymentType,
        string $details,
        string $status,
        ?int $reviewedBy = null,
        ?string $reviewedAt = null,
        string $message = '',
        ?int $hostId = null
    ): int {
        $sql = "
            INSERT INTO ca_payment 
                (USER_ID, HOST_ID, GAME_ID, AMOUNT, PAYMENT_DATE, PAYMENT_TIME, PAYMENT_TYPE, DETAILS, MESSAGE, STATUS, REVIEWED_BY, REVIEWED_AT, CREATED_AT)
            VALUES 
                (:user_id, :host_id, :game_id, :amount, :payment_date, :payment_time, :payment_type, :details, :message, :status, :reviewed_by, :reviewed_at, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':host_id' => $hostId,
            ':game_id' => $gameId,
            ':amount' => $amount,
            ':payment_date' => $paymentDate,
            ':payment_time' => $paymentTime,
            ':payment_type' => $paymentType,
            ':details' => $details,
            ':message' => $message,
            ':status' => $status,
            ':reviewed_by' => $reviewedBy,
            ':reviewed_at' => $reviewedAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function approvePayment(int $paymentId, int $reviewerId): int
    {
        $sql = "UPDATE ca_payment SET STATUS = 'Y', REVIEWED_BY = :reviewer, REVIEWED_AT = NOW() WHERE ID = :id AND STATUS = 'N'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reviewer' => $reviewerId, ':id' => $paymentId]);
        return $stmt->rowCount();
    }

    public function rejectPayment(int $paymentId, int $reviewerId, string $reason): int
    {
        $sql = "UPDATE ca_payment SET STATUS = 'R', REJECTION_REASON = :reason, REVIEWED_BY = :reviewer, REVIEWED_AT = NOW() WHERE ID = :id AND STATUS = 'N'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':reason' => $reason,
            ':reviewer' => $reviewerId,
            ':id' => $paymentId
        ]);
        return $stmt->rowCount();
    }

    public function reinitiatePayment(int $paymentId, int $userId, ?string $message = null): int
    {
        $hasMessage = ($message !== null && trim($message) !== '');
        if ($hasMessage) {
            $sql = "
                UPDATE ca_payment
                SET STATUS = 'N',
                    IS_REINITIATED = 1,
                    PREV_REJECTION_REASON = COALESCE(REJECTION_REASON, PREV_REJECTION_REASON),
                    MESSAGE = ?,
                    REJECTION_REASON = NULL,
                    REVIEWED_AT = NULL
                WHERE ID = ? AND USER_ID = ? AND STATUS = 'R'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([trim($message), $paymentId, $userId]);
        } else {
            $sql = "
                UPDATE ca_payment
                SET STATUS = 'N',
                    IS_REINITIATED = 1,
                    PREV_REJECTION_REASON = COALESCE(REJECTION_REASON, PREV_REJECTION_REASON),
                    REJECTION_REASON = NULL,
                    REVIEWED_AT = NULL
                WHERE ID = ? AND USER_ID = ? AND STATUS = 'R'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paymentId, $userId]);
        }
        return $stmt->rowCount();
    }

    public function deletePayment(int $paymentId): int
    {
        $stmt = $this->db->prepare("DELETE FROM ca_payment WHERE ID = ?");
        $stmt->execute([$paymentId]);
        return $stmt->rowCount();
    }

    public function insertExpense(
        int $userId,
        int $hostId,
        string $venue,
        string $type,
        float $amount,
        string $date,
        string $time
    ): int {
        $sql = "
            INSERT INTO ca_expense 
                (USER_ID, HOST_ID, VENUE, TYPE, AMOUNT, CURRENCY, EXPENSE_DATE, EXPENSE_TIME, STATUS, CREATED_AT)
            VALUES 
                (:user_id, :host_id, :venue, :type, :amount, 'CAD', :date, :time, 'Y', NOW())
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':host_id' => $hostId,
            ':venue' => $venue,
            ':type' => $type,
            ':amount' => $amount,
            ':date' => $date,
            ':time' => $time,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function upsertCarryRecord(
        int $hostId,
        int $playerId,
        int $destMonth,
        int $destYear,
        int $sourceMonth,
        int $sourceYear,
        float $amount,
        string $balanceType,
        int $isLocked = 0
    ): int {
        $existing = $this->getCarryRecord($hostId, $playerId, $destYear, $destMonth);
        if ($existing) {
            $id = (int) $existing['id'];
            $sql = "
                UPDATE host_player_carry_forward
                SET opening_balance = :balance,
                    balance_type = :btype,
                    source_month = :smonth,
                    source_year = :syear,
                    is_locked = :locked,
                    updated_at = NOW()
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':balance' => $amount,
                ':btype' => $balanceType,
                ':smonth' => $sourceMonth,
                ':syear' => $sourceYear,
                ':locked' => $isLocked,
                ':id' => $id,
            ]);
            return $id;
        }

        $sql = "
            INSERT INTO host_player_carry_forward
                (host_id, player_id, carry_month, carry_year, opening_balance, balance_type, source_month, source_year, is_locked, created_at, updated_at)
            VALUES
                (:host_id, :player_id, :cmonth, :cyear, :balance, :btype, :smonth, :syear, :locked, NOW(), NOW())
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':host_id' => $hostId,
            ':player_id' => $playerId,
            ':cmonth' => $destMonth,
            ':cyear' => $destYear,
            ':balance' => $amount,
            ':btype' => $balanceType,
            ':smonth' => $sourceMonth,
            ':syear' => $sourceYear,
            ':locked' => $isLocked,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteCarryRecord(int $hostId, int $playerId, int $destMonth, int $destYear): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM host_player_carry_forward
            WHERE host_id = ? AND player_id = ? AND carry_month = ? AND carry_year = ?
        ");
        $stmt->execute([$hostId, $playerId, $destMonth, $destYear]);
        return $stmt->rowCount();
    }

    public function insertCarryHistory(
        int $carryForwardId,
        int $hostId,
        int $playerId,
        int $sourceMonth,
        int $sourceYear,
        int $destMonth,
        int $destYear,
        float $amount,
        string $balanceType,
        string $action,
        string $remarks,
        int $createdBy
    ): int {
        // Enforce safe enum value: if table only supports CREATE/UPDATE, normalize ROLLBACK to UPDATE
        $safeAction = in_array(strtoupper($action), ['CREATE', 'UPDATE'], true) ? strtoupper($action) : 'UPDATE';

        $sql = "
            INSERT INTO ca_host_player_carry_forward_history
                (carry_forward_id, host_id, player_id, source_month, source_year, destination_month, destination_year, opening_balance, balance_type, action, remarks, created_by, created_at)
            VALUES
                (:cf_id, :host_id, :player_id, :smonth, :syear, :dmonth, :dyear, :balance, :btype, :action, :remarks, :created_by, NOW())
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cf_id' => $carryForwardId,
            ':host_id' => $hostId,
            ':player_id' => $playerId,
            ':smonth' => $sourceMonth,
            ':syear' => $sourceYear,
            ':dmonth' => $destMonth,
            ':dyear' => $destYear,
            ':balance' => $amount,
            ':btype' => $balanceType,
            ':action' => $safeAction,
            ':remarks' => $remarks,
            ':created_by' => $createdBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateSubscriptionCarried(int $hostId, int $playerId, int $year, int $month, int $isCarried): int
    {
        $sql = "
            UPDATE ca_host_subscription
            SET is_carried = :is_carried, updated_at = NOW()
            WHERE host_id = :host_id AND player_id = :player_id AND sub_year = :year AND sub_month = :month AND status = 'APPLIED'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':is_carried' => $isCarried,
            ':host_id' => $hostId,
            ':player_id' => $playerId,
            ':year' => $year,
            ':month' => $month,
        ]);
        return $stmt->rowCount();
    }

    public function logAudit(
        int $userId,
        string $activityType,
        string $description,
        ?int $hostId = null,
        ?int $gameId = null,
        ?array $meta = null
    ): int {
        $sql = "
            INSERT INTO ca_player_logs
                (USER_ID, HOST_ID, GAME_ID, ACTIVITY_TYPE, DESCRIPTION, META, IP_ADDRESS, CREATED_AT)
            VALUES
                (:user_id, :host_id, :game_id, :type, :desc, :meta, :ip, NOW())
        ";

        $ip = !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
            : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':host_id' => $hostId,
            ':game_id' => $gameId,
            ':type' => $activityType,
            ':desc' => $description,
            ':meta' => $meta !== null ? json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ':ip' => $ip,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getHistoricalTransactions(int $hostId, int $playerId, int $limit, int $offset): array
    {
        // Deterministic unified union of transactions: Games, Expenses, Payments
        $sql = "
            SELECT * FROM (
                SELECT 
                    cg.ID AS id,
                    'game' AS item_type,
                    cg.GAME_ID AS ref_id,
                    cg.PRICE AS amount,
                    'DEBIT' AS direction,
                    ce.EVENT_DATE AS tx_date,
                    ce.EVENT_TIME AS tx_time,
                    COALESCE(ce.EVENT_VENUE, 'Casa') AS venue,
                    COALESCE(ce.EVENT_CATEGORY, 'Badminton Game') AS description,
                    'Paid' AS status
                FROM ca_gamejoin cg
                INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
                WHERE cg.USER_ID = :pid1
                  AND (cg.HOST_ID = :hid1 OR :hid2 = 0)
                  AND cg.STATUS = 'Y'
                  AND (ce.STATUS IS NULL OR (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C'))
                  AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')

                UNION ALL

                SELECT 
                    ex.ID AS id,
                    'expense' AS item_type,
                    ex.ID AS ref_id,
                    ex.AMOUNT AS amount,
                    'DEBIT' AS direction,
                    ex.EXPENSE_DATE AS tx_date,
                    ex.EXPENSE_TIME AS tx_time,
                    COALESCE(ex.VENUE, 'Casa') AS venue,
                    COALESCE(ex.TYPE, 'Expense') AS description,
                    'Paid' AS status
                FROM ca_expense ex
                WHERE ex.USER_ID = :pid2
                  AND (ex.HOST_ID = :hid3 OR :hid4 = 0)
                  AND ex.STATUS = 'Y'
                  AND NOT (ex.TYPE = 'PreviousDue' OR ex.TYPE LIKE 'Carry Forward from %')

                UNION ALL

                SELECT 
                    p.ID AS id,
                    'payment' AS item_type,
                    p.GAME_ID AS ref_id,
                    p.AMOUNT AS amount,
                    'CREDIT' AS direction,
                    p.PAYMENT_DATE AS tx_date,
                    p.PAYMENT_TIME AS tx_time,
                    COALESCE(e.EVENT_VENUE, ex.VENUE, p.DETAILS, 'Casa') AS venue,
                    COALESCE(p.PAYMENT_TYPE, 'Payment') AS description,
                    p.STATUS AS status
                FROM ca_payment p
                LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
                LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
                WHERE p.USER_ID = :pid3
                  AND (
                      (p.GAME_ID > 0 AND (COALESCE(p.HOST_ID, e.HOST_ID) = :hid5 OR :hid6 = 0))
                      OR (p.GAME_ID < 0 AND (COALESCE(p.HOST_ID, ex.HOST_ID) = :hid7 OR :hid8 = 0))
                      OR (p.GAME_ID = 0 AND (COALESCE(p.HOST_ID, p.REVIEWED_BY) = :hid9 OR :hid10 = 0))
                  )
                  AND NOT (
                      (p.GAME_ID = 0 AND (p.PAYMENT_TYPE = 'Carry' OR p.PAYMENT_TYPE LIKE 'Carry Forward from %'))
                  )
            ) AS combined_tx
            ORDER BY tx_date DESC, tx_time DESC, id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pid1', $playerId, PDO::PARAM_INT);
        $stmt->bindValue(':hid1', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid2', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':pid2', $playerId, PDO::PARAM_INT);
        $stmt->bindValue(':hid3', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid4', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':pid3', $playerId, PDO::PARAM_INT);
        $stmt->bindValue(':hid5', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid6', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid7', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid8', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid9', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':hid10', $hostId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countHistoricalTransactions(int $hostId, int $playerId): int
    {
        $sql = "
            SELECT COUNT(*) AS total FROM (
                SELECT cg.ID FROM ca_gamejoin cg
                INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
                WHERE cg.USER_ID = :pid1 AND (cg.HOST_ID = :hid1 OR :hid2 = 0) AND cg.STATUS = 'Y'
                  AND (ce.STATUS IS NULL OR (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C'))
                  AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
                UNION ALL
                SELECT ex.ID FROM ca_expense ex
                WHERE ex.USER_ID = :pid2 AND (ex.HOST_ID = :hid3 OR :hid4 = 0) AND ex.STATUS = 'Y'
                  AND NOT (ex.TYPE = 'PreviousDue' OR ex.TYPE LIKE 'Carry Forward from %')
                UNION ALL
                SELECT p.ID FROM ca_payment p
                LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
                LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
                WHERE p.USER_ID = :pid3
                  AND (
                      (p.GAME_ID > 0 AND (COALESCE(p.HOST_ID, e.HOST_ID) = :hid5 OR :hid6 = 0))
                      OR (p.GAME_ID < 0 AND (COALESCE(p.HOST_ID, ex.HOST_ID) = :hid7 OR :hid8 = 0))
                      OR (p.GAME_ID = 0 AND (COALESCE(p.HOST_ID, p.REVIEWED_BY) = :hid9 OR :hid10 = 0))
                  )
                  AND NOT (
                      (p.GAME_ID = 0 AND (p.PAYMENT_TYPE = 'Carry' OR p.PAYMENT_TYPE LIKE 'Carry Forward from %'))
                  )
            ) AS combined_tx
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pid1' => $playerId,
            ':hid1' => $hostId,
            ':hid2' => $hostId,
            ':pid2' => $playerId,
            ':hid3' => $hostId,
            ':hid4' => $hostId,
            ':pid3' => $playerId,
            ':hid5' => $hostId,
            ':hid6' => $hostId,
            ':hid7' => $hostId,
            ':hid8' => $hostId,
            ':hid9' => $hostId,
            ':hid10' => $hostId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Complete continuous chronological ledger dataset with running balance and totals.
     */
    public function getFullLedgerData(int $hostId, int $playerId, int $page = 1, int $pageSize = 20): array
    {
        $rows = [];

        // 1. Games (ALL completed/valid games, excluding carry-forward records)
        $stmtGames = $this->db->prepare("
            SELECT
                cg.GAME_ID,
                cg.PRICE,
                cg.CURRENCY,
                ce.EVENT_DATE,
                ce.EVENT_TIME,
                ce.EVENT_VENUE,
                ce.EVENT_CATEGORY
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = :pid
                AND (cg.HOST_ID = :hid1 OR :hid2 = 0)
                AND cg.STATUS = 'Y'
                AND (ce.STATUS IS NULL OR (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C'))
                AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
            ORDER BY ce.EVENT_DATE ASC, ce.EVENT_TIME ASC
        ");
        $stmtGames->execute([':pid' => $playerId, ':hid1' => $hostId, ':hid2' => $hostId]);
        while ($g = $stmtGames->fetch(PDO::FETCH_ASSOC)) {
            $cat = $g['EVENT_CATEGORY'];
            $rows[] = [
                'date'        => $g['EVENT_DATE'],
                'time'        => $g['EVENT_TIME'],
                'venue'       => $g['EVENT_VENUE'],
                'particulars' => ($cat !== null && $cat !== '') ? $cat : 'Badminton Game',
                'kind'        => 'game',
                'debit'       => (float) $g['PRICE'],
                'credit'      => 0.0,
                'currency'    => $g['CURRENCY'] ?: 'CAD',
            ];
        }

        // 2. Extra Expenses
        $stmtExp = $this->db->prepare("
            SELECT
                ID,
                VENUE,
                TYPE,
                AMOUNT,
                CURRENCY,
                EXPENSE_DATE,
                EXPENSE_TIME
            FROM ca_expense
            WHERE USER_ID = :pid
                AND (HOST_ID = :hid1 OR :hid2 = 0)
                AND STATUS = 'Y'
                AND NOT (TYPE = 'PreviousDue' OR TYPE LIKE 'Carry Forward from %')
            ORDER BY EXPENSE_DATE ASC, EXPENSE_TIME ASC
        ");
        $stmtExp->execute([':pid' => $playerId, ':hid1' => $hostId, ':hid2' => $hostId]);
        while ($e = $stmtExp->fetch(PDO::FETCH_ASSOC)) {
            $type = $e['TYPE'];
            $rows[] = [
                'date'        => $e['EXPENSE_DATE'],
                'time'        => $e['EXPENSE_TIME'],
                'venue'       => $e['VENUE'],
                'particulars' => ($type !== null && $type !== '') ? $type : 'Expense',
                'kind'        => 'expense',
                'debit'       => (float) $e['AMOUNT'],
                'credit'      => 0.0,
                'currency'    => $e['CURRENCY'] ?: 'CAD',
            ];
        }

        // 3. Approved Payments
        $stmtPay = $this->db->prepare("
            SELECT
                p.ID,
                p.GAME_ID,
                p.AMOUNT,
                p.PAYMENT_DATE,
                p.PAYMENT_TIME,
                p.PAYMENT_TYPE,
                p.STATUS,
                COALESCE(e.EVENT_VENUE, ex.VENUE, '') AS VENUE,
                e.EVENT_CATEGORY,
                ex.TYPE AS EXPENSE_TYPE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = :pid
                AND p.STATUS = 'Y'
                AND (
                    (p.GAME_ID > 0 AND (e.HOST_ID = :hid1 OR :hid2 = 0))
                    OR (p.GAME_ID < 0 AND (ex.HOST_ID = :hid3 OR :hid4 = 0))
                    OR (p.GAME_ID = 0 AND (p.HOST_ID = :hid5 OR p.REVIEWED_BY = :hid6 OR :hid7 = 0))
                )
                AND NOT (p.GAME_ID = 0 AND (p.PAYMENT_TYPE = 'Carry' OR p.PAYMENT_TYPE LIKE 'Carry Forward from %'))
            ORDER BY p.PAYMENT_DATE ASC, p.PAYMENT_TIME ASC
        ");
        $stmtPay->execute([
            ':pid'  => $playerId,
            ':hid1' => $hostId, ':hid2' => $hostId,
            ':hid3' => $hostId, ':hid4' => $hostId,
            ':hid5' => $hostId, ':hid6' => $hostId, ':hid7' => $hostId,
        ]);
        while ($p = $stmtPay->fetch(PDO::FETCH_ASSOC)) {
            $gameId = (int) $p['GAME_ID'];
            $pType  = $p['PAYMENT_TYPE'];
            $evCat  = $p['EVENT_CATEGORY'];
            $exType = $p['EXPENSE_TYPE'];

            $isCarryPay = ($gameId === 0 && ($pType === 'Carry' || (is_string($pType) && strpos($pType, 'Carry Forward from ') === 0)));
            if ($isCarryPay) continue;

            $isSettle = (($gameId > 0 && ($evCat === 'PreviousDue' || (is_string($evCat) && strpos($evCat, 'Carry Forward from ') === 0)))
                 || ($gameId < 0 && ($exType === 'PreviousDue' || (is_string($exType) && strpos($exType, 'Carry Forward from ') === 0))));

            $kind = $isSettle ? 'carry_settle' : 'payment';

            $rows[] = [
                'date'        => $p['PAYMENT_DATE'],
                'time'        => $p['PAYMENT_TIME'],
                'venue'       => ($p['VENUE'] !== '') ? $p['VENUE'] : '—',
                'particulars' => ($pType !== null && $pType !== '') ? $pType : 'Payment',
                'kind'        => $kind,
                'debit'       => 0.0,
                'credit'      => (float) $p['AMOUNT'],
                'currency'    => 'CAD',
            ];
        }

        // Chronological sort
        usort($rows, function ($a, $b) {
            $cmp = strcmp((string) $a['date'], (string) $b['date']);
            if ($cmp !== 0) return $cmp;
            return strcmp((string) $a['time'], (string) $b['time']);
        });

        // Earliest opening balance check
        $startBal = 0.0;
        if (!empty($rows)) {
            $firstDate = $rows[0]['date'];
            $ey = (int) date('Y', strtotime($firstDate));
            $em = (int) date('n', strtotime($firstDate));
            $carry = $this->getCarryRecord($hostId, $playerId, $ey, $em);
            if ($carry && (float) $carry['opening_balance'] > 0) {
                $amt = (float) $carry['opening_balance'];
                $startBal = (strtoupper((string) $carry['balance_type']) === 'DUE') ? $amt : -$amt;
            }
        }

        $totalDebit  = 0.0;
        $totalCredit = 0.0;
        $balance     = $startBal;
        foreach ($rows as &$r) {
            $totalDebit += $r['debit'];
            $totalCredit += $r['credit'];
            $balance += $r['debit'] - $r['credit'];
            $r['running'] = round($balance, 2);
        }
        unset($r);

        $earliestDate = !empty($rows) ? $rows[0]['date'] : '';
        $latestDate   = !empty($rows) ? end($rows)['date'] : '';

        // Order by DESC date (most recent transaction first)
        $rows = array_reverse($rows);

        $totalRows  = count($rows);
        $perPage    = max(5, min(100, $pageSize));
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset     = ($page - 1) * $perPage;
        $pageRows   = array_slice($rows, $offset, $perPage);

        $playerUser = $this->getUser($playerId);
        $playerName = $playerUser['NAME'] ?? 'Player';

        return [
            'success'         => true,
            'host_id'         => $hostId,
            'player_id'       => $playerId,
            'player_name'     => $playerName,
            'total_items'     => $totalRows,
            'total_pages'     => $totalPages,
            'page'            => $page,
            'pageSize'        => $perPage,
            'offset'          => $offset,
            'first_date'      => $earliestDate,
            'last_date'       => $latestDate,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
            'closing_balance' => round($balance, 2),
            'items'           => $pageRows,
        ];
    }
}
