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
}
