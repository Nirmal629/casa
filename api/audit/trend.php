<?php
/**
 * api/audit/trend.php — time-series data for the charts.
 *   - daily: active users / game views / joins per day across ?range=
 *   - time_of_day: activity split into Morning / Afternoon / Evening / Night
 * Host-scoped.
 */

require __DIR__ . '/_bootstrap.php';

$H = $HOST_ID;
$r = audit_range();
$from = $r['from'];
$to   = $r['to'];

try {
    // "all time" -> start at the host's first real activity, but never chart
    // more than ~18 months of daily/weekly points
    if ($r['key'] === 'all') {
        $minRow = $pdo->query("
            SELECT LEAST(
                COALESCE((SELECT MIN(CREATED_AT) FROM ca_player_logs WHERE HOST_ID = $H), NOW()),
                COALESCE((SELECT MIN(CREATED_AT) FROM ca_gamejoin  WHERE HOST_ID = $H), NOW())
            ) AS mn
        ")->fetch();
        $earliest = $minRow && $minRow['mn'] ? $minRow['mn'] : $from;
        $floor = date('Y-m-d 00:00:00', strtotime('-18 months'));
        $from = max($earliest, $floor);
    }

    /* ---- per-day series: activity log + real join records merged ------ */
    $logQ = $pdo->prepare("
        SELECT DATE(CREATED_AT) AS d,
               COUNT(DISTINCT USER_ID)                       AS active_users,
               SUM(ACTIVITY_TYPE = 'GAME_VIEWED')            AS views,
               SUM(ACTIVITY_TYPE = 'GAME_LIST_VIEWED')       AS list_views,
               SUM(ACTIVITY_TYPE = 'LEAVE_GAME')             AS leaves
        FROM ca_player_logs
        WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f AND :t
        GROUP BY DATE(CREATED_AT)
    ");
    $logQ->execute([':f' => $from, ':t' => $to]);
    $log = [];
    foreach ($logQ->fetchAll() as $row) { $log[$row['d']] = $row; }

    $joinQ = $pdo->prepare("
        SELECT DATE(CREATED_AT) AS d,
               COUNT(*)                 AS joins,
               COUNT(DISTINCT USER_ID)  AS join_users
        FROM ca_gamejoin
        WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f AND :t
        GROUP BY DATE(CREATED_AT)
    ");
    $joinQ->execute([':f' => $from, ':t' => $to]);
    $jn = [];
    foreach ($joinQ->fetchAll() as $row) { $jn[$row['d']] = $row; }

    // fill every day in the range so the chart has no gaps
    $days = [];
    $cur  = new DateTime(substr($from, 0, 10));
    $end  = new DateTime(substr($to, 0, 10));
    $guard = 0;
    while ($cur <= $end && $guard++ < 800) {
        $k = $cur->format('Y-m-d');
        $l = $log[$k] ?? [];
        $j = $jn[$k] ?? [];
        $days[] = [
            'date'         => $k,
            'label'        => $cur->format('M j'),
            'active_users' => max((int) ($l['active_users'] ?? 0), (int) ($j['join_users'] ?? 0)),
            'views'        => (int) ($l['views'] ?? 0),
            'list_views'   => (int) ($l['list_views'] ?? 0),
            'joins'        => (int) ($j['joins'] ?? 0),
            'leaves'       => (int) ($l['leaves'] ?? 0),
        ];
        $cur->modify('+1 day');
    }
    // "all"/very-long ranges: collapse to weekly buckets so the axis stays readable
    if (count($days) > 92) {
        $weekly = [];
        foreach ($days as $i => $d) {
            $wk = intdiv($i, 7);
            if (!isset($weekly[$wk])) {
                $weekly[$wk] = ['date' => $d['date'], 'label' => $d['label'], 'active_users' => 0, 'views' => 0, 'list_views' => 0, 'joins' => 0, 'leaves' => 0];
            }
            foreach (['active_users', 'views', 'list_views', 'joins', 'leaves'] as $m) {
                $weekly[$wk][$m] += $d[$m];
            }
        }
        $days = array_values($weekly);
    }

    /* ---- time-of-day buckets (server local time) -------------------- */
    $tq = $pdo->prepare("
        SELECT h, SUM(c) AS c FROM (
            SELECT HOUR(CREATED_AT) AS h, COUNT(*) AS c
              FROM ca_player_logs
             WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f1 AND :t1
             GROUP BY h
            UNION ALL
            SELECT HOUR(CREATED_AT) AS h, COUNT(*) AS c
              FROM ca_gamejoin
             WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f2 AND :t2
             GROUP BY h
        ) x GROUP BY h
    ");
    $tq->execute([':f1' => $from, ':t1' => $to, ':f2' => $from, ':t2' => $to]);
    $hourRows = $tq->fetchAll();
    $buckets = ['Morning' => 0, 'Afternoon' => 0, 'Evening' => 0, 'Night' => 0];
    foreach ($hourRows as $hr) {
        $h = (int) $hr['h'];
        $c = (int) $hr['c'];
        if ($h >= 5 && $h <= 11)       $buckets['Morning']   += $c;
        elseif ($h >= 12 && $h <= 16)  $buckets['Afternoon'] += $c;
        elseif ($h >= 17 && $h <= 21)  $buckets['Evening']   += $c;
        else                           $buckets['Night']     += $c;
    }

    json_out([
        'range'  => ['key' => $r['key'], 'from' => substr($from, 0, 10), 'to' => substr($to, 0, 10)],
        'daily'  => $days,
        'time_of_day' => array_map(fn($k) => ['label' => $k, 'value' => $buckets[$k]], array_keys($buckets)),
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load trend.', 'detail' => $e->getMessage()], 500);
}
