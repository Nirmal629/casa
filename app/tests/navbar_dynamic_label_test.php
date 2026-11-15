<?php

declare(strict_types=1);

require_once __DIR__ . '/../../init.php';

// Test Suite for Dynamic Navbar Label Requirement
echo "Running Dynamic Navbar Label Tests...\n\n";

$passed = 0;
$failed = 0;

function assertLabel(string $testName, string $actual, string $expected): void {
    global $passed, $failed;
    if ($actual === $expected) {
        echo " [PASS] {$testName}: got '{$actual}'\n";
        $passed++;
    } else {
        echo " [FAIL] {$testName}: expected '{$expected}', got '{$actual}'\n";
        $failed++;
    }
}

// Helper to simulate navbar label calculation logic
function resolveNavbarStoreLabel(
    string $currentInnerPage,
    string $requestUri,
    ?string $club_name = null,
    ?int $host_id = null,
    ?int $club_id = null
): string {
    $reqPath = parse_url($requestUri, PHP_URL_PATH) ?? '';
    $isClubGamesPage = (
        $currentInnerPage === 'player-dashboard.php'
        || str_contains($reqPath, 'player-dashboard')
        || str_contains($reqPath, 'player/dashboard')
    );

    $navbarStoreLabel = 'Casa Store';

    if ($isClubGamesPage) {
        $resolvedClubName = '';

        if (!empty($club_name) && is_string($club_name)) {
            $resolvedClubName = trim($club_name);
        }

        if ($resolvedClubName === '') {
            $candidateClubId = $club_id ?? 0;
            $candidateHostId = $host_id ?? 0;

            $clubModel = new \App\Models\ClubModel();
            if ($candidateClubId > 0) {
                $clubData = $clubModel->find($candidateClubId);
                if (!empty($clubData['club_name'])) {
                    $resolvedClubName = trim((string) $clubData['club_name']);
                }
            } elseif ($candidateHostId > 0) {
                $clubData = $clubModel->findByHostId($candidateHostId);
                if (!empty($clubData['club_name'])) {
                    $resolvedClubName = trim((string) $clubData['club_name']);
                }
            }
        }

        if ($resolvedClubName !== '') {
            $navbarStoreLabel = $resolvedClubName;
        }
    }

    return $navbarStoreLabel;
}

// 1. Home Page / Player Hub test -> Should remain 'Casa Store'
assertLabel(
    'Normal Home Page (Player Hub)',
    resolveNavbarStoreLabel('player-hub.php', '/player/player-hub.php'),
    'Casa Store'
);

assertLabel(
    'Normal Home Page (Root index.php)',
    resolveNavbarStoreLabel('index.php', '/index.php'),
    'Casa Store'
);

// 2. Club Games Page with CasaClubToronto (host_id = 21)
assertLabel(
    'Club Games Page (host_id=21 -> CasaClubToronto)',
    resolveNavbarStoreLabel('player-dashboard.php', '/player/player-dashboard.php?host_id=21', null, 21),
    'CasaClubToronto'
);

// 3. Club Games Page with pre-resolved club_name from Controller
assertLabel(
    'Club Games Page with pre-resolved club_name',
    resolveNavbarStoreLabel('player-dashboard.php', '/player/player-dashboard', 'CasaClubToronto'),
    'CasaClubToronto'
);

// 4. Other Clubs dynamically (host_id = 304 -> NetNinja)
assertLabel(
    'Club Games Page (host_id=304 -> NetNinja)',
    resolveNavbarStoreLabel('player-dashboard.php', '/player/player-dashboard.php?host_id=304', null, 304),
    'NetNinja'
);

// 5. Other Clubs dynamically (host_id = 268 -> Birdiebusters)
assertLabel(
    'Club Games Page (host_id=268 -> Birdiebusters)',
    resolveNavbarStoreLabel('player-dashboard.php', '/player/player-dashboard.php?host_id=268', null, 268),
    'Birdiebusters'
);

// 6. By Club ID (club_id = 1 -> CasaClubToronto)
assertLabel(
    'Club Games Page (club_id=1 -> CasaClubToronto)',
    resolveNavbarStoreLabel('player-dashboard.php', '/player/player-dashboard.php?club_id=1', null, null, 1),
    'CasaClubToronto'
);

// 7. Invalid/Unknown Host ID -> Defaults safely to 'Casa Store'
assertLabel(
    'Club Games Page (invalid host_id=999999 -> fallback)',
    resolveNavbarStoreLabel('player-dashboard.php', '/player/player-dashboard.php?host_id=999999', null, 999999),
    'Casa Store'
);

// 8. Outside Club Context (Profile Page) -> Reverts to 'Casa Store'
assertLabel(
    'Outside Club Context (player-profile.php)',
    resolveNavbarStoreLabel('player-profile.php', '/player/player-profile.php', null, 21),
    'Casa Store'
);

// 9. Outside Club Context (Product Listing) -> Reverts to 'Casa Store'
assertLabel(
    'Outside Club Context (product-listing.php)',
    resolveNavbarStoreLabel('product-listing.php', '/product-listing.php', null, 21),
    'Casa Store'
);

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
if ($failed > 0) {
    exit(1);
}

