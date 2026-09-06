<?php
/**
 * api/audit/_bootstrap.php
 * ------------------------------------------------------------------
 * Shared bootstrap for every Host Audit Log endpoint.
 *
 *  - starts the session
 *  - HARD Host-only gate (403 for anyone who is not a Host)
 *  - opens a PDO connection ($pdo)
 *  - exposes $HOST_ID (the logged-in host's user id) — every query MUST
 *    be scoped to this; a host_id request parameter is never trusted
 *  - date-range + pagination helpers
 *  - json_out() responder
 * ------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Match the timezone the rest of the API layer records timestamps in.
date_default_timezone_set('America/Toronto');

// An API must return clean JSON — never let a stray notice/warning corrupt it.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/** Send a JSON payload and stop. */
function json_out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---- Authorization: Host only, enforced server-side ---------------------- */
$__uid  = $_SESSION['user_id']  ?? null;
$__role = isset($_SESSION['usertype']) ? trim($_SESSION['usertype']) : '';

if (!$__uid) {
    json_out(['error' => 'unauthenticated', 'message' => 'Please sign in.'], 401);
}
if (strcasecmp($__role, 'Host') !== 0) {
    json_out(['error' => 'forbidden', 'message' => 'Audit Log is available to Host accounts only.'], 403);
}

$HOST_ID = (int) $__uid;

/* ---- Database ---------------------------------------------------------- */
require_once __DIR__ . '/../../config/env.php';
try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', 'localhost') .
        ';dbname='    . env('DB_NAME', 'casa_test') .
        ';charset='   . env('DB_CHARSET', 'utf8mb4'),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (Throwable $e) {
    json_out(['error' => 'db', 'message' => 'Database unavailable.'], 500);
}

/*
 * "Now" for every date-range calculation. This environment's DB clock can be
 * hours off the PHP clock, and CREATED_AT columns are written by the DB, so
 * the ranges MUST be anchored to the database's own clock — not PHP's.
 */
try {
    $GLOBALS['__AUDIT_NOW'] = $pdo->query('SELECT NOW()')->fetchColumn() ?: date('Y-m-d H:i:s');
} catch (Throwable $e) {
    $GLOBALS['__AUDIT_NOW'] = date('Y-m-d H:i:s');
}

/* ---- Request helpers -------------------------------------------------- */

/**
 * Resolve a date range from ?range=today|yesterday|7d|30d|custom (&from=&to=).
 * Returns ['from' => 'Y-m-d 00:00:00', 'to' => 'Y-m-d 23:59:59', 'key' => ..., 'days' => n].
 */
function audit_range(): array
{
    $key = $_GET['range'] ?? 'all';
    $now = new DateTime($GLOBALS['__AUDIT_NOW'] ?? 'now');   // anchored to the DB clock
    $end = (clone $now)->setTime(23, 59, 59);

    switch ($key) {
        case 'all':
            $start = (new DateTime('2000-01-01'))->setTime(0, 0, 0);
            $key   = 'all';
            break;
        case 'today':
            $start = (clone $now)->setTime(0, 0, 0);
            break;
        case 'yesterday':
            $start = (clone $now)->modify('-1 day')->setTime(0, 0, 0);
            $end   = (clone $now)->modify('-1 day')->setTime(23, 59, 59);
            break;
        case '30d':
            $start = (clone $now)->modify('-29 days')->setTime(0, 0, 0);
            break;
        case '90d':
            $start = (clone $now)->modify('-89 days')->setTime(0, 0, 0);
            break;
        case 'custom':
            $f = $_GET['from'] ?? '';
            $t = $_GET['to'] ?? '';
            $start = DateTime::createFromFormat('Y-m-d', $f) ?: (clone $now)->modify('-6 days');
            $endC  = DateTime::createFromFormat('Y-m-d', $t) ?: clone $now;
            $start->setTime(0, 0, 0);
            $end = $endC->setTime(23, 59, 59);
            $key = 'custom';
            break;
        case '7d':
        default:
            $key   = '7d';
            $start = (clone $now)->modify('-6 days')->setTime(0, 0, 0);
            break;
    }
    if ($start > $end) { [$start, $end] = [$end, $start]; }

    return [
        'from' => $start->format('Y-m-d H:i:s'),
        'to'   => $end->format('Y-m-d H:i:s'),
        'key'  => $key,
        'days' => (int) $start->diff($end)->format('%a') + 1,
    ];
}

/** Clamp an int within [min,max] with a fallback default. */
function audit_int($v, int $def, int $min, int $max): int
{
    if (!is_numeric($v)) return $def;
    $v = (int) $v;
    return max($min, min($max, $v));
}

/**
 * Classify a user's engagement behaviour from host-scoped metrics.
 * Every rule is derived from real counts — no arbitrary buckets.
 */
function audit_behaviour(array $m): string
{
    $viewed      = (int) ($m['views'] ?? 0);
    $listViews   = (int) ($m['list_views'] ?? 0);
    $joins       = (int) ($m['joins'] ?? 0);
    $completed   = (int) ($m['completed'] ?? 0);
    $abandoned   = (int) ($m['abandoned'] ?? 0);
    $activeDays  = (int) ($m['active_days'] ?? 0);
    $sinceActive = $m['days_since_active'];          // null => never active
    $browse      = $viewed + $listViews;

    if ($sinceActive === null || $sinceActive > 30) return 'Inactive';
    if (($browse + $joins) < 3)                      return 'Low Activity';
    if ($joins >= 2 && ($abandoned / max($joins, 1)) >= 0.5)      return 'High Abandonment';
    if ($joins === 0 && $browse >= 5)                             return 'Browsing Only';
    if ($joins >= 3 && ($completed / max($joins, 1)) >= 0.6)      return 'Highly Engaged';
    if ($activeDays >= 3)                                         return 'Returning';
    return 'Low Activity';
}

/** Ordered list of behaviour categories (for the distribution chart / filter). */
function audit_behaviour_list(): array
{
    return ['Highly Engaged', 'Returning', 'Browsing Only', 'High Abandonment', 'Low Activity', 'Inactive'];
}
