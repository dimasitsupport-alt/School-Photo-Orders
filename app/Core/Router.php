<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, mixed $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, mixed $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    private function add(string $method, string $path, mixed $action, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => rtrim($path, '/') ?: '/',
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $path);

            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                if (is_string($middleware)) {
                    (new $middleware())->handle();
                    continue;
                }

                if (is_callable($middleware)) {
                    $middleware();
                }
            }

            $this->callAction($route['action'], $params);
            return;
        }

        http_response_code(404);
        (new Controller())->view('errors/404', ['message' => 'Halaman tidak ditemukan.']);
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $parameterNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $matches) use (&$parameterNames): string {
            $parameterNames[] = $matches[1];
            return '([^/]+)';
        }, $routePath);

        if ($pattern === null || preg_match('#^' . $pattern . '$#', $requestPath, $matches) !== 1) {
            return null;
        }

        array_shift($matches);
        $params = [];

        foreach ($matches as $index => $value) {
            $params[$parameterNames[$index] ?? $index] = urldecode($value);
        }

        return $params;
    }

    private function callAction(mixed $action, array $params): void
    {
        if (is_callable($action)) {
            $action(...array_values($params));
            return;
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            (new $controller())->{$method}(...array_values($params));
            return;
        }

        throw new \RuntimeException('Invalid route action.');
    }
}
