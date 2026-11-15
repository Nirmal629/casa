<?php

declare(strict_types=1);

require_once __DIR__ . '/../../init.php';

echo "Running Inner-Header Rendering E2E Tests...\n\n";

$passed = 0;
$failed = 0;

$userModel = new \App\Models\UserModel(\App\Config\Database::getPdo());
$user = $userModel->findAuthUserById(8);
if (!$user) {
    echo "Could not find active test player.\n";
    exit(1);
}
$secVersion = \App\Helpers\Session::computeSecurityVersion($user);

function runHeaderTest(
    string $scenario,
    array $getParams,
    string $phpSelf,
    string $requestUri,
    string $expectedSnippet,
    array $user,
    string $secVersion
): void {
    global $passed, $failed;

    // Isolate by running in a separate PHP process
    $script = __DIR__ . '/temp_sub_render.php';
    $code = '<?php
    $_GET = ' . var_export($getParams, true) . ';
    $_SERVER["PHP_SELF"] = ' . var_export($phpSelf, true) . ';
    $_SERVER["REQUEST_URI"] = ' . var_export($requestUri, true) . ';
    $_SERVER["SCRIPT_NAME"] = ' . var_export($phpSelf, true) . ';

    require_once __DIR__ . "/../../init.php";
    \App\Helpers\Session::start();
    $_SESSION["user_id"] = ' . $user['ID'] . ';
    $_SESSION["usertype"] = "Player";
    $_SESSION["role"] = "player";
    $_SESSION["NAME"] = ' . var_export($user['NAME'], true) . ';
    $_SESSION["security_version"] = ' . var_export($secVersion, true) . ';

    ob_start();
    include __DIR__ . "/../Views/layouts/header/inner-header.php";
    $output = ob_get_clean();

    $expected = ' . var_export($expectedSnippet, true) . ';
    if (strpos($output, $expected) !== false) {
        echo "FOUND";
    } else {
        echo "NOT_FOUND";
    }
    ';

    file_put_contents($script, $code);
    $output = trim((string) shell_exec('php ' . escapeshellarg($script)));
    @unlink($script);

    if ($output === 'FOUND') {
        echo " [PASS] {$scenario}: found '{$expectedSnippet}' in rendered HTML\n";
        $passed++;
    } else {
        echo " [FAIL] {$scenario}: expected snippet '{$expectedSnippet}' NOT found! (output: {$output})\n";
        $failed++;
    }
}

// 1. Normal Home Page (The Player Hub) -> Should show Casa Store
runHeaderTest(
    'Normal Home Page (The Player Hub)',
    [],
    '/player/player-hub.php',
    '/player/player-hub.php',
    '<span>Casa Store</span>',
    $user,
    $secVersion
);

// 2. Club Games Page: CasaClubToronto (host_id = 21)
runHeaderTest(
    'Club Games Page: CasaClubToronto (host_id = 21)',
    ['host_id' => '21'],
    '/player/player-dashboard.php',
    '/player/player-dashboard.php?host_id=21',
    '<span>CasaClubToronto</span>',
    $user,
    $secVersion
);

// 3. Club Games Page: NetNinja (host_id = 304)
runHeaderTest(
    'Club Games Page: NetNinja (host_id = 304)',
    ['host_id' => '304'],
    '/player/player-dashboard.php',
    '/player/player-dashboard.php?host_id=304',
    '<span>NetNinja</span>',
    $user,
    $secVersion
);

// 4. Club Games Page: Birdiebusters (host_id = 268)
runHeaderTest(
    'Club Games Page: Birdiebusters (host_id = 268)',
    ['host_id' => '268'],
    '/player/player-dashboard.php',
    '/player/player-dashboard.php?host_id=268',
    '<span>Birdiebusters</span>',
    $user,
    $secVersion
);

// 5. Navigate back to Home Page (The Player Hub) -> Should revert to Casa Store
runHeaderTest(
    'Navigate Back to Home Page (The Player Hub)',
    [],
    '/player/player-hub.php',
    '/player/player-hub.php',
    '<span>Casa Store</span>',
    $user,
    $secVersion
);

// 6. Navigate to Store (Product Listing) -> Outside Club Context -> Casa Store
runHeaderTest(
    'Outside Club Context (Product Listing)',
    [],
    '/product-listing.php',
    '/product-listing.php',
    '<span>Casa Store</span>',
    $user,
    $secVersion
);

// 7. Navigate to Profile -> Outside Club Context -> Casa Store
runHeaderTest(
    'Outside Club Context (Player Profile)',
    [],
    '/player/player-profile.php',
    '/player/player-profile.php',
    '<span>Casa Store</span>',
    $user,
    $secVersion
);

// 8. Invalid Host ID on Games Page -> Safely defaults to Casa Store
runHeaderTest(
    'Invalid Host ID (host_id = 999999) -> Defaults to Casa Store',
    ['host_id' => '999999'],
    '/player/player-dashboard.php',
    '/player/player-dashboard.php?host_id=999999',
    '<span>Casa Store</span>',
    $user,
    $secVersion
);

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
if ($failed > 0) {
    exit(1);
}
