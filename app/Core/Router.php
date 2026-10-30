<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    protected array $routes = [];
    protected array $globalMiddleware = [
        \App\Middleware\CsrfGuard::class,
    ];
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function use(array|callable|string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function get(string $path, callable|array $callback, array|callable|string $middleware = []): void
    {
        $this->addRoute('GET', $path, $callback, $middleware);
    }

    public function post(string $path, callable|array $callback, array|callable|string $middleware = []): void
    {
        $this->addRoute('POST', $path, $callback, $middleware);
    }

    private function addRoute(string $method, string $path, callable|array $callback, array|callable|string $middleware): void
    {
        $this->routes[$method][$path] = [
            'callback'   => $callback,
            'middleware' => $middleware,
        ];
    }

    public function resolve(): mixed
    {
        $path   = $this->request->getUri();
        $method = $this->request->getMethod();

        $matchedRoute = null;
        $params = [];

        // 1. Exact match fast path
        if (isset($this->routes[$method][$path])) {
            $matchedRoute = $this->routes[$method][$path];
        } else {
            // 2. Dynamic route pattern match (e.g. /api/payments/{id}/approve)
            if (isset($this->routes[$method])) {
                foreach ($this->routes[$method] as $routePath => $route) {
                    if (str_contains($routePath, '{')) {
                        $pattern = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath) . '$#';
                        if (preg_match($pattern, $path, $matches)) {
                            $matchedRoute = $route;
                            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                            break;
                        }
                    }
                }
            }
        }

        if ($matchedRoute === null) {
            // Check if path exists under any other method (for 405 detection)
            $pathExists = false;
            foreach ($this->routes as $routeMethod => $routes) {
                if (isset($routes[$path])) {
                    $pathExists = true;
                    break;
                }
                foreach ($routes as $routePath => $route) {
                    if (str_contains($routePath, '{')) {
                        $pattern = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath) . '$#';
                        if (preg_match($pattern, $path)) {
                            $pathExists = true;
                            break 2;
                        }
                    }
                }
            }

            $this->response->setStatusCode($pathExists ? 405 : 404);
            echo $pathExists ? "405 Method Not Allowed" : "404 Not Found";
            return null;
        }

        $this->request->setRouteParams($params);

        $callback   = $matchedRoute['callback'];
        $middleware = $matchedRoute['middleware'];

        // Run global middleware pipeline (e.g. CSRF verification on mutating requests)
        foreach ($this->globalMiddleware as $gm) {
            $this->runMiddleware($gm);
        }

        $this->runMiddleware($middleware);

        if (is_array($callback)) {
            [$controllerClass, $action] = $callback;
            return call_user_func_array([new $controllerClass(), $action], array_values($params));
        }

        return call_user_func_array($callback, array_values($params));
    }

    private function runMiddleware(array|callable|string $middleware): void
    {
        if (empty($middleware)) {
            return;
        }

        if (is_callable($middleware)) {
            call_user_func($middleware);
            return;
        }

        if (is_string($middleware) && class_exists($middleware) && method_exists($middleware, 'handle')) {
            call_user_func([$middleware, 'handle']);
            return;
        }

        if (is_array($middleware) && isset($middleware[0]) && is_string($middleware[0]) && class_exists($middleware[0])) {
            $args = $middleware[1] ?? [];
            call_user_func([$middleware[0], 'handle'], $args);
            return;
        }
        throw new \RuntimeException('Invalid route middleware configuration.');
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}

