<?php

namespace Tests\Unit\Services\Licenses\Import;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use RcnSistemas\Services\Licenses\Import\RenewalSpreadsheetMapper;

final class RenewalSpreadsheetMapperTest extends TestCase
{
    private const ANUAL_HEADER = ['A' => 'Producto', 'B' => 'Serie', 'C' => 'Versión', 'D' => 'Fecha caducidad', 'E' => 'Usuarios', 'F' => 'RFC', 'G' => 'Nombre empresa', 'H' => 'Contacto', 'L' => 'Contacto', 'M' => 'Tel 1', 'N' => 'Correo contacto'];
    private const TRAD_HEADER = ['A' => 'Sistema', 'B' => 'Serie', 'C' => 'versión', 'D' => 'Cantidad usuarios', 'E' => 'RFC', 'F' => 'Nombre empresa', 'G' => 'Contacto', 'H' => 'Teléfono empresa', 'I' => 'Correo empresa'];

    public function test_it_groups_licenses_by_rfc_and_maps_the_columns(): void
    {
        $sheets = [
            'Anual' => [
                1 => self::ANUAL_HEADER,
                2 => ['A' => 'CONTABILIDAD', 'B' => 'abc1', 'C' => 'ANUAL', 'D' => '09/04/2025', 'E' => '2', 'F' => 'amo050128jc3', 'G' => 'AUTOMOTRIZ  DE MONTEJO', 'H' => 'Vendedor', 'L' => 'JORGE GONZALEZ  .', 'M' => '988-52-52', 'N' => 'Jorge@Example.com'],
                3 => ['A' => 'XML EN LINEA +', 'B' => 'ABC2', 'C' => 'ANUAL', 'D' => '46242', 'E' => '100', 'F' => 'AMO050128JC3', 'G' => 'AUTOMOTRIZ DE MONTEJO', 'M' => '9885252'],
                4 => ['A' => 'NOMINAS', 'B' => 'ABC3', 'C' => 'ANUAL', 'D' => '01/02/2026', 'E' => '1', 'F' => 'BADW890416AG6', 'G' => 'WILMA BARRERA', 'N' => 'viejo@hotmail.com.old'],
            ],
            'Tradicionales' => [1 => self::TRAD_HEADER],
        ];

        $customers = (new RenewalSpreadsheetMapper())->map($sheets, 'Renovaciones.xlsx');

        $this->assertSame(['AMO050128JC3', 'BADW890416AG6'], array_keys($customers));
        $company = $customers['AMO050128JC3'];
        $this->assertSame(['rfc' => 'AMO050128JC3', 'name' => 'JORGE GONZALEZ', 'business_name' => 'AUTOMOTRIZ DE MONTEJO', 'customer_type' => 'persona_moral', 'email' => 'jorge@example.com', 'phone' => '9885252'], $company['customer']);
        $this->assertSame(['serial_number' => 'ABC1', 'product_name' => 'CONTPAQi® Contabilidad', 'license_type' => 'anual', 'version' => null, 'users_count' => 2, 'expires_at' => '2025-04-09', 'notes' => 'Importada desde Renovaciones.xlsx (hoja Anual, fila 2).'], $company['licenses'][0]);
        $this->assertSame('CONTPAQi® XML En Línea+', $company['licenses'][1]['product_name']);
        $this->assertSame('2026-08-08', $company['licenses'][1]['expires_at']);
        $this->assertSame('persona_fisica', $customers['BADW890416AG6']['customer']['customer_type']);
        $this->assertSame('WILMA BARRERA', $customers['BADW890416AG6']['customer']['name']);
        $this->assertNull($customers['BADW890416AG6']['customer']['email']);
    }

    public function test_it_merges_repeated_traditional_rows_and_skips_repeated_headers(): void
    {
        $sheets = ['Anual' => [1 => self::ANUAL_HEADER], 'Tradicionales' => [
            1 => self::TRAD_HEADER,
            2 => ['A' => 'CONTABILIDAD', 'B' => 'T1', 'C' => '18.0.0', 'D' => '6', 'E' => 'MVE100705ES0', 'F' => 'MEDINA VEGA', 'G' => 'DIANA', 'H' => '2857195'],
            12 => self::TRAD_HEADER,
            17 => ['A' => 'CONTABILIDAD', 'B' => 'T1', 'C' => '18.0.0', 'D' => '6', 'E' => 'MVE100705ES0', 'F' => 'MEDINA VEGA', 'G' => 'DIANA', 'H' => '2857195', 'I' => 'diana@live.com.mx'],
        ]];

        $customers = (new RenewalSpreadsheetMapper())->map($sheets, 'x.xlsx');

        $this->assertCount(1, $customers['MVE100705ES0']['licenses']);
        $license = $customers['MVE100705ES0']['licenses'][0];
        $this->assertSame(['tradicional', '18.0.0', null, 6], [$license['license_type'], $license['version'], $license['expires_at'], $license['users_count']]);
        $this->assertSame('diana@live.com.mx', $customers['MVE100705ES0']['customer']['email']);
    }

    public function test_it_reports_invalid_values_with_defaults(): void
    {
        $mapper = new RenewalSpreadsheetMapper();
        $customers = $mapper->map(['Anual' => [
            1 => self::ANUAL_HEADER,
            2 => ['A' => 'NOMINAS', 'B' => 'S1', 'C' => 'ANUAL', 'D' => '31/02/2026', 'E' => 'dos', 'F' => 'CILL8211177M0', 'G' => 'LUIS'],
            3 => ['A' => 'NOMINAS', 'B' => 'S2', 'C' => 'ANUAL', 'D' => '01/01/2026', 'E' => '1', 'F' => 'NO-ES-RFC', 'G' => 'X'],
        ], 'Tradicionales' => [1 => self::TRAD_HEADER]], 'x.xlsx');

        $license = $customers['CILL8211177M0']['licenses'][0];
        $this->assertSame([1, null], [$license['users_count'], $license['expires_at']]);
        $this->assertCount(1, $customers);
        $this->assertCount(3, $mapper->warnings());
    }

    public function test_it_rejects_a_sheet_whose_columns_moved(): void
    {
        $this->expectException(RuntimeException::class);
        (new RenewalSpreadsheetMapper())->map(['Anual' => [1 => ['A' => 'Serie'] + self::ANUAL_HEADER], 'Tradicionales' => [1 => self::TRAD_HEADER]], 'x.xlsx');
    }
}
