<?php

declare(strict_types=1);

/** @var App\Core\Router $router */

// Public pages routes
$router->get('/about-us.php', function() { require_once __DIR__ . '/../Views/pages/company/about-us.php'; });
$router->get('/about-us', function() { require_once __DIR__ . '/../Views/pages/company/about-us.php'; });
$router->get('/contact-us.php', function() { require_once __DIR__ . '/../Views/pages/contact/contact-us.php'; });
$router->get('/contact-us', function() { require_once __DIR__ . '/../Views/pages/contact/contact-us.php'; });
$router->get('/contact-handler.php', function() { require_once __DIR__ . '/../Views/pages/contact/contact-handler.php'; });
$router->post('/contact-handler.php', function() { require_once __DIR__ . '/../Views/pages/contact/contact-handler.php'; });
$router->get('/gallery-page.php', function() { require_once __DIR__ . '/../Views/pages/gallery/gallery-page.php'; });
$router->get('/gallery-page', function() { require_once __DIR__ . '/../Views/pages/gallery/gallery-page.php'; });
$router->get('/gallery.php', function() { require_once __DIR__ . '/../Views/pages/gallery/gallery.php'; });
$router->get('/casa-clubs.php', function() { require_once __DIR__ . '/../Views/pages/community/casa-clubs.php'; });
$router->get('/casa-clubs', function() { require_once __DIR__ . '/../Views/pages/community/casa-clubs.php'; });
$router->get('/casa-trainers.php', function() { require_once __DIR__ . '/../Views/pages/community/casa-trainers.php'; });
$router->get('/casa-trainers', function() { require_once __DIR__ . '/../Views/pages/community/casa-trainers.php'; });
$router->get('/organiser.php', function() { require_once __DIR__ . '/../Views/pages/community/organiser.php'; });
$router->get('/organiser', function() { require_once __DIR__ . '/../Views/pages/community/organiser.php'; });
$router->get('/players.php', function() { require_once __DIR__ . '/../Views/pages/community/players.php'; });
$router->get('/players', function() { require_once __DIR__ . '/../Views/pages/community/players.php'; });
$router->get('/discover-games.php', function() { require_once __DIR__ . '/../Views/pages/community/discover-games.php'; });

// Legal pages routes
$router->get('/privacy-policy.php', function() { require_once __DIR__ . '/../Views/pages/legal/privacy-policy.php'; });
$router->get('/privacy-policy', function() { require_once __DIR__ . '/../Views/pages/legal/privacy-policy.php'; });
$router->get('/termsCondition.php', function() { require_once __DIR__ . '/../Views/pages/legal/termsCondition.php'; });
$router->get('/terms-condition', function() { require_once __DIR__ . '/../Views/pages/legal/termsCondition.php'; });
$router->get('/CancellationPolicy.php', function() { require_once __DIR__ . '/../Views/pages/legal/CancellationPolicy.php'; });
$router->get('/cancellation-policy', function() { require_once __DIR__ . '/../Views/pages/legal/CancellationPolicy.php'; });
