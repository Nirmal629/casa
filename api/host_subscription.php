<?php
/**
 * api/host_subscription.php
 * ------------------------------------------------------------------
 * Host tool: apply a monthly subscription charge to players who played
 * 2+ completed games with this host in a given month.
 *
 * The charge is written into ca_expense (TYPE = 'Subscription') so the
 * EXISTING ledger / Payment tab picks it up automatically — no existing
 * ledger, carry-forward or payment code is touched.
 *
 *   ?action=list      &year=&month=
 *   ?action=apply     POST year, month, items=[{player_id, amount}, ...]
 *   ?action=unlock    POST id  (or player_id, year, month)
 *   ?action=rollback  POST id  (or player_id, year, month)   [requires unlocked]
 *
 * Host-only. Every query is scoped to the logged-in host's user id.
 * ------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Toronto');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function hs_out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---- auth: any host account (Host / Trainer), never a Player ---------- */
$uid  = $_SESSION['user_id'] ?? null;
$role = isset($_SESSION['usertype']) ? trim($_SESSION['usertype']) : '';
if (!$uid) {
    hs_out(['error' => 'unauthenticated', 'message' => 'Please sign in.'], 401);
}
if (strcasecmp($role, 'Player') === 0 || $role === '') {
    hs_out(['error' => 'forbidden', 'message' => 'Host accounts only.'], 403);
}
$HOST_ID = (int) $uid;

require_once __DIR__ . '/../config/env.php';
try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', 'localhost') . ';dbname=' . env('DB_NAME', 'casa_test') . ';charset=' . env('DB_CHARSET', 'utf8mb4'),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (Throwable $e) {
    hs_out(['error' => 'db', 'message' => 'Database unavailable.'], 500);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$H = $HOST_ID;

/** validated (year, month) from the request, defaulting to the current month. */
function hs_period(): array
{
    $y = (int) ($_REQUEST['year'] ?? date('Y'));
    $m = (int) ($_REQUEST['month'] ?? date('n'));
    if ($y < 2020 || $y > 2100) $y = (int) date('Y');
    if ($m < 1 || $m > 12)      $m = (int) date('n');
    return [$y, $m];
}

/** completed-games count per player for this host in (y, m); ['player_id' => ['games'=>, 'amount'=>]] */
function hs_eligibility(PDO $pdo, int $host, int $y, int $m): array
{
    $st = $pdo->prepare("
        SELECT cg.USER_ID AS pid, COUNT(*) AS games, ROUND(COALESCE(SUM(cg.PRICE),0),2) AS amount
        FROM ca_gamejoin cg
        JOIN ca_events e ON e.ID = cg.GAME_ID
        WHERE cg.HOST_ID = :h AND cg.STATUS = 'Y' AND e.STATUS = 'Completed'
          AND MONTH(e.EVENT_DATE) = :m AND YEAR(e.EVENT_DATE) = :y
          AND NOT (e.EVENT_CATEGORY = 'PreviousDue' OR e.EVENT_CATEGORY LIKE 'Carry Forward from %')
        GROUP BY cg.USER_ID
    ");
    $st->execute([':h' => $host, ':y' => $y, ':m' => $m]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[(int) $r['pid']] = ['games' => (int) $r['games'], 'amount' => (float) $r['amount']];
    }
    return $out;
}

try {

/* ============================ LIST ============================ */
if ($action === 'list') {
    [$y, $m] = hs_period();
    $elig = hs_eligibility($pdo, $H, $y, $m);
    $eligible = array_filter($elig, fn($v) => $v['games'] >= 2);

    // existing subscription rows for this period
    $subs = [];
    $sr = $pdo->prepare("SELECT * FROM ca_host_subscription WHERE host_id = :h AND sub_year = :y AND sub_month = :m");
    $sr->execute([':h' => $H, ':y' => $y, ':m' => $m]);
    foreach ($sr->fetchAll() as $s) {
        $subs[(int) $s['player_id']] = $s;
    }

    // player names for whoever is eligible or already has a row
    $ids = array_values(array_unique(array_merge(array_keys($eligible), array_keys($subs))));
    $names = [];
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        foreach ($pdo->query("SELECT ID, NAME, EMAIL, PROFILE_IMAGE, WHATSAPP_NUMBER FROM ca_users WHERE ID IN ($in)") as $u) {
            $names[(int) $u['ID']] = $u;
        }
    }

    $rows = [];
    $appliedCount = 0; $appliedTotal = 0.0;
    foreach ($eligible as $pid => $g) {
        $u = $names[$pid] ?? ['NAME' => 'User #' . $pid, 'EMAIL' => null, 'PROFILE_IMAGE' => null, 'WHATSAPP_NUMBER' => null];
        $sub = $subs[$pid] ?? null;
        $applied = $sub && $sub['status'] === 'APPLIED';
        if ($applied) { $appliedCount++; $appliedTotal += (float) $sub['amount']; }
        $rows[] = [
            'player_id'    => $pid,
            'name'         => $u['NAME'] ?: ('User #' . $pid),
            'email'        => $u['EMAIL'],
            'phone'        => $u['WHATSAPP_NUMBER'],
            'avatar'       => !empty($u['PROFILE_IMAGE']) ? 'profile_img/' . $u['PROFILE_IMAGE'] : null,
            'games'        => $g['games'],
            'games_amount' => round($g['amount'], 2),
            'subscription' => $sub ? [
                'id'        => (int) $sub['id'],
                'amount'    => (float) $sub['amount'],
                'currency'  => $sub['currency'],
                'status'    => $sub['status'],
                'is_locked' => (int) $sub['is_locked'] === 1,
                'applied_at' => $sub['applied_at'],
            ] : null,
        ];
    }
    usort($rows, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    $now = getdate();
    $period = ($y > $now['year'] || ($y === $now['year'] && $m > $now['mon'])) ? 'future'
        : (($y === $now['year'] && $m === $now['mon']) ? 'current' : 'past');

    hs_out([
        'year'  => $y, 'month' => $m, 'period' => $period,
        'summary' => [
            'eligible'       => count($rows),
            'applied'        => $appliedCount,
            'applied_total'  => round($appliedTotal, 2),
        ],
        'players' => $rows,
    ]);
}

/* ---- read POSTed items (JSON body or form arrays) -------------------- */
function hs_items(): array
{
    $raw = file_get_contents('php://input');
    if ($raw && ($j = json_decode($raw, true)) && isset($j['items']) && is_array($j['items'])) {
        return $j['items'];
    }
    $items = [];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $it) {
            $items[] = ['player_id' => $it['player_id'] ?? 0, 'amount' => $it['amount'] ?? 0];
        }
    } elseif (isset($_POST['player_id'])) {
        $items[] = ['player_id' => $_POST['player_id'], 'amount' => $_POST['amount'] ?? 0];
    }
    return $items;
}

