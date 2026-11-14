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


