<?php

namespace Tests\Unit\Services\Licenses;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Licenses\LicenseCatalog;

final class LicenseCatalogTest extends TestCase
{
    /** @return array<string, array{string, ?string, string}> */
    public static function statuses(): array
    {
        return [
            'tradicional sin vencimiento' => ['tradicional', null, 'vitalicia'],
            'tradicional ignora fecha' => ['tradicional', '2020-01-01', 'vitalicia'],
            'anual sin fecha' => ['anual', null, 'sin_fecha'],
            'vencida ayer' => ['anual', '2026-10-29', 'vencida'],
            'vence hoy' => ['anual', '2026-10-30', 'por_vencer'],
            'limite de la ventana' => ['mensual', '2026-12-29', 'por_vencer'],
            'fuera de la ventana' => ['anual', '2026-12-30', 'vigente'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_it_derives_the_status(string $type, ?string $expiresAt, string $expected): void
    {
        $this->assertSame($expected, LicenseCatalog::status($type, $expiresAt, '2026-10-30'));
    }

    /** @return array<string, array{string, ?string, string, string}> tipo, vencimiento actual, hoy, nuevo vencimiento */
    public static function renewals(): array
    {
        return [
            'anual anticipada conserva los dias restantes' => ['anual', '2026-11-06', '2026-11-01', '2027-11-06'],
            'anual con 29 de febrero en el periodo' => ['anual', '2027-03-10', '2027-02-20', '2028-03-09'],
            'anual el mismo dia del vencimiento' => ['anual', '2026-11-06', '2026-11-06', '2027-11-06'],
            'mensual anticipada' => ['mensual', '2026-11-20', '2026-11-15', '2026-12-20'],
            'anual vencida cuenta desde hoy' => ['anual', '2026-10-01', '2026-11-04', '2027-11-04'],
            'mensual sin fecha cuenta desde hoy' => ['mensual', null, '2026-11-04', '2026-12-04'],
        ];
    }

    #[DataProvider('renewals')]
    public function test_it_computes_the_next_expiration(string $type, ?string $expiresAt, string $today, string $expected): void
    {
        $this->assertSame($expected, LicenseCatalog::nextExpiration($type, $expiresAt, $today));
    }

    public function test_traditional_licenses_cannot_be_renewed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LicenseCatalog::nextExpiration('tradicional', null, '2026-11-04');
    }
}