/* ============================ APPLY ============================ */
if ($action === 'apply') {
    $y = (int) ($_POST['year'] ?? 0);
    $m = (int) ($_POST['month'] ?? 0);
    $bodyRaw = file_get_contents('php://input');
    if ((!$y || !$m) && $bodyRaw && ($j = json_decode($bodyRaw, true))) {
        $y = $y ?: (int) ($j['year'] ?? 0);
        $m = $m ?: (int) ($j['month'] ?? 0);
        if (isset($j['items'])) $_POST['__json_items'] = $j['items'];
    }
    if ($y < 2020 || $y > 2100 || $m < 1 || $m > 12) {
        hs_out(['error' => 'bad_request', 'message' => 'Invalid month/year.'], 400);
    }
    $items = isset($_POST['__json_items']) && is_array($_POST['__json_items']) ? $_POST['__json_items'] : hs_items();
    if (!$items) {
        hs_out(['error' => 'bad_request', 'message' => 'No players selected.'], 400);
    }

    $elig = hs_eligibility($pdo, $H, $y, $m);
    $expenseDate = sprintf('%04d-%02d-01', $y, $m);
    $results = [];

    $findSub = $pdo->prepare("SELECT id, status FROM ca_host_subscription WHERE host_id = :h AND player_id = :p AND sub_year = :y AND sub_month = :m LIMIT 1");
    $insExp  = $pdo->prepare("INSERT INTO ca_expense (USER_ID, HOST_ID, VENUE, TYPE, AMOUNT, CURRENCY, EXPENSE_DATE, EXPENSE_TIME, STATUS)
                              VALUES (:p, :h, '', 'Subscription', :amt, '$', :ed, CURTIME(), 'Y')");
    $insSub  = $pdo->prepare("INSERT INTO ca_host_subscription (host_id, player_id, sub_year, sub_month, amount, currency, games_count, expense_id, status, is_locked, applied_by)
                              VALUES (:h, :p, :y, :m, :amt, '$', :gc, :eid, 'APPLIED', 1, :by)");
    $reSub   = $pdo->prepare("UPDATE ca_host_subscription SET amount = :amt, games_count = :gc, expense_id = :eid, status = 'APPLIED', is_locked = 1, applied_by = :by, applied_at = NOW() WHERE id = :id");

    foreach ($items as $it) {
        $pid = (int) ($it['player_id'] ?? 0);
        $amt = round((float) ($it['amount'] ?? 0), 2);
        if ($pid <= 0) { continue; }

        if ($amt <= 0 || $amt > 100000) {
            $results[$pid] = ['ok' => false, 'message' => 'Enter a valid amount.'];
            continue;
        }
        if (!isset($elig[$pid]) || $elig[$pid]['games'] < 2) {
            $results[$pid] = ['ok' => false, 'message' => 'Player did not play 2+ games this month.'];
            continue;
        }
        $findSub->execute([':h' => $H, ':p' => $pid, ':y' => $y, ':m' => $m]);
        $existing = $findSub->fetch();
        if ($existing && $existing['status'] === 'APPLIED') {
            $results[$pid] = ['ok' => false, 'message' => 'Already applied — roll back first to change.'];
            continue;
        }

        try {
            $pdo->beginTransaction();
            $insExp->execute([':p' => $pid, ':h' => $H, ':amt' => $amt, ':ed' => $expenseDate]);
            $eid = (int) $pdo->lastInsertId();
            if ($existing) {
                $reSub->execute([':amt' => $amt, ':gc' => $elig[$pid]['games'], ':eid' => $eid, ':by' => $H, ':id' => (int) $existing['id']]);
            } else {
                $insSub->execute([':h' => $H, ':p' => $pid, ':y' => $y, ':m' => $m, ':amt' => $amt, ':gc' => $elig[$pid]['games'], ':eid' => $eid, ':by' => $H]);
            }
            $pdo->commit();
            $results[$pid] = ['ok' => true, 'message' => 'Applied', 'amount' => $amt, 'expense_id' => $eid];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $results[$pid] = ['ok' => false, 'message' => 'Could not apply.'];
        }
    }

    $okCount = count(array_filter($results, fn($r) => $r['ok']));
    hs_out([
        'applied' => $okCount,
        'total'   => count($results),
        'results' => $results,
        'message' => $okCount . ' of ' . count($results) . ' applied.',
    ]);
}

/* ---- locate a subscription row from id or (player_id, year, month) --- */
function hs_locate(PDO $pdo, int $host): ?array
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $st = $pdo->prepare("SELECT * FROM ca_host_subscription WHERE id = :id AND host_id = :h LIMIT 1");
        $st->execute([':id' => $id, ':h' => $host]);
    } else {
        $pid = (int) ($_POST['player_id'] ?? 0);
        $y = (int) ($_POST['year'] ?? 0);
        $m = (int) ($_POST['month'] ?? 0);
        if ($pid <= 0 || !$y || !$m) return null;
        $st = $pdo->prepare("SELECT * FROM ca_host_subscription WHERE host_id = :h AND player_id = :p AND sub_year = :y AND sub_month = :m LIMIT 1");
        $st->execute([':h' => $host, ':p' => $pid, ':y' => $y, ':m' => $m]);
    }
    return $st->fetch() ?: null;
}

