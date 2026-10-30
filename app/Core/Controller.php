<?php

declare(strict_types=1);

namespace App\Core;
use RuntimeException;

abstract class Controller
{
    protected Response $response;
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    public function render(string $viewPath, array $data = []): void
    {
        extract($data);
        $fullPath = __DIR__ . '/../Views/' . $viewPath . '.php';

        if (!file_exists($fullPath)) {
            $parts = explode('/', $viewPath);
            if (count($parts) === 2) {
                $altPath = __DIR__ . '/../Views/' . $parts[0] . '/' . $parts[0] . '-' . $parts[1] . '.php';
                if (file_exists($altPath)) {
                    $fullPath = $altPath;
                }
            }
        }

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            throw new RuntimeException("View file not found: {$viewPath}");
        }
    }

    public function json(mixed $data, int $statusCode = 200): void
    {
        $this->response->json($data, $statusCode);
    }

    public function redirect(string $url): void
    {
        $this->response->redirect($url);
    }
}
