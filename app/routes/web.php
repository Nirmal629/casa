<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;

/** @var App\Core\Router $router */

// Home routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/index.php', [HomeController::class, 'index']);

$router->post('/', [AuthController::class, 'login']);
$router->post('/index.php', [AuthController::class, 'login']);

// Development Manual Testing Logger API
$router->get('/api/manual_test_logger.php', function() { require_once __DIR__ . '/../../api/manual_test_logger.php'; });
$router->post('/api/manual_test_logger.php', function() { require_once __DIR__ . '/../../api/manual_test_logger.php'; });
$router->get('/api/manual-test-logger', function() { require_once __DIR__ . '/../../api/manual_test_logger.php'; });
$router->post('/api/manual-test-logger', function() { require_once __DIR__ . '/../../api/manual_test_logger.php'; });


