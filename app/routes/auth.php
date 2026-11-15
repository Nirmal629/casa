<?php

declare(strict_types=1);

use App\Controllers\AuthController;

/** @var App\Core\Router $router */

// Auth routes
$router->get('/logout.php', [AuthController::class, 'logout']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->post('/login.php', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/reset.php', function() { http_response_code(404); echo 'Not Found'; });
