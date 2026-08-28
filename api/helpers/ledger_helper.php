<?php
if (!function_exists('calculateLedgerSummary')) {
    function calculateLedgerSummary($conn, $user_id, $host_id, $year = 0, $month = 0) {
        $user_id = (int)$user_id;
        $host_id = (int)$host_id;
        $year = (int)$year;
        $month = (int)$month;

        $useDateFilter = ($year > 0 && $month > 0);

        // 1. Games (Completed, Status Y)
        $gamesQuery = "
            SELECT cg.PRICE
            FROM ca_gamejoin cg
            INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
            WHERE cg.USER_ID = '$user_id'
              AND cg.HOST_ID = '$host_id'
              AND cg.STATUS = 'Y'
              AND ce.STATUS = 'Completed'
              AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
        ";
        if ($useDateFilter) {
            $gamesQuery .= " AND MONTH(ce.EVENT_DATE) = '$month' AND YEAR(ce.EVENT_DATE) = '$year'";
        }
        $gamesResult = mysqli_query($conn, $gamesQuery);
        $gameCount = 0;
        $totalGameAmt = 0.0;
        if ($gamesResult) {
            $gameCount = mysqli_num_rows($gamesResult);
            while ($gRow = mysqli_fetch_assoc($gamesResult)) {
                $totalGameAmt += (float)$gRow['PRICE'];
            }
        }

        // 2. Expenses (Y)
        $expenseQuery = "
            SELECT AMOUNT FROM ca_expense
            WHERE USER_ID = '$user_id'
              AND HOST_ID = '$host_id'
              AND STATUS = 'Y'
              AND NOT (TYPE = 'PreviousDue' OR TYPE LIKE 'Carry Forward from %')
        ";
        if ($useDateFilter) {
            $expenseQuery .= " AND MONTH(EXPENSE_DATE) = '$month' AND YEAR(EXPENSE_DATE) = '$year'";
        }
        $expenseResult = mysqli_query($conn, $expenseQuery);
        $totalExpAmt = 0.0;
        if ($expenseResult) {
            while ($eRow = mysqli_fetch_assoc($expenseResult)) {
                $totalExpAmt += (float)$eRow['AMOUNT'];
            }
        }

        // 3. Opening Balance
        $openingDue = 0.0;
        $openingAdvance = 0.0;

        if ($useDateFilter) {
            // Filtered month: get opening balance of this specific month
            try {
                $opQuery = mysqli_query($conn, "SELECT opening_balance, balance_type FROM host_player_carry_forward WHERE host_id = $host_id AND player_id = $user_id AND carry_month = $month AND carry_year = $year LIMIT 1");
                if ($opQuery && $opRow = mysqli_fetch_assoc($opQuery)) {
                    $opAmt = (float)$opRow['opening_balance'];
                    if ($opRow['balance_type'] === 'DUE') {
                        $openingDue = $opAmt;
                    } else {
                        $openingAdvance = $opAmt;
                    }
                }
            } catch (mysqli_sql_exception $e) {
                $openingDue = 0.0;
                $openingAdvance = 0.0;
            }
        } else {
            // All-time: find earliest transaction's month/year, and get the opening balance of that earliest month.
            $earliestYear = 9999;
            $earliestMonth = 12;

            // Games dates
            $dateQuery1 = mysqli_query($conn, "
                SELECT MIN(ce.EVENT_DATE) as min_date
                FROM ca_gamejoin cg
                INNER JOIN ca_events ce ON ce.ID = cg.GAME_ID
                WHERE cg.USER_ID = '$user_id' AND cg.HOST_ID = '$host_id' AND cg.STATUS = 'Y' AND ce.STATUS = 'Completed'
                  AND NOT (ce.EVENT_CATEGORY = 'PreviousDue' OR ce.EVENT_CATEGORY LIKE 'Carry Forward from %')
            ");
            if ($dateQuery1 && $row = mysqli_fetch_assoc($dateQuery1)) {
                if ($row['min_date']) {
                    $ty = (int)date('Y', strtotime($row['min_date']));
                    $tm = (int)date('n', strtotime($row['min_date']));
                    if ($ty < $earliestYear || ($ty === $earliestYear && $tm < $earliestMonth)) {
                        $earliestYear = $ty;
                        $earliestMonth = $tm;
                    }
                }
            }

            // Expenses dates
            $dateQuery2 = mysqli_query($conn, "
                SELECT MIN(EXPENSE_DATE) as min_date
                FROM ca_expense
                WHERE USER_ID = '$user_id' AND HOST_ID = '$host_id' AND STATUS = 'Y'
                  AND NOT (TYPE = 'PreviousDue' OR TYPE LIKE 'Carry Forward from %')
            ");
            if ($dateQuery2 && $row = mysqli_fetch_assoc($dateQuery2)) {
                if ($row['min_date']) {
                    $ty = (int)date('Y', strtotime($row['min_date']));
                    $tm = (int)date('n', strtotime($row['min_date']));
                    if ($ty < $earliestYear || ($ty === $earliestYear && $tm < $earliestMonth)) {
                        $earliestYear = $ty;
                        $earliestMonth = $tm;
                    }
                }
            }

            // Payments dates
            $dateQuery3 = mysqli_query($conn, "
                SELECT MIN(p.PAYMENT_DATE) as min_date
                FROM ca_payment p
                LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
                LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
                WHERE p.USER_ID = '$user_id' AND p.STATUS = 'Y'
                  AND (
                      (p.GAME_ID > 0 AND e.HOST_ID = '$host_id')
                      OR (p.GAME_ID < 0 AND ex.HOST_ID = '$host_id')
                      OR (p.GAME_ID = 0 AND p.REVIEWED_BY = '$host_id')
                  )
            ");
            if ($dateQuery3 && $row = mysqli_fetch_assoc($dateQuery3)) {
                if ($row['min_date']) {
                    $ty = (int)date('Y', strtotime($row['min_date']));
                    $tm = (int)date('n', strtotime($row['min_date']));
                    if ($ty < $earliestYear || ($ty === $earliestYear && $tm < $earliestMonth)) {
                        $earliestYear = $ty;
                        $earliestMonth = $tm;
                    }
                }
            }

            if ($earliestYear !== 9999) {
                try {
                    $opQuery = mysqli_query($conn, "SELECT opening_balance, balance_type FROM host_player_carry_forward WHERE host_id = $host_id AND player_id = $user_id AND carry_month = $earliestMonth AND carry_year = $earliestYear LIMIT 1");
                    if ($opQuery && $opRow = mysqli_fetch_assoc($opQuery)) {
                        $opAmt = (float)$opRow['opening_balance'];
                        if ($opRow['balance_type'] === 'DUE') {
                            $openingDue = $opAmt;
                        } else {
                            $openingAdvance = $opAmt;
                        }
                    }
                } catch (mysqli_sql_exception $e) {
                    $openingDue = 0.0;
                    $openingAdvance = 0.0;
                }
            }
        }

        // 4. Payments (STATUS = 'Y' only)
        $paymentsQuery = "
            SELECT p.AMOUNT, p.GAME_ID, p.PAYMENT_TYPE
            FROM ca_payment p
            LEFT JOIN ca_events e ON p.GAME_ID = e.ID AND p.GAME_ID > 0
            LEFT JOIN ca_expense ex ON p.GAME_ID = -ex.ID AND p.GAME_ID < 0
            WHERE p.USER_ID = '$user_id'
              AND p.STATUS = 'Y'
              AND (
                  (p.GAME_ID > 0 AND e.HOST_ID = '$host_id')
                  OR (p.GAME_ID < 0 AND ex.HOST_ID = '$host_id')
                  OR (p.GAME_ID = 0 AND p.REVIEWED_BY = '$host_id')
              )
        ";
        if ($useDateFilter) {
            $paymentsQuery .= "
              AND MONTH(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = '$month'
              AND YEAR(COALESCE(e.EVENT_DATE, ex.EXPENSE_DATE, p.PAYMENT_DATE)) = '$year'
            ";
        }
        $paymentsResult = mysqli_query($conn, $paymentsQuery);
        $totalPaid = 0.0;
        if ($paymentsResult) {
            while ($pRow = mysqli_fetch_assoc($paymentsResult)) {
                $gameId = (int)$pRow['GAME_ID'];
                $pType = $pRow['PAYMENT_TYPE'];

                $isCarryPay = ($gameId === 0 && ($pType === 'Carry' || (is_string($pType) && strpos($pType, 'Carry Forward from ') === 0)));
                if ($isCarryPay) {
                    continue;
                }

                $totalPaid += (float)$pRow['AMOUNT'];
            }
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
}