/* ============================ UNLOCK ============================ */
if ($action === 'unlock') {
    $row = hs_locate($pdo, $H);
    if (!$row) hs_out(['error' => 'not_found', 'message' => 'Subscription not found.'], 404);
    if ($row['status'] !== 'APPLIED') hs_out(['error' => 'bad_state', 'message' => 'Nothing to unlock.'], 409);
    $pdo->prepare("UPDATE ca_host_subscription SET is_locked = 0 WHERE id = :id AND host_id = :h")
        ->execute([':id' => (int) $row['id'], ':h' => $H]);
    hs_out(['ok' => true, 'message' => 'Unlocked. You can now roll it back.']);
}

/* ============================ LOCK (re-lock) =================== */
if ($action === 'lock') {
    $row = hs_locate($pdo, $H);
    if (!$row) hs_out(['error' => 'not_found', 'message' => 'Subscription not found.'], 404);
    if ($row['status'] !== 'APPLIED') hs_out(['error' => 'bad_state', 'message' => 'Nothing to lock.'], 409);
    $pdo->prepare("UPDATE ca_host_subscription SET is_locked = 1 WHERE id = :id AND host_id = :h")
        ->execute([':id' => (int) $row['id'], ':h' => $H]);
    hs_out(['ok' => true, 'message' => 'Locked.']);
}

/* ============================ ROLLBACK ========================= */
if ($action === 'rollback') {
    $row = hs_locate($pdo, $H);
    if (!$row) hs_out(['error' => 'not_found', 'message' => 'Subscription not found.'], 404);
    if ($row['status'] !== 'APPLIED') hs_out(['error' => 'bad_state', 'message' => 'Already rolled back.'], 409);
    if ((int) $row['is_locked'] === 1) {
        hs_out(['error' => 'locked', 'message' => 'This subscription is locked. Unlock it first.'], 409);
    }
    try {
        $pdo->beginTransaction();
        if (!empty($row['expense_id'])) {
            $pdo->prepare("UPDATE ca_expense SET STATUS = 'N' WHERE ID = :e AND HOST_ID = :h")
                ->execute([':e' => (int) $row['expense_id'], ':h' => $H]);
        }
        $pdo->prepare("UPDATE ca_host_subscription SET status = 'ROLLED_BACK', is_locked = 0 WHERE id = :id AND host_id = :h")
            ->execute([':id' => (int) $row['id'], ':h' => $H]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        hs_out(['error' => 'query', 'message' => 'Rollback failed.'], 500);
    }
    hs_out(['ok' => true, 'message' => 'Rolled back — removed from that month\'s ledger.']);
}

hs_out(['error' => 'bad_request', 'message' => 'Unknown action.'], 400);

} catch (Throwable $e) {
    hs_out(['error' => 'query', 'message' => 'Something went wrong.', 'detail' => $e->getMessage()], 500);
}
