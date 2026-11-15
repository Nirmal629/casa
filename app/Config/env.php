<?php

if (!function_exists('load_env_file')) {

    function load_env_file(string $path, bool $overwrite = true): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!$overwrite && (isset($_ENV[$name]) || isset($_SERVER[$name]) || getenv($name) !== false)) {
                continue;
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;

            putenv("{$name}={$value}");
        }
    }
}


if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value === false || $value === null || $value === '')
            ? $default
            : $value;
    }
}

if (!function_exists('get_loaded_env_info')) {
    /**
     * Get details of currently active APP_ENV and loaded .env files.
     */
    function get_loaded_env_info(): array
    {
        return [
            'app_env'      => env('APP_ENV', 'local'),
            'app_debug'    => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
            'loaded_files' => $_ENV['LOADED_ENV_FILES'] ?? [],
        ];
    }
}

$rootDir = dirname(__DIR__, 2);

$baseEnvFile = $rootDir . '/.env';

$loadedFiles = [];

if (is_file($baseEnvFile)) {
    load_env_file($baseEnvFile, true);
    $loadedFiles[] = basename($baseEnvFile);
}

$appEnv = strtolower(trim(env('APP_ENV', 'local')));

$envSpecificFile = $rootDir . '/.env.' . $appEnv;

if (is_file($envSpecificFile)) {
    $environmentFile = $envSpecificFile;
} else {
    $environmentFile = match ($appEnv) {
        'production', 'prod'          => $rootDir . '/.env.production',
        'development', 'dev'          => is_file($rootDir . '/.env.development') ? $rootDir . '/.env.development' : $rootDir . '/.env.local',
        'local'                       => $rootDir . '/.env.local',
        default                       => null,
    };
}

if ($environmentFile !== null && is_file($environmentFile)) {
    load_env_file($environmentFile, true);
    $loadedFiles[] = basename($environmentFile);
}

$_ENV['LOADED_ENV_FILES'] = $loadedFiles;

// Dynamically configure application timezone purely from loaded env file (.env.local / .env.development / .env.production)
$appTimezone = env('APP_TIMEZONE', env('TIMEZONE', 'Asia/Kolkata'));
if (!empty($appTimezone)) {
    @date_default_timezone_set((string) $appTimezone);
}

// Centralized HTTP/API Request-Response Debug Logger
if (file_exists($rootDir . '/app/Helpers/HttpLogger.php')) {
    require_once $rootDir . '/app/Helpers/HttpLogger.php';
    \App\Helpers\HttpLogger::init();
}