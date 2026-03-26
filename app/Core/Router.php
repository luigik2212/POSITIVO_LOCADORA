<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $action, bool $auth = false): void
    {
        $this->addRoute('GET', $path, $action, $auth);
    }

    public function post(string $path, array $action, bool $auth = false): void
    {
        $this->addRoute('POST', $path, $action, $auth);
    }

    private function addRoute(string $method, string $path, array $action, bool $auth): void
    {
        $this->routes[$method][$path] = ['action' => $action, 'auth' => $auth];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = appBasePath();

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            exit('Página não encontrada');
        }

        if ($route['auth'] && !isAuthenticated()) {
            header('Location: ' . url('/login'));
            exit;
        }

        if ($path === '/login' && isAuthenticated()) {
            header('Location: ' . url('/'));
            exit;
        }

        [$controllerClass, $methodName] = $route['action'];
        $controller = new $controllerClass();
        $controller->{$methodName}();
    }
}
