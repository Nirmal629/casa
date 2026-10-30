<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public function getUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        return rtrim($uri, '/') ?: '/';
    }

    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool { return $this->getMethod() === 'POST'; }
    public function isGet(): bool  { return $this->getMethod() === 'GET'; }
    public function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'))
            || (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));
    }

    protected array $routeParams = [];

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function ip(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
