<?php

namespace Tests\Unit\Services\Licenses;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Licenses\QuotationCatalog;

final class QuotationCatalogTest extends TestCase
{
    /** @return array<string, array{string, string, bool}> */
    public static function transitions(): array
    {
        return [
            'enviada a aceptada' => ['enviada', 'aceptada', true],
            'enviada a rechazada' => ['enviada', 'rechazada', true],
            'aceptada a facturada' => ['aceptada', 'facturada', true],
            'regreso aceptada a enviada' => ['aceptada', 'enviada', true],
            'regreso facturada a aceptada' => ['facturada', 'aceptada', true],
            'regreso rechazada a enviada' => ['rechazada', 'enviada', true],
            'mismo estado' => ['facturada', 'facturada', true],
            'salto enviada a facturada' => ['enviada', 'facturada', false],
            'rechazada a facturada' => ['rechazada', 'facturada', false],
            'rechazada a aceptada' => ['rechazada', 'aceptada', false],
            'aceptada a rechazada' => ['aceptada', 'rechazada', false],
            'facturada a enviada' => ['facturada', 'enviada', false],
            'facturada a rechazada' => ['facturada', 'rechazada', false],
            'estado desconocido' => ['borrador', 'enviada', false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_it_allows_only_ordered_transitions(string $from, string $to, bool $expected): void
    {
        $this->assertSame($expected, QuotationCatalog::canTransition($from, $to));
    }

    public function test_options_list_the_current_and_allowed_statuses(): void
    {
        $this->assertSame(['enviada', 'aceptada', 'rechazada'], array_keys(QuotationCatalog::options('enviada')));
        $this->assertSame(['aceptada', 'facturada'], array_keys(QuotationCatalog::options('facturada')));
    }
}
