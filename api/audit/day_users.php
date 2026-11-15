<?php
/**
 * api/audit/day_users.php — users active on a single day, for the
 * "click a point on the activity-trend chart" drill-down.
 *
 * ?date = YYYY-MM-DD          (required)  — or ?from= ?to= for a bucket span
 * ?page= ?per_page= (5..100, default 20)
 *
 * "Active" = had an activity-log row for this host OR joined one of the
 * host's games on that day.
 */

require __DIR__ . '/_bootstrap.php';

$H = $HOST_ID;

$date = trim((string) ($_GET['date'] ?? ''));
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $from = $date . ' 00:00:00';
    $to   = $date . ' 23:59:59';
    $label = $date;
} else {
    $from = trim((string) ($_GET['from'] ?? ''));
    $to   = trim((string) ($_GET['to'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $to)) {
        json_out(['error' => 'bad_request', 'message' => 'date (YYYY-MM-DD) or from/to is required.'], 400);
    }
    $from = substr($from, 0, 10) . ' 00:00:00';
    $to   = substr($to, 0, 10) . ' 23:59:59';
    $label = substr($from, 0, 10) . ' – ' . substr($to, 0, 10);
}

$page = audit_int($_GET['page'] ?? 1, 1, 1, 100000);
$per  = audit_int($_GET['per_page'] ?? 20, 20, 5, 100);

try {
    $idStmt = $pdo->prepare("
        SELECT uid FROM (
            SELECT DISTINCT USER_ID AS uid FROM ca_player_logs
              WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f1 AND :t1
            UNION
            SELECT DISTINCT USER_ID FROM ca_gamejoin
              WHERE HOST_ID = $H AND CREATED_AT BETWEEN :f2 AND :t2
        ) x
    ");
    $idStmt->execute([':f1' => $from, ':t1' => $to, ':f2' => $from, ':t2' => $to]);
    $ids = array_map('intval', array_column($idStmt->fetchAll(), 'uid'));
    $total = count($ids);
    $pages = max(1, (int) ceil($total / $per));
    $page  = min($page, $pages);

    $data = [];
    if ($ids) {
        $slice = array_slice($ids, ($page - 1) * $per, $per);
        $in = implode(',', $slice);
        $rows = $pdo->query("
            SELECT u.ID, u.NAME, u.EMAIL, u.PROFILE_IMAGE,
                (SELECT COUNT(*) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.HOST_ID = $H AND l.CREATED_AT BETWEEN '$from' AND '$to') AS events,
                (SELECT SUM(l.ACTIVITY_TYPE = 'GAME_VIEWED') FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.HOST_ID = $H AND l.CREATED_AT BETWEEN '$from' AND '$to') AS views,
                (SELECT COUNT(*) FROM ca_gamejoin cg
                   WHERE cg.USER_ID = u.ID AND cg.HOST_ID = $H AND cg.CREATED_AT BETWEEN '$from' AND '$to') AS joins,
                (SELECT MIN(l.CREATED_AT) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.HOST_ID = $H AND l.CREATED_AT BETWEEN '$from' AND '$to') AS first_at,
                (SELECT MAX(l.CREATED_AT) FROM ca_player_logs l
                   WHERE l.USER_ID = u.ID AND l.HOST_ID = $H AND l.CREATED_AT BETWEEN '$from' AND '$to') AS last_at
            FROM ca_users u WHERE u.ID IN ($in)
        ")->fetchAll();
        $byId = [];
        foreach ($rows as $x) { $byId[(int) $x['ID']] = $x; }
        foreach ($slice as $uid) {
            $x = $byId[$uid] ?? null;
            if (!$x) continue;
            $data[] = [
                'id'       => (int) $x['ID'],
                'name'     => $x['NAME'] ?: ('User #' . $x['ID']),
                'email'    => $x['EMAIL'],
                'avatar'  => !empty($x['PROFILE_IMAGE']) ? 'profile_img/' . $x['PROFILE_IMAGE'] : null,
                'events'   => (int) $x['events'],
                'views'    => (int) $x['views'],
                'joins'    => (int) $x['joins'],
                'first_at' => $x['first_at'],
                'last_at'  => $x['last_at'],
            ];
        }
        usort($data, fn($a, $b) => ($b['events'] + $b['joins']) <=> ($a['events'] + $a['joins']));
    }

    json_out([
        'day'   => $label,
        'total' => $total,
        'data'  => $data,
        'pagination' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => $pages],
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load active users.', 'detail' => $e->getMessage()], 500);
}
