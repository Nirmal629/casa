<?php

// Central Entry Point & Router for PHP Built-in Server / Web Server
require_once __DIR__ . '/app/Helpers/WebAccess.php';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?? '');
if (\App\Helpers\WebAccess::blocked($path)) {
    http_response_code(404);
    exit('Not Found');
}
$filePath = __DIR__ . $path;

// Serve physical asset or API script files directly if they exist on disk
if ($path !== '/' && $path !== '/index.php' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Dispatch all virtual MVC routes to the Central Application Router
require_once __DIR__ . '/app/app.php';


