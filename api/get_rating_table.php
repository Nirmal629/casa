<?php
// api/get_rating_table.php
session_start();

// show errors while debugging (turn off in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// JSON response
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// include DB connection (adjust path if necessary)
$included = false;
$try = [
    __DIR__ . '/../dbConnection.php',   // expected location
    __DIR__ . '/dbConnection.php',
    __DIR__ . '/../../dbConnection.php'
];
foreach ($try as $p) {
    if (file_exists($p)) {
        include_once $p;
        $included = true;
        break;
    }
}
if (!$included) {
    http_response_code(500);
    echo json_encode(['error' => 'dbConnection.php not found. Looked at: ' . implode(' | ', $try)]);
    exit;
}

// validate $conn
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection ($conn) not available or not mysqli.']);
    exit;
}

// read filters (GET)
$levelFilter  = isset($_GET['level']) && $_GET['level'] !== '' ? trim($_GET['level']) : '';
$genderFilter = isset($_GET['gender']) && $_GET['gender'] !== '' ? trim($_GET['gender']) : '';

// helper: safe escape
function esc($conn, $v) {
    return mysqli_real_escape_string($conn, $v);
}

// helper: gender clause (matches GENDER or GENDER_CATEGORY or Mix)
function genderClause($conn, $g) {
    $gE = esc($conn, $g);
    return " (GENDER = '{$gE}' OR GENDER = 'Mix') ";
}

// Build WHERE common base
$baseWhere = "USERTYPE='Player' AND DEL_STATUS='N' AND LOG_STATUS='Y'";

// If level filter: return flat players list
if ($levelFilter !== '') {
    $lvl = esc($conn, $levelFilter);
    $where = $baseWhere . " AND VERIFIED_LEVEL = '{$lvl}'";

    if ($genderFilter !== '') {
        $where .= " AND " . genderClause($conn, $genderFilter);
    }

    // Use NULLIF to treat empty string as NULL, COALESCE to fallback to 0, cast for numeric ordering
    $sql = "SELECT ID, NAME, VERIFIED_LEVEL AS level,
                   COALESCE(NULLIF(CURRENT_RANKING, ''), '0') AS ranking,
                   COALESCE(GENDER, GENDER, '') AS gender
            FROM ca_users
            WHERE {$where}
            ORDER BY CAST(COALESCE(NULLIF(CURRENT_RANKING, ''), '0') AS UNSIGNED) DESC";

    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        http_response_code(500);
        echo json_encode(['error' => mysqli_error($conn), 'query' => $sql]);
        exit;
    }

    $players = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $players[] = [
            'ID'      => $r['ID'],
            'name'    => $r['NAME'],
            'level'   => $r['level'],
            'ranking' => is_numeric($r['ranking']) ? (int)$r['ranking'] : 0,
            'gender'  => $r['gender']
        ];
    }

    echo json_encode(['players' => $players]);
    exit;
}

// No level filter: grouped by level (preserve desired ordering)
$levelOrder = [
    "Advanced",
    "Intermediate +",
    "Intermediate",
    "Amateur",
    "Beginner"
];

$output = [];

foreach ($levelOrder as $lvl) {
    $lvlE = esc($conn, $lvl);
    $where = "VERIFIED_LEVEL = '{$lvlE}' AND {$baseWhere}";

    if ($genderFilter !== '') {
        $where .= " AND " . genderClause($conn, $genderFilter);
    }

    $sql = "SELECT ID, NAME, VERIFIED_LEVEL,
                   COALESCE(NULLIF(CURRENT_RANKING, ''), '0') AS CURRENT_RANKING,
                   COALESCE(GENDER, GENDER, '') AS GENDER
            FROM ca_users
            WHERE {$where}
            ORDER BY CAST(COALESCE(NULLIF(CURRENT_RANKING, ''), '0') AS UNSIGNED) ASC, NAME ASC";

    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        http_response_code(500);
        echo json_encode(['error' => mysqli_error($conn), 'query' => $sql]);
        exit;
    }

    $players = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $players[] = [
            'name' => $row['NAME'],
            'ranking' => is_numeric($row['CURRENT_RANKING']) ? (int)$row['CURRENT_RANKING'] : 0,
            'level' => $row['VERIFIED_LEVEL'],
            'gender' => $row['GENDER']
        ];
    }

    if (!empty($players)) {
        $output[] = [
            'level' => $lvl,
            'players' => $players
        ];
    }
}

// return grouped output
echo json_encode($output);
exit;
