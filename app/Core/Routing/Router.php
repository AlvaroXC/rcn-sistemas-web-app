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
        foreach ([false, true] as $dynamic) {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $request->method() || str_contains($route['path'], '{') !== $dynamic) {
                    continue;
                }

                $parameters = $this->parameters($route['path'], $request->path());
                if ($parameters === null) {
                    continue;
                }

                $result = ($route['handler'])($request->withQuery($parameters));
                if (!$result instanceof Response) {
                    throw new RuntimeException('Las rutas deben devolver una instancia de Response.');
                }

                return $result;
            }
        }

        return Response::html('Página no encontrada.', 404);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '//' ? '/' : $normalized;
    }

    private function parameters(string $routePath, string $requestPath): ?array
    {
        preg_match_all('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', $routePath, $tokens, PREG_OFFSET_CAPTURE);
        $pattern = '';
        $position = 0;

        foreach ($tokens[0] as $index => [$token, $offset]) {
            $pattern .= preg_quote(substr($routePath, $position, $offset - $position), '#');
            $pattern .= '(?P<' . $tokens[1][$index][0] . '>[^/]+)';
            $position = $offset + strlen($token);
        }

        $pattern .= preg_quote(substr($routePath, $position), '#');
        if (!preg_match('#^' . $pattern . '$#', $this->normalizePath($requestPath), $matches)) {
            return null;
        }

        $parameters = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = rawurldecode($value);
            }
        }

        return $parameters;
    }
}
