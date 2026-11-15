<?php

declare(strict_types=1);

/** @var App\Core\Router $router */

// Tournament & Court Dashboard routes
$router->get('/tournament', function() { require_once __DIR__ . '/../../tournament/index.php'; });
$router->post('/tournament', function() { require_once __DIR__ . '/../../tournament/index.php'; });
$router->get('/tournament/', function() { require_once __DIR__ . '/../../tournament/index.php'; });
$router->post('/tournament/', function() { require_once __DIR__ . '/../../tournament/index.php'; });

$router->get('/player/tournament', function() { require_once __DIR__ . '/../../tournament/index.php'; });
$router->post('/player/tournament', function() { require_once __DIR__ . '/../../tournament/index.php'; });
$router->get('/player/tournament/', function() { require_once __DIR__ . '/../../tournament/index.php'; });
$router->post('/player/tournament/', function() { require_once __DIR__ . '/../../tournament/index.php'; });

$router->get('/tournament/court-dashboard.php', function() { require_once __DIR__ . '/../../tournament/court-dashboard.php'; });
$router->post('/tournament/court-dashboard.php', function() { require_once __DIR__ . '/../../tournament/court-dashboard.php'; });
$router->get('/tournament/badminton-scorer.php', function() { require_once __DIR__ . '/../../tournament/badminton-scorer.php'; });
$router->post('/tournament/badminton-scorer.php', function() { require_once __DIR__ . '/../../tournament/badminton-scorer.php'; });
$router->get('/tournament/spin-wheel.php', function() { require_once __DIR__ . '/../../tournament/spin-wheel.php'; });
$router->post('/tournament/spin-wheel.php', function() { require_once __DIR__ . '/../../tournament/spin-wheel.php'; });
$router->get('/tournament/api-match-score.php', function() { require_once __DIR__ . '/../../tournament/api-match-score.php'; });
$router->post('/tournament/api-match-score.php', function() { require_once __DIR__ . '/../../tournament/api-match-score.php'; });

$router->get('/tournament-details.php', function() { require_once __DIR__ . '/../../tournament-details.php'; });
$router->get('/tournament-details', function() { require_once __DIR__ . '/../../tournament-details.php'; });
$router->get('/player/tournament-details.php', function() { require_once __DIR__ . '/../../tournament-details.php'; });
$router->get('/player/tournament-details', function() { require_once __DIR__ . '/../../tournament-details.php'; });
