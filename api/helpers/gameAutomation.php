<?php

function runGameAutomation(mysqli $conn, int $gameId): void
{
    /* ======================================================
       FETCH EVENT (ONLY BADMINTON GAME)
    ====================================================== */
    $eventRes = mysqli_query($conn, "
        SELECT EVENT_CATEGORY, EVENT_VENUE, EVENT_TIME, TO_TIME, HOST_ID, TOTAL_EVENT_COST
        FROM ca_events
        WHERE ID = '$gameId'
        LIMIT 1
    ");

    if (!$eventRes || mysqli_num_rows($eventRes) === 0) {
        return;
    }

    $event = mysqli_fetch_assoc($eventRes);

    if (trim($event['EVENT_CATEGORY']) !== 'Badminton Game') {
        return;
    }
    /* ======================================================
       FETCH JOINED PLAYERS
    ====================================================== */
    $res = mysqli_query($conn, "
        SELECT ID
        FROM ca_gamejoin
        WHERE GAME_ID = '$gameId'
          AND STATUS = 'Y'
        ORDER BY CREATED_AT ASC
    ");

    $players = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $players[] = $row['ID'];
    }

    $joined = count($players);

    /* ======================================================
       COURT & SLOT LOGIC
    ====================================================== */
    $courts_confirmed = floor($joined / 4);
    $block_position   = $joined % 6;
    $spots_available  = ($block_position == 0) ? 6 : 6 - $block_position;

    /* ======================================================
       AUTO CONFIRM LOGIC
    ====================================================== */
    $confirm_ids = [];

    if ($block_position == 4) {
        $start = ($courts_confirmed - 1) * 4;
        $confirm_ids = array_slice($players, $start, 4);
    }
    elseif ($block_position == 5 || $block_position == 0) {
        $confirm_ids = [ end($players) ];
    }

    if (!empty($confirm_ids)) {
        $ids = implode(',', $confirm_ids);
        mysqli_query($conn, "
            UPDATE ca_gamejoin
            SET CONFIRMED = 'Y'
            WHERE ID IN ($ids)
              AND CONFIRMED = 'N'
        ");
    }

    /* ======================================================
       JOIN MESSAGE
    ====================================================== */
    $message = ($courts_confirmed == 0)
        ? "0 Court Confirmed Yet, {$spots_available} Spots Available"
        : "{$courts_confirmed} Court Confirmed, {$spots_available} Spots Available";

    mysqli_query($conn, "
        UPDATE ca_events
        SET JOIN_MESSAGE = '".mysqli_real_escape_string($conn, $message)."'
        WHERE ID = '$gameId'
    ");

    /* ======================================================
       CONFIRMED PLAYERS
    ====================================================== */
    $confirmedRes = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM ca_gamejoin
        WHERE GAME_ID = '$gameId'
          AND STATUS = 'Y'
          AND CONFIRMED = 'Y'
    ");

    $confirmed = (int)mysqli_fetch_assoc($confirmedRes)['total'];

    /* ======================================================
       CASE 1: ≤ 4 PLAYERS → NO RECALC
    ====================================================== */
    if ($confirmed <= 4) {

        if ($confirmed === 0) {
            mysqli_query($conn, "
                UPDATE ca_events
                SET TOTAL_PLAYER_COST = 0,
                    PROFIT_LOSS = 0
                WHERE ID = '$gameId'
            ");
            return;
        }

        $sumRes = mysqli_query($conn, "
            SELECT SUM(PRICE) AS total
            FROM ca_gamejoin
            WHERE GAME_ID = '$gameId'
              AND CONFIRMED = 'Y'
        ");

        $total_player_cost = (float)mysqli_fetch_assoc($sumRes)['total'];
        $profit_loss = ceil($total_player_cost - (float)$event['TOTAL_EVENT_COST']);

        mysqli_query($conn, "
            UPDATE ca_events
            SET TOTAL_PLAYER_COST = '$total_player_cost',
                PROFIT_LOSS = '$profit_loss'
            WHERE ID = '$gameId'
        ");

        return;
    }

    /* ======================================================
       CASE 2: > 4 PLAYERS → FULL RECALC
    ====================================================== */

    /* Venue Cost */
    $venueRes = mysqli_query($conn, "
        SELECT COST
        FROM ca_venue
        WHERE NAME = '".mysqli_real_escape_string($conn, $event['EVENT_VENUE'])."'
          AND HOST_ID = '".$event['HOST_ID']."'
        LIMIT 1
    ");
    $court_rate = (float)mysqli_fetch_assoc($venueRes)['COST'];

    /* Birdie Cost */
    $birdieRes = mysqli_query($conn, "
        SELECT BRIDIE
        FROM ca_bride
        WHERE HOST_ID = '".$event['HOST_ID']."'
        LIMIT 1
    ");
    $birdie_price = (float)mysqli_fetch_assoc($birdieRes)['BRIDIE'];

    /* Birdies Used */
    $birdies_used = ($event['EVENT_CATEGORY'] === 'Epic Badminton')
        ? $confirmed * 2
        : $confirmed;

    /* Time */
    $hours = max(
        1,
        (strtotime($event['TO_TIME']) - strtotime($event['EVENT_TIME'])) / 3600
    );

    /* Costs */
    $facility_cost    = $court_rate * $hours;
    $accessories_cost = $birdie_price * $birdies_used;
    $club_cost        = $confirmed;

    $total_event_cost = ceil(
        $facility_cost + $accessories_cost + $club_cost
    );

    $event_cost = ceil($total_event_cost / $confirmed);

    /* Update player prices */
    mysqli_query($conn, "
        UPDATE ca_gamejoin
        SET PRICE = '$event_cost'
        WHERE GAME_ID = '$gameId'
          AND CONFIRMED = 'Y'
    ");

    /* Final totals */
    $sumRes = mysqli_query($conn, "
        SELECT SUM(PRICE) AS total
        FROM ca_gamejoin
        WHERE GAME_ID = '$gameId'
          AND CONFIRMED = 'Y'
    ");
    $total_player_cost = (float)mysqli_fetch_assoc($sumRes)['total'];

    $profit_loss = ceil($total_player_cost - $total_event_cost);

    mysqli_query($conn, "
        UPDATE ca_events SET
            EVENT_COST = '$event_cost',
            FACILITY_COST = '$facility_cost',
            ACCESSORIES_COST = '$accessories_cost',
            TOTAL_EVENT_COST = '$total_event_cost',
            TOTAL_PLAYER_COST = '$total_player_cost',
            CLUB_COST = '$club_cost',
            BIRDIE_USED = '$birdies_used',
            PROFIT_LOSS = '$profit_loss'
        WHERE ID = '$gameId'
    ");
}
