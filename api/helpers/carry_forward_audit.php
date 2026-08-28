<?php
/**
 * NEW FEATURE: Carry Forward Audit Trail
 *
 * Write-only, independent integration used by api/carry_forward.php.
 *
 *   - host_player_carry_forward            → current state
 *                                            (one row per host + player +
 *                                             carry_month + carry_year — upsert,
 *                                             never a duplicate)
 *   - ca_host_player_carry_forward_history → append-only audit history
 *                                            (every Carry operation = new row,
 *                                             rows are NEVER updated)
 *
 * This file does NOT modify any existing business logic, calculations,
 * APIs, or existing database tables. If the audit tables are unavailable
 * the function simply returns false and the existing carry flow continues
 * exactly as before.
 */

if (!function_exists('recordCarryForwardAudit')) {

    /**
     * Record a Carry Forward operation into the audit trail.
     *
     * @param mysqli $conn
     * @param int    $host_id     Host user performing the carry (created_by)
     * @param int    $player_id   Player whose balance is being carried
     * @param int    $sourceMonth Month being carried FROM (source)
     * @param int    $sourceYear  Year being carried FROM (source)
     * @param int    $destMonth   Month receiving the balance (carry_month)
     * @param int    $destYear    Year receiving the balance (carry_year)
     * @param float  $carryAmount Signed balance: > 0 DUE, < 0 ADVANCE, 0 reset
     * @param string $monthName   Source month name (e.g. "September") for remarks
     *
     * @return bool true when both tables were written, false on any failure
     */
    function recordCarryForwardAudit($conn, $host_id, $player_id, $sourceMonth, $sourceYear, $destMonth, $destYear, $carryAmount, $monthName)
    {
        $host_id     = (int) $host_id;
        $player_id   = (int) $player_id;
        $sourceMonth = (int) $sourceMonth;
        $sourceYear  = (int) $sourceYear;
        $destMonth   = (int) $destMonth;
        $destYear    = (int) $destYear;
        $carryAmount = (float) $carryAmount;

        // Balance type: DUE (positive balance) / ADVANCE (player credit) / DUE for $0 reset
        $balanceType    = ($carryAmount > 0) ? 'DUE' : (($carryAmount < 0) ? 'ADVANCE' : 'DUE');
        $openingBalance = abs($carryAmount);

        // ------------------------------------------------------------------
        // is_locked mirrors the existing month-locking rule for the carry_month:
        //   "a month is LOCKED as soon as its closing balance has been carried
        //    forward → a Carry Forward record exists in the NEXT month."
        // (Same queries as api/render_player_pay_html.php — read-only.)
        // ------------------------------------------------------------------
        $lockNextMonth = ($destMonth % 12) + 1;
        $lockNextYear  = ($destMonth == 12) ? ($destYear + 1) : $destYear;

        $lockRes = mysqli_query($conn, "SELECT 1 FROM host_player_carry_forward
                WHERE host_id = $host_id AND player_id = $player_id
                  AND carry_month = $lockNextMonth AND carry_year = $lockNextYear
                LIMIT 1");
        $isLocked = ($lockRes && mysqli_num_rows($lockRes) > 0) ? 1 : 0;

        // ------------------------------------------------------------------
        // Current state: one row per host + player + carry_month + carry_year.
        // Existing row  → UPDATE (no duplicate)
        // No existing   → INSERT
        // ------------------------------------------------------------------
        $curQuery = mysqli_query($conn, "SELECT id FROM host_player_carry_forward
                WHERE host_id = $host_id AND player_id = $player_id
                  AND carry_month = $destMonth AND carry_year = $destYear
                LIMIT 1");

        if ($curQuery && ($curRow = mysqli_fetch_assoc($curQuery))) {
            $action          = 'UPDATE';
            $carryForwardId  = (int) $curRow['id'];
            $ok = mysqli_query($conn, "UPDATE host_player_carry_forward SET
                    opening_balance = $openingBalance,
                    balance_type    = '$balanceType',
                    source_month    = $sourceMonth,
                    source_year     = $sourceYear,
                    is_locked       = $isLocked,
                    updated_at      = NOW()
                WHERE id = $carryForwardId");
            if (!$ok) return false;
        } else {
            $action = 'CREATE';
            $ok = mysqli_query($conn, "INSERT INTO host_player_carry_forward
                    (host_id, player_id, carry_month, carry_year, opening_balance, balance_type,
                     source_month, source_year, is_locked, created_at, updated_at)
                VALUES
                    ($host_id, $player_id, $destMonth, $destYear, $openingBalance, '$balanceType',
                     $sourceMonth, $sourceYear, $isLocked, NOW(), NOW())");
            if (!$ok) return false;
            $carryForwardId = (int) mysqli_insert_id($conn);
        }

        // ------------------------------------------------------------------
        // History: APPEND-ONLY — every Carry operation creates a NEW row.
        // Existing history rows are never updated or deleted.
        // ------------------------------------------------------------------
        $remarks = ($carryAmount == 0)
            ? 'Carry balance reset to $0.00'
            : 'Carried from ' . $monthName . ' ' . $sourceYear
                . ' to ' . $destMonth . '/' . $destYear;
        $remarksEsc = mysqli_real_escape_string($conn, $remarks);

        $ok = mysqli_query($conn, "INSERT INTO ca_host_player_carry_forward_history
                (carry_forward_id, host_id, player_id, source_month, source_year,
                 destination_month, destination_year, opening_balance, balance_type,
                 action, remarks, created_by, created_at)
            VALUES
                ($carryForwardId, $host_id, $player_id, $sourceMonth, $sourceYear,
                 $destMonth, $destYear, $openingBalance, '$balanceType',
                 '$action', '$remarksEsc', $host_id, NOW())");

        return (bool) $ok;
    }
}
