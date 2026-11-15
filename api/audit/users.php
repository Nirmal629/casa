<?php
/**
 * api/audit/users.php  — paginated, filtered, searchable user-activity table.
 *
 * Query params:
 *   page       (int, 1+)
 *   per_page   (int, 5-100, default 20)
 *   search     (matches NAME / EMAIL / ID)
 *   behaviour  (one of audit_behaviour_list(), or "all")
 *   sort       last_active | engagement | views | joins | completed | name
 *   dir        asc | desc
 *
 * Behaviour + engagement are computed in PHP from the metrics, so behaviour
 * filtering / sorting is applied after fetch on the host's universe (bounded
 * by the host's own player base, not the whole platform).
 */

require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_metrics.php';

$page     = audit_int($_GET['page'] ?? 1, 1, 1, 100000);
$perPage  = audit_int($_GET['per_page'] ?? 20, 20, 5, 100);
$search   = trim((string) ($_GET['search'] ?? ''));
$behav    = (string) ($_GET['behaviour'] ?? 'all');
$sort     = (string) ($_GET['sort'] ?? 'last_active');
$dir      = strtolower((string) ($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

$sortWhitelist = ['last_active', 'engagement', 'views', 'joins', 'completed', 'abandoned', 'name'];
if (!in_array($sort, $sortWhitelist, true)) {
    $sort = 'last_active';
}

try {
    $sql = audit_metrics_sql($HOST_ID);
    $params = [];

    if ($search !== '') {
        $sql .= " AND (u.NAME LIKE :s OR u.EMAIL LIKE :s2 OR u.ID = :sid) ";
        $params[':s']   = '%' . $search . '%';
        $params[':s2']  = '%' . $search . '%';
        $params[':sid'] = ctype_digit($search) ? (int) $search : 0;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $shaped = array_map('audit_shape_row', $rows);

    // behaviour filter (post-compute)
    if ($behav !== 'all' && in_array($behav, audit_behaviour_list(), true)) {
        $shaped = array_values(array_filter($shaped, fn($r) => $r['behaviour'] === $behav));
    }

    // sort
    usort($shaped, function ($a, $b) use ($sort, $dir) {
        $get = function ($r) use ($sort) {
            switch ($sort) {
                case 'name':        return strtolower($r['name']);
                case 'engagement':  return $r['engagement'];
                case 'views':       return $r['metrics']['views'];
                case 'joins':       return $r['metrics']['joins'];
                case 'completed':   return $r['metrics']['completed'];
                case 'abandoned':   return $r['metrics']['abandoned'];
                case 'last_active':
                default:            return $r['last_active'] ? strtotime($r['last_active']) : 0;
            }
        };
        $va = $get($a);
        $vb = $get($b);
        $cmp = $va <=> $vb;
        return $dir === 'asc' ? $cmp : -$cmp;
    });

    $total = count($shaped);
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $slice = array_slice($shaped, ($page - 1) * $perPage, $perPage);

    // session-time avg for the current page only (bounded work)
    $ids = array_column($slice, 'id');
    $sessionByUser = [];
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        $sq = $pdo->query("
            SELECT USER_ID, ROUND(AVG(sess)) AS avg_min FROM (
                SELECT l.USER_ID,
                    TIMESTAMPDIFF(MINUTE, l.CREATED_AT, (
                        SELECT MIN(o.CREATED_AT) FROM ca_player_logs o
                        WHERE o.USER_ID = l.USER_ID AND o.ACTIVITY_TYPE = 'LOGOUT'
                          AND o.CREATED_AT > l.CREATED_AT
                          AND o.CREATED_AT < l.CREATED_AT + INTERVAL 6 HOUR
                    )) AS sess
                FROM ca_player_logs l
                WHERE l.USER_ID IN ($in) AND l.ACTIVITY_TYPE = 'LOGIN'
            ) t
            WHERE sess IS NOT NULL AND sess BETWEEN 1 AND 360
            GROUP BY USER_ID
        ");
        foreach ($sq->fetchAll() as $r) {
            $sessionByUser[(int) $r['USER_ID']] = (int) $r['avg_min'];
        }
    }
    foreach ($slice as &$row) {
        $row['avg_session_min'] = $sessionByUser[$row['id']] ?? null;
    }
    unset($row);

    json_out([
        'data' => $slice,
        'pagination' => [
            'page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => $pages,
        ],
        'filters' => ['search' => $search, 'behaviour' => $behav, 'sort' => $sort, 'dir' => $dir],
    ]);
} catch (Throwable $e) {
    json_out(['error' => 'query', 'message' => 'Could not load users.', 'detail' => $e->getMessage()], 500);
}
