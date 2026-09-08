<?php

namespace Tests\Unit\Core\Routing;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Router;

final class RouterTest extends TestCase
{
    public function test_it_dispatches_a_matching_route(): void
    {
        $router = new Router();
        $router->get('/productos', static fn (): Response => Response::html('productos'));

        $response = $router->dispatch(new Request('GET', '/productos'));

        self::assertSame(200, $response->status());
        self::assertSame('productos', $response->content());
    }

    public function test_it_returns_not_found_for_an_unknown_route(): void
    {
        $router = new Router();

        $response = $router->dispatch(new Request('GET', '/desconocido'));

        self::assertSame(404, $response->status());
    }

    public function test_it_dispatches_a_dynamic_route_and_exposes_its_parameters(): void
    {
        $router = new Router();
        $router->get('/productos/{id}', static fn (Request $request): Response => Response::html((string) $request->query('id')));

        $response = $router->dispatch(new Request('GET', '/productos/27'));

        self::assertSame(200, $response->status());
        self::assertSame('27', $response->content());
    }

    public function test_it_prioritizes_static_routes_over_dynamic_routes(): void
    {
        $router = new Router();
        $router->get('/clientes/{id}', static fn (): Response => Response::html('detalle'));
        $router->get('/clientes/buscar', static fn (): Response => Response::html('buscador'));

        $response = $router->dispatch(new Request('GET', '/clientes/buscar'));

        self::assertSame('buscador', $response->content());
    }
}
