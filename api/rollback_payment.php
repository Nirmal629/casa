<?php
session_start();
include('dbConnection.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $game_id = mysqli_real_escape_string($conn, $_POST['game_id']);
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $year = (int) ($_POST['year'] ?? 0);
    $month = (int) ($_POST['month'] ?? 0);
    if ($year <= 0) {
        $year = (int) date('Y');
    }
    if ($month <= 0) {
        $month = (int) date('n');
    }

    $isPlayerSide = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Player');
    if ($isPlayerSide) {
        require_once __DIR__ . '/render_player_pay_html.php';
        echo renderPlayerPayHtml($conn, $user_id, $year, $month, true);
        mysqli_close($conn);
        exit;
    }

    // Month locking: rollback is blocked for a month that has already been
    // carried forward (a Carry Forward record exists for the next month).
    $uid = (int) $user_id;
    $host_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $nMonth = ($month % 12) + 1;
    $nYear = ($month == 12) ? ($year + 1) : $year;
    $isOverrideActive = (isset($_SESSION['ledger_override'][$user_id][$year][$month]) && $_SESSION['ledger_override'][$user_id][$year][$month] === true) || (isset($_POST['override']) && (int)$_POST['override'] === 1);

    if (!$isOverrideActive) {
        $isLocked = false;
        try {
            $lockRes = mysqli_query($conn, "SELECT 1 FROM host_player_carry_forward
                    WHERE host_id = $host_id AND player_id = $uid
                      AND carry_month = $nMonth AND carry_year = $nYear
                    LIMIT 1");
            $isLocked = ($lockRes && mysqli_num_rows($lockRes) > 0);
        } catch (mysqli_sql_exception $e) {
            $isLocked = false;
        }
        if ($isLocked) {
            // Month is locked — re-render the locked UI, do NOT rollback
            require_once __DIR__ . '/render_player_pay_html.php';
            echo renderPlayerPayHtml($conn, $user_id, $year, $month);
            mysqli_close($conn);
            exit;
        }
    }

    // Only the CURRENT month allows rollback. Previous months are read-only
    // (carry forward only) — enforce server-side as well.
    $nowYear  = (int) date('Y');
    $nowMonth = (int) date('n');
    if (!$isOverrideActive) {
        if ($year != $nowYear || $month != $nowMonth) {
            require_once __DIR__ . '/render_player_pay_html.php';
            echo renderPlayerPayHtml($conn, $user_id, $year, $month);
            mysqli_close($conn);
            exit;
        }
    }

    // Delete the payment entry
    $query = "DELETE FROM ca_payment WHERE GAME_ID = '$game_id' AND USER_ID = '$user_id' AND STATUS = 'Y'";

    if (mysqli_query($conn, $query)) {
        // Shared renderer — grouped Event Cost / Payments / Balance layout
        require_once __DIR__ . '/render_player_pay_html.php';
        echo renderPlayerPayHtml($conn, $user_id, $year, $month);
    } else {
        echo "Failed to delete payment: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>