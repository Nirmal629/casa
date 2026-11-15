<?php

declare(strict_types=1);

namespace App\Models\Player;

use App\Core\Model;
use App\Config\Database;
use PDO;
use PDOException;

class PlayerLedgerModel extends Model
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    //Calculates monthly or all-time financial ledger summary for a player.
    public function calculateSummary(int $userId, int $hostId, int $year = 0, int $month = 0): array
    {
        if ($hostId <= 0) {
            return [
                'games' => 0,
                'totalExpense' => 0.0,
                'totalPaid' => 0.0,
                'totalDue' => 0.0,
                'balance' => 0.0,
                'openingDue' => 0.0,
                'openingAdvance' => 0.0
            ];
        }

        $useDateFilter = ($year > 0 && $month > 0);

        // 1. Games (Completed, Status Y)
        $gamesSql = "
            SELECT cg.PRICE
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = :user_id
              AND cg.HOST_ID = :host_id
              AND cg.STATUS = 'Y'
              AND (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C')
              AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
        ";
        if ($useDateFilter) {
            $gamesSql .= " AND MONTH(ce.EVENT_DATE) = :month AND YEAR(ce.EVENT_DATE) = :year";
        }

        $stmt = $this->db->prepare($gamesSql);
        $params = [':user_id' => $userId, ':host_id' => $hostId];
        if ($useDateFilter) {
            $params[':month'] = $month;
            $params[':year'] = $year;
        }
        $stmt->execute($params);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $gameCount = count($games);
        $totalGameAmt = 0.0;
        foreach ($games as $gRow) {
            $totalGameAmt += (float) $gRow['PRICE'];
        }

        // 2. Expenses (Y)
        $expenseSql = "
            SELECT AMOUNT FROM ca_expense
            WHERE USER_ID = :user_id
              AND HOST_ID = :host_id
              AND STATUS = 'Y'
              AND NOT (TYPE = 'PreviousDue' OR TYPE LIKE 'Carry Forward from %')
        ";
        if ($useDateFilter) {
            $expenseSql .= " AND MONTH(EXPENSE_DATE) = :month AND YEAR(EXPENSE_DATE) = :year";
        }

        $stmtExp = $this->db->prepare($expenseSql);
        $stmtExp->execute($params);
        $expenses = $stmtExp->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalExpAmt = 0.0;
        foreach ($expenses as $eRow) {
            $totalExpAmt += (float) $eRow['AMOUNT'];
        }

        // 3. Opening Balance
        $openingDue = 0.0;
        $openingAdvance = 0.0;

        if ($useDateFilter) {
            try {
                $opStmt = $this->db->prepare("SELECT opening_balance, balance_type FROM host_player_carry_forward WHERE host_id = :host_id AND player_id = :player_id AND carry_month = :carry_month AND carry_year = :carry_year LIMIT 1");
                $opStmt->execute([
                    ':host_id' => $hostId,
                    ':player_id' => $userId,
                    ':carry_month' => $month,
                    ':carry_year' => $year
                ]);
                $opRow = $opStmt->fetch(PDO::FETCH_ASSOC);
                if ($opRow) {
                    $opAmt = (float) $opRow['opening_balance'];
                    if ($opRow['balance_type'] === 'DUE') {
                        $openingDue = $opAmt;
                    } else {
                        $openingAdvance = $opAmt;
                    }
                }
            } catch (PDOException $e) {
                $openingDue = 0.0;
                $openingAdvance = 0.0;
            }
        }

        // 4. Payments (STATUS = 'Y' only)
        $paymentsSql = "
            SELECT p.AMOUNT, p.GAME_ID, p.PAYMENT_TYPE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = :user_id
              AND p.STATUS = 'Y'
              AND (
                  (p.GAME_ID > 0 AND COALESCE(p.HOST_ID, e.HOST_ID) = :host_id1)
                  OR (p.GAME_ID < 0 AND COALESCE(p.HOST_ID, ex.HOST_ID) = :host_id2)
                  OR (p.GAME_ID = 0 AND COALESCE(p.HOST_ID, p.REVIEWED_BY) = :host_id3)
              )
        ";
        if ($useDateFilter) {
            $paymentsSql .= "
              AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :month
              AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :year
            ";
        }

        $payParams = [
            ':user_id' => $userId,
            ':host_id1' => $hostId,
            ':host_id2' => $hostId,
            ':host_id3' => $hostId,
        ];
        if ($useDateFilter) {
            $payParams[':month'] = $month;
            $payParams[':year'] = $year;
        }

        $stmtPay = $this->db->prepare($paymentsSql);
        $stmtPay->execute($payParams);
        $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalPaid = 0.0;
        foreach ($payments as $pRow) {
            $gameId = (int) $pRow['GAME_ID'];
            $pType = $pRow['PAYMENT_TYPE'];

            $isCarryPay = ($gameId === 0 && ($pType === 'Carry' || (is_string($pType) && strpos($pType, 'Carry Forward from ') === 0)));
            if ($isCarryPay) {
                continue;
            }

            $totalPaid += (float) $pRow['AMOUNT'];
        }

        $totalExpense = $totalGameAmt + $totalExpAmt + $openingDue;
        $balance = $totalExpense - $totalPaid - $openingAdvance;
        $totalDue = max(0.0, $balance);

        return [
            'games' => $gameCount,
            'totalExpense' => round($totalExpense, 2),
            'totalPaid' => round($totalPaid, 2),
            'totalDue' => round($totalDue, 2),
            'balance' => round($balance, 2),
            'openingDue' => round($openingDue, 2),
            'openingAdvance' => round($openingAdvance, 2)
        ];
    }

    //Fetches all structured ledger data for a player for a given month and year.
    public function fetchLedgerData(int $userId, int $year, int $month, int $overrideHostId = 0): array
    {
        $mysqliConn = Database::getMysqli();
        $baseDir = dirname(__DIR__, 3);
        if (file_exists($baseDir . '/app/Helpers/audit.php')) {
            require_once $baseDir . '/app/Helpers/audit.php';
        }

        $hostId = function_exists('auditResolveHostId') ? auditResolveHostId($mysqliConn, $userId, null, $overrideHostId) : $overrideHostId;

        if ($hostId <= 0) {
            return [
                'valid_host' => false,
                'message' => 'Please select a valid Club / Host to view the monthly payment ledger.'
            ];
        }

        $isCurrentMonth = ($year === (int) date('Y') && $month === (int) date('n'));
        $isPastMonth = ($year < (int) date('Y') || ($year === (int) date('Y') && $month < (int) date('n')));

        $nextMonth = ($month % 12) + 1;
        $nextYear = ($month == 12) ? ($year + 1) : $year;

        $isLocked = false;
        try {
            $lockStmt = $this->db->prepare("SELECT 1 FROM host_player_carry_forward WHERE host_id = ? AND player_id = ? AND carry_month = ? AND carry_year = ? LIMIT 1");
            $lockStmt->execute([$hostId, $userId, $nextMonth, $nextYear]);
            $isLocked = (bool) $lockStmt->fetchColumn();
        } catch (PDOException $e) {
            $isLocked = false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isOverrideActive = isset($_SESSION['ledger_override'][$userId][$year][$month]) && $_SESSION['ledger_override'][$userId][$year][$month] === true;
        if ($isPastMonth && $isLocked && $isOverrideActive) {
            $isOverrideActive = false;
            unset($_SESSION['ledger_override'][$userId][$year][$month]);
        }
        $isEffectivelyLocked = $isLocked && !$isOverrideActive;

        // Host subscription unlock check
        $subUnlock = false;
        $subIsCarried = false;
        try {
            $subStmt = $this->db->prepare("SELECT id, is_carried FROM ca_host_subscription WHERE host_id = ? AND player_id = ? AND sub_year = ? AND sub_month = ? AND status = 'APPLIED' LIMIT 1");
            $subStmt->execute([$hostId, $userId, $year, $month]);
            $suRow = $subStmt->fetch(PDO::FETCH_ASSOC);
            if ($suRow) {
                $subIsCarried = ((int) ($suRow['is_carried'] ?? 0) === 1);
                if ($isPastMonth && !$subIsCarried) {
                    $subUnlock = true;
                }
            }
        } catch (PDOException $e) {
            $subUnlock = false;
        }

        $monthState = $isEffectivelyLocked ? 'locked' : ($isCurrentMonth ? 'active' : 'pending');

        // Venues list
        $venueOptions = ['Casa'];
        try {
            $vStmt = $this->db->prepare("SELECT DISTINCT EVENT_VENUE FROM ca_events WHERE EVENT_VENUE IS NOT NULL AND EVENT_VENUE != '' ORDER BY EVENT_VENUE");
            $vStmt->execute();
            $vList = $vStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($vList)) {
                $venueOptions = $vList;
            }
        } catch (PDOException $e) {
            $venueOptions = ['Casa'];
        }

        $defaultDay = ($month == (int) date('n') && $year == (int) date('Y')) ? date('d') : '01';
        $defaultDt = sprintf('%04d-%02d-%02dT%02d:%02d', $year, $month, (int) $defaultDay, date('H'), date('i'));

        // Games Query
        $gamesSql = "
            SELECT
                cg.ID AS GAME_JOIN_ID,
                cg.GAME_ID,
                cg.PRICE,
                cg.CURRENCY,
                ce.EVENT_DATE,
                ce.EVENT_TIME,
                ce.EVENT_VENUE,
                ce.EVENT_CATEGORY AS COST_TYPE
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = ?
                AND cg.HOST_ID = ?
                AND cg.STATUS = 'Y'
                AND (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C')
                AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
                AND MONTH(ce.EVENT_DATE) = ?
                AND YEAR(ce.EVENT_DATE) = ?
            ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
        ";
        $gStmt = $this->db->prepare($gamesSql);
        $gStmt->execute([$userId, $hostId, $month, $year]);
        $gamesRows = $gStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Expenses Query
        $expenseSql = "
            SELECT
                ID,
                VENUE,
                TYPE AS COST_TYPE,
                AMOUNT,
                CURRENCY,
                EXPENSE_DATE,
                EXPENSE_TIME
            FROM ca_expense
            WHERE USER_ID = ?
                AND HOST_ID = ?
                AND STATUS = 'Y'
                AND NOT (TYPE = 'PreviousDue' OR TYPE LIKE 'Carry Forward from %')
                AND MONTH(EXPENSE_DATE) = ?
                AND YEAR(EXPENSE_DATE) = ?
            ORDER BY EXPENSE_DATE DESC, EXPENSE_TIME DESC
        ";
        $eStmt = $this->db->prepare($expenseSql);
        $eStmt->execute([$userId, $hostId, $month, $year]);
        $expenseRows = $eStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Carry Record Query
        $carryRecord = null;
        try {
            $cStmt = $this->db->prepare("SELECT opening_balance, balance_type, source_month, source_year FROM host_player_carry_forward WHERE host_id = ? AND player_id = ? AND carry_month = ? AND carry_year = ? LIMIT 1");
            $cStmt->execute([$hostId, $userId, $month, $year]);
            $carryRecord = $cStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $carryRecord = null;
        }

        $carryCostRows = [];
        $carryPayRows = [];

        if ($carryRecord && (float) $carryRecord['opening_balance'] > 0) {
            $cAmount = (float) $carryRecord['opening_balance'];
            $cMonthName = date("F", mktime(0, 0, 0, (int) $carryRecord['source_month'], 10));
            $cLabel = "Carry Forward from " . $cMonthName . " " . $carryRecord['source_year'];

            if ($carryRecord['balance_type'] === 'DUE') {
                $carryCostRows[] = [
                    'kind' => 'expense',
                    'id' => 0,
                    'price' => $cAmount,
                    'currency' => 'CAD',
                    'cost_date' => sprintf('%04d-%02d-01', $year, $month),
                    'cost_time' => '00:00:00',
                    'venue' => 'Casa',
                    'type' => $cLabel,
                ];
            } else {
                $carryPayRows[] = [
                    'ID' => 0,
                    'GAME_ID' => 0,
                    'AMOUNT' => $cAmount,
                    'PAYMENT_DATE' => sprintf('%04d-%02d-01', $year, $month),
                    'PAYMENT_TIME' => '00:00:00',
                    'PAYMENT_TYPE' => $cLabel,
                    'STATUS' => 'Y'
                ];
            }
        }

        $costRows = [];
        foreach ($gamesRows as $gRow) {
            $costRows[] = [
                'kind' => 'game',
                'id' => $gRow['GAME_ID'],
                'price' => $gRow['PRICE'],
                'currency' => $gRow['CURRENCY'],
                'cost_date' => $gRow['EVENT_DATE'],
                'cost_time' => $gRow['EVENT_TIME'],
                'venue' => $gRow['EVENT_VENUE'],
                'type' => (!empty($gRow['COST_TYPE'])) ? $gRow['COST_TYPE'] : 'Game',
            ];
        }
        foreach ($expenseRows as $eRow) {
            $costRows[] = [
                'kind' => 'expense',
                'id' => $eRow['ID'],
                'price' => $eRow['AMOUNT'],
                'currency' => (!empty($eRow['CURRENCY'])) ? $eRow['CURRENCY'] : 'CAD',
                'cost_date' => $eRow['EXPENSE_DATE'],
                'cost_time' => $eRow['EXPENSE_TIME'],
                'venue' => $eRow['VENUE'],
                'type' => (!empty($eRow['COST_TYPE'])) ? $eRow['COST_TYPE'] : 'Expense',
            ];
        }

        usort($costRows, function ($a, $b) {
            $cmp = strcmp((string) $b['cost_date'], (string) $a['cost_date']);
            if ($cmp !== 0) return $cmp;
            return strcmp((string) $b['cost_time'], (string) $a['cost_time']);
        });

        // Bulk Pre-fetch Cost Rows Payments & Latest Status to eliminate N+1 queries
        $payMap = [];
        $latestStatusMap = [];
        if (!empty($costRows)) {
            $gameIds = array_map(function ($r) {
                return ($r['kind'] === 'expense') ? (-1 * (int) $r['id']) : (int) $r['id'];
            }, $costRows);
            $uniqueGameIds = array_values(array_unique($gameIds));

            if (!empty($uniqueGameIds)) {
                $placeholders = implode(',', array_fill(0, count($uniqueGameIds), '?'));

                // 1. Bulk aggregation for Total, STATUS, PENDING_ID
                $bulkPaySql = "
                    SELECT p.GAME_ID,
                           SUM(p.AMOUNT) AS Total,
                           MAX(p.STATUS) AS STATUS,
                           MAX(CASE WHEN p.STATUS = 'N' THEN p.ID ELSE NULL END) AS PENDING_ID
                    FROM ca_payment p
                    WHERE p.USER_ID = ?
                      AND p.STATUS != 'R'
                      AND p.GAME_ID IN ($placeholders)
                    GROUP BY p.GAME_ID
                ";
                $bulkStmt = $this->db->prepare($bulkPaySql);
                $bulkStmt->execute(array_merge([$userId], $uniqueGameIds));
                while ($pRow = $bulkStmt->fetch(PDO::FETCH_ASSOC)) {
                    $payMap[(int) $pRow['GAME_ID']] = $pRow;
                }

                // 2. Bulk lookup for latest status per GAME_ID
                $bulkStatusSql = "
                    SELECT p.GAME_ID, p.STATUS
                    FROM ca_payment p
                    INNER JOIN (
                        SELECT MAX(ID) AS max_id
                        FROM ca_payment
                        WHERE USER_ID = ?
                          AND GAME_ID IN ($placeholders)
                        GROUP BY GAME_ID
                    ) m ON p.ID = m.max_id
                ";
                $statusStmt = $this->db->prepare($bulkStatusSql);
                $statusStmt->execute(array_merge([$userId], $uniqueGameIds));
                while ($sRow = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
                    $latestStatusMap[(int) $sRow['GAME_ID']] = (string) $sRow['STATUS'];
                }
            }
        }

        // Process Cost Rows Payments in-memory in O(1)
        $processedCostRows = [];
        foreach ($costRows as $row) {
            $game_id = ($row['kind'] === 'expense') ? (-1 * (int) $row['id']) : (int) $row['id'];
            $price = (float) $row['price'];

            $payData = $payMap[$game_id] ?? [];
            $paid = (float) ($payData['Total'] ?? 0);
            $due = $price - $paid;
            $latestStatus = $latestStatusMap[$game_id] ?? '';

            $processedCostRows[] = array_merge($row, [
                'game_id' => $game_id,
                'paid' => $paid,
                'due' => $due,
                'status' => $payData['STATUS'] ?? '',
                'latest_status' => $latestStatus
            ]);
        }

        // Payments Query
        $paymentsSql = "
            SELECT
                p.ID,
                p.GAME_ID,
                p.AMOUNT,
                p.PAYMENT_DATE,
                p.PAYMENT_TIME,
                p.PAYMENT_TYPE,
                p.STATUS,
                p.REJECTION_REASON,
                p.PREV_REJECTION_REASON,
                p.IS_REINITIATED,
                COALESCE(e.EVENT_VENUE, ex.VENUE, p.DETAILS, '') AS VENUE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = :user_id
                AND (
                    (p.GAME_ID > 0 AND (COALESCE(p.HOST_ID, e.HOST_ID) = :host_id1 OR e.HOST_ID IS NULL OR e.HOST_ID = 0 OR :host_id2 = 0))
                    OR (p.GAME_ID < 0 AND (COALESCE(p.HOST_ID, ex.HOST_ID) = :host_id3 OR ex.HOST_ID IS NULL OR ex.HOST_ID = 0 OR :host_id4 = 0))
                    OR (p.GAME_ID = 0 AND (COALESCE(p.HOST_ID, p.REVIEWED_BY) = :host_id5 OR p.REVIEWED_BY IS NULL OR p.REVIEWED_BY = 0 OR :host_id6 = 0))
                )
                AND NOT (
                    (p.GAME_ID = 0 AND (p.PAYMENT_TYPE = 'Carry' OR p.PAYMENT_TYPE LIKE 'Carry Forward from %'))
                    OR (p.GAME_ID > 0 AND EXISTS (
                            SELECT 1 FROM ca_events e2
                            WHERE e2.ID = p.GAME_ID
                              AND (e2.EVENT_CATEGORY = 'PreviousDue' OR e2.EVENT_CATEGORY LIKE 'Carry Forward from %')
                        ))
                    OR (p.GAME_ID < 0 AND EXISTS (
                            SELECT 1 FROM ca_expense ex2
                            WHERE ex2.ID = -p.GAME_ID
                              AND (ex2.TYPE = 'PreviousDue' OR ex2.TYPE LIKE 'Carry Forward from %')
                        ))
                )
                AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :month
                AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :year
            ORDER BY p.PAYMENT_DATE DESC, p.PAYMENT_TIME DESC
        ";
        $stmtPayments = $this->db->prepare($paymentsSql);
        $stmtPayments->execute([
            ':user_id' => $userId,
            ':host_id1' => $hostId,
            ':host_id2' => $hostId,
            ':host_id3' => $hostId,
            ':host_id4' => $hostId,
            ':host_id5' => $hostId,
            ':host_id6' => $hostId,
            ':month' => $month,
            ':year' => $year
        ]);
        $paymentsRows = $stmtPayments->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Carry Settlement Query
        $carrySettleSql = "
            SELECT
                p.ID,
                p.GAME_ID,
                p.AMOUNT,
                p.PAYMENT_DATE,
                p.PAYMENT_TIME,
                p.PAYMENT_TYPE,
                p.STATUS,
                COALESCE(e.EVENT_VENUE, ex.VENUE, p.DETAILS, '') AS VENUE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = :user_id
                AND p.STATUS != 'R'
                AND (
                    (p.GAME_ID > 0 AND (COALESCE(p.HOST_ID, e.HOST_ID) = :host_id1 OR e.HOST_ID IS NULL OR e.HOST_ID = 0 OR :host_id2 = 0))
                    OR (p.GAME_ID < 0 AND (COALESCE(p.HOST_ID, ex.HOST_ID) = :host_id3 OR ex.HOST_ID IS NULL OR ex.HOST_ID = 0 OR :host_id4 = 0))
                    OR (p.GAME_ID = 0 AND (COALESCE(p.HOST_ID, p.REVIEWED_BY) = :host_id5 OR p.REVIEWED_BY IS NULL OR p.REVIEWED_BY = 0 OR :host_id6 = 0))
                )
                AND (
                    (p.GAME_ID > 0 AND (e.EVENT_CATEGORY = 'PreviousDue' OR e.EVENT_CATEGORY LIKE 'Carry Forward from %'))
                    OR (p.GAME_ID < 0 AND (ex.TYPE = 'PreviousDue' OR ex.TYPE LIKE 'Carry Forward from %'))
                )
                AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :month
                AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = :year
            ORDER BY p.PAYMENT_DATE DESC, p.PAYMENT_TIME DESC
        ";
        $stmtCS = $this->db->prepare($carrySettleSql);
        $stmtCS->execute([
            ':user_id' => $userId,
            ':host_id1' => $hostId,
            ':host_id2' => $hostId,
            ':host_id3' => $hostId,
            ':host_id4' => $hostId,
            ':host_id5' => $hostId,
            ':host_id6' => $hostId,
            ':month' => $month,
            ':year' => $year
        ]);
        $carrySettleRows = $stmtCS->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $summary = $this->calculateSummary($userId, $hostId, $year, $month);

        $hasData = (count($processedCostRows) > 0)
            || (count($carryCostRows) > 0)
            || (count($paymentsRows) > 0)
            || (count($carryPayRows) > 0)
            || (count($carrySettleRows) > 0);

        return [
            'valid_host' => true,
            'user_id' => $userId,
            'host_id' => $hostId,
            'year' => $year,
            'month' => $month,
            'is_current_month' => $isCurrentMonth,
            'is_past_month' => $isPastMonth,
            'is_locked' => $isLocked,
            'is_override_active' => $isOverrideActive,
            'is_effectively_locked' => $isEffectivelyLocked,
            'month_state' => $monthState,
            'venue_options' => $venueOptions,
            'default_dt' => $defaultDt,
            'carry_cost_rows' => $carryCostRows,
            'cost_rows' => $processedCostRows,
            'carry_pay_rows' => $carryPayRows,
            'carry_settle_rows' => $carrySettleRows,
            'payments_rows' => $paymentsRows,
            'summary' => $summary,
            'has_data' => $hasData
        ];
    }

    // Saves a player payment submission in ca_payment table using PDO.
    public function savePlayerPayment(
        int $userId,
        int $gameId,
        float $amount,
        string $paymentDate,
        string $paymentTime,
        string $paymentType,
        string $details = '',
        string $message = '',
        int $overrideHostId = 0
    ): array {
        if ($userId <= 0 || $amount <= 0 || empty($paymentDate) || empty($paymentTime) || empty($paymentType)) {
            return [
                'status' => 'error',
                'message' => 'Invalid payment details provided.'
            ];
        }

        $hostId = $overrideHostId;
        if ($hostId <= 0 && $gameId > 0) {
            try {
                $hStmt = $this->db->prepare("SELECT HOST_ID FROM ca_events WHERE ID = ? LIMIT 1");
                $hStmt->execute([$gameId]);
                $hostId = (int) ($hStmt->fetchColumn() ?: 0);
            } catch (PDOException $e) {
                $hostId = 0;
            }
        } elseif ($hostId <= 0 && $gameId < 0) {
            try {
                $hStmt = $this->db->prepare("SELECT HOST_ID FROM ca_expense WHERE ID = ? LIMIT 1");
                $hStmt->execute([abs($gameId)]);
                $hostId = (int) ($hStmt->fetchColumn() ?: 0);
            } catch (PDOException $e) {
                $hostId = 0;
            }
        }

        if ($hostId <= 0) {
            $mysqliConn = Database::getMysqli();
            $baseDir = dirname(__DIR__, 3);
            if (file_exists($baseDir . '/app/Helpers/audit.php')) {
                require_once $baseDir . '/app/Helpers/audit.php';
            }
            $hostId = function_exists('auditResolveHostId') ? auditResolveHostId($mysqliConn, $userId, $gameId > 0 ? $gameId : null, 0) : 0;
        }

        try {
            $existingRejectedId = 0;
            $prevReason = '';
            if ($gameId != 0) {
                $checkStmt = $this->db->prepare("SELECT ID, REJECTION_REASON FROM ca_payment WHERE USER_ID = ? AND GAME_ID = ? AND STATUS = 'R' ORDER BY ID DESC LIMIT 1");
                $checkStmt->execute([$userId, $gameId]);
                $rejRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($rejRow) {
                    $existingRejectedId = (int) $rejRow['ID'];
                    $prevReason = (string) ($rejRow['REJECTION_REASON'] ?? '');
                }
            }

            if ($existingRejectedId > 0) {
                $sql = "UPDATE ca_payment 
                        SET AMOUNT = :amount, 
                            PAYMENT_DATE = :payment_date, 
                            PAYMENT_TIME = :payment_time, 
                            PAYMENT_TYPE = :payment_type, 
                            DETAILS = :details, 
                            MESSAGE = :message, 
                            STATUS = 'N', 
                            IS_REINITIATED = 1, 
                            PREV_REJECTION_REASON = CASE WHEN :prev_reason != '' THEN :prev_reason2 ELSE PREV_REJECTION_REASON END, 
                            REJECTION_REASON = NULL, 
                            HOST_ID = CASE WHEN :host_id > 0 THEN :host_id2 ELSE HOST_ID END,
                            REVIEWED_BY = CASE WHEN :host_id3 > 0 THEN :host_id4 ELSE REVIEWED_BY END, 
                            REVIEWED_AT = NULL 
                        WHERE ID = :id AND USER_ID = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':amount' => $amount,
                    ':payment_date' => $paymentDate,
                    ':payment_time' => $paymentTime,
                    ':payment_type' => $paymentType,
                    ':details' => $details,
                    ':message' => $message,
                    ':prev_reason' => $prevReason,
                    ':prev_reason2' => $prevReason,
                    ':host_id' => $hostId,
                    ':host_id2' => $hostId,
                    ':host_id3' => $hostId,
                    ':host_id4' => $hostId,
                    ':id' => $existingRejectedId,
                    ':user_id' => $userId,
                ]);
                $paymentId = $existingRejectedId;
            } elseif ($hostId > 0) {
                $sql = "INSERT INTO ca_payment (USER_ID, HOST_ID, GAME_ID, AMOUNT, PAYMENT_DATE, PAYMENT_TIME, PAYMENT_TYPE, DETAILS, MESSAGE, STATUS, REVIEWED_BY)
                        VALUES (:user_id, :host_id, :game_id, :amount, :payment_date, :payment_time, :payment_type, :details, :message, 'N', :reviewed_by)";
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
                    ':reviewed_by' => $hostId,
                ]);
                $paymentId = (int) $this->db->lastInsertId();
            } else {
                $sql = "INSERT INTO ca_payment (USER_ID, HOST_ID, GAME_ID, AMOUNT, PAYMENT_DATE, PAYMENT_TIME, PAYMENT_TYPE, DETAILS, MESSAGE, STATUS)
                        VALUES (:user_id, :host_id, :game_id, :amount, :payment_date, :payment_time, :payment_type, :details, :message, 'N')";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':host_id' => ($hostId > 0 ? $hostId : null),
                    ':game_id' => $gameId,
                    ':amount' => $amount,
                    ':payment_date' => $paymentDate,
                    ':payment_time' => $paymentTime,
                    ':payment_type' => $paymentType,
                    ':details' => $details,
                    ':message' => $message,
                ]);
                $paymentId = (int) $this->db->lastInsertId();
            }

            try {
                $mysqliConn = Database::getMysqli();
                if (function_exists('logPlayerActivity')) {
                    $actType = ($existingRejectedId > 0) ? 'REINITIATE_PAYMENT' : 'PAYMENT';
                    logPlayerActivity($mysqliConn, $userId, $actType, 'Submitted payment for game ID ' . $gameId . ' amount ' . $amount, $hostId);
                }
            } catch (\Throwable $auditErr) {
                error_log('[PlayerLedgerModel::savePlayerPayment Audit Error] ' . $auditErr->getMessage());
            }

            return [
                'status' => 'success',
                'message' => ($existingRejectedId > 0) ? 'Payment re-initiated and submitted for approval.' : 'Payment submitted for approval.',
                'payment_id' => $paymentId,
                'host_id' => $hostId
            ];
        } catch (\Throwable $e) {
            error_log('[PlayerLedgerModel::savePlayerPayment Error] ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to save payment: ' . $e->getMessage()
            ];
        }
    }

    //Re-initiates a rejected player payment (STATUS = 'R' -> 'N', IS_REINITIATED = 1) using PDO.
    public function reinitiatePlayerPayment(
        int $paymentId,
        int $userId,
        string $details = '',
        int $overrideHostId = 0
    ): array {
        if ($paymentId <= 0 || $userId <= 0) {
            return [
                'status' => 'error',
                'message' => 'Invalid payment ID or user ID.'
            ];
        }

        try {
            $pStmt = $this->db->prepare("SELECT REJECTION_REASON, GAME_ID, REVIEWED_BY FROM ca_payment WHERE ID = ? AND USER_ID = ? LIMIT 1");
            $pStmt->execute([$paymentId, $userId]);
            $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);

            if (!$pRow) {
                return [
                    'status' => 'error',
                    'message' => 'Payment record not found.'
                ];
            }

            $prevReason = (string) ($pRow['REJECTION_REASON'] ?? '');
            $gameId = (int) ($pRow['GAME_ID'] ?? 0);
            $hostId = $overrideHostId > 0 ? $overrideHostId : (int) ($pRow['REVIEWED_BY'] ?? 0);

            if ($hostId <= 0) {
                $mysqliConn = Database::getMysqli();
                $baseDir = dirname(__DIR__, 3);
                if (file_exists($baseDir . '/app/Helpers/audit.php')) {
                    require_once $baseDir . '/app/Helpers/audit.php';
                }
                $hostId = function_exists('auditResolveHostId') ? auditResolveHostId($mysqliConn, $userId, $gameId > 0 ? $gameId : null, 0) : 0;
            }

            $sql = "UPDATE ca_payment 
                    SET STATUS = 'N', 
                        IS_REINITIATED = 1, 
                        PREV_REJECTION_REASON = CASE WHEN :prev_reason != '' THEN :prev_reason2 ELSE PREV_REJECTION_REASON END, 
                        MESSAGE = CASE WHEN :details != '' THEN :details2 ELSE MESSAGE END, 
                        REJECTION_REASON = NULL, 
                        REVIEWED_BY = CASE WHEN GAME_ID = 0 AND (REVIEWED_BY IS NOT NULL AND REVIEWED_BY > 0) THEN REVIEWED_BY WHEN :host_id > 0 THEN :host_id2 ELSE REVIEWED_BY END, 
                        REVIEWED_AT = NULL
                    WHERE ID = :payment_id AND USER_ID = :user_id AND STATUS = 'R'";
            $uStmt = $this->db->prepare($sql);
            $uStmt->execute([
                ':prev_reason' => $prevReason,
                ':prev_reason2' => $prevReason,
                ':details' => $details,
                ':details2' => $details,
                ':host_id' => $hostId,
                ':host_id2' => $hostId,
                ':payment_id' => $paymentId,
                ':user_id' => $userId,
            ]);

            try {
                $mysqliConn = Database::getMysqli();
                if (function_exists('logPlayerActivity')) {
                    $logDesc = "Re-initiated payment ID " . $paymentId;
                    if ($details !== '') {
                        $logDesc .= " (Player Reason: " . $details . ")";
                    }
                    if ($prevReason !== '') {
                        $logDesc .= " (Previous Rejection Reason: " . $prevReason . ")";
                    }
                    logPlayerActivity($mysqliConn, $userId, 'REINITIATE_PAYMENT', $logDesc, $hostId, $gameId);
                }
            } catch (\Throwable $auditErr) {
                error_log('[PlayerLedgerModel::reinitiatePlayerPayment Audit Error] ' . $auditErr->getMessage());
            }

            return [
                'status' => 'success',
                'message' => 'Payment re-initiated successfully.',
                'payment_id' => $paymentId,
                'host_id' => $hostId
            ];
        } catch (\Throwable $e) {
            error_log('[PlayerLedgerModel::reinitiatePlayerPayment Error] ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to re-initiate payment: ' . $e->getMessage()
            ];
        }
    }
}
