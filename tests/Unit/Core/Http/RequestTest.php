<?php

namespace Tests\Unit\Core\Http;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\Http\Request;

final class RequestTest extends TestCase
{
    public function test_it_exposes_request_data_without_using_globals(): void
    {
        $request = new Request('post', '/contacto', ['product' => '4'], ['name' => 'Cliente']);

        self::assertSame('POST', $request->method());
        self::assertTrue($request->isMethod('POST'));
        self::assertSame('/contacto', $request->path());
        self::assertSame('4', $request->query('product'));
        self::assertSame('Cliente', $request->input('name'));
        self::assertNull($request->input('missing'));
    }
}
