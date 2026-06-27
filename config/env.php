<?php
function load_env_file($path)
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

function env($key, $default = null)
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    if ($value === null || $value === '') {
        return $default;
    }

    return $value;
}

$baseDir = dirname(__DIR__);
$envMode = env('APP_ENV', 'local');
$envFile = $baseDir . '/.env';

if ($envMode === 'production' || $envMode === 'prod') {
    if (is_file($baseDir . '/.env.prod')) {
        $envFile = $baseDir . '/.env.prod';
    }
} elseif (is_file($baseDir . '/.env.local')) {
    $envFile = $baseDir . '/.env.local';
}

load_env_file($envFile);
