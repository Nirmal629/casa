<?php

declare(strict_types=1);

/** @var App\Core\Router $router */

// Store & Accessories routes
$router->get('/accessories-shop.php', function() { require_once __DIR__ . '/../Views/store/accessories-shop.php'; });
$router->get('/addToCart.php', function() { require_once __DIR__ . '/../Views/store/addToCart.php'; });
$router->post('/addToCart.php', function() { require_once __DIR__ . '/../Views/store/addToCart.php'; });
$router->get('/check-out.php', function() { require_once __DIR__ . '/../Views/store/checkout.php'; });
$router->post('/check-out.php', function() { require_once __DIR__ . '/../Views/store/checkout.php'; });
$router->get('/product-listing.php', function() { require_once __DIR__ . '/../Views/store/product-listing.php'; });
$router->get('/product-details-page.php', function() { require_once __DIR__ . '/../Views/store/product-details.php'; });
$router->get('/my-order.php', function() { require_once __DIR__ . '/../Views/store/orders.php'; });
