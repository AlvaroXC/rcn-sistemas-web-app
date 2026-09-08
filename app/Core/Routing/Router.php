<?php

namespace RcnSistemas\Core\Routing;

use Closure;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RuntimeException;

final class Router
{
    /** @var array<int, array{method: string, path: string, handler: callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method() || $route['path'] !== $request->path()) {
                continue;
            }

            $result = ($route['handler'])($request);
            if (!$result instanceof Response) {
                throw new RuntimeException('Las rutas deben devolver una instancia de Response.');
            }

            return $result;
        }

        return Response::html('Página no encontrada.', 404);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '//' ? '/' : $normalized;
    }
}
