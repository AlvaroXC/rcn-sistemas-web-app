<?php

namespace Tests\Unit\Services\Licenses;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Models\LicenseQuotation;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\LicenseQuotationRepository;
use RcnSistemas\Services\Licenses\LicenseListFilter;
use RcnSistemas\Services\Licenses\LicenseQuotationService;

final class LicenseQuotationServiceTest extends TestCase
{
    private array $data = ['license_id' => 5, 'folio' => ' COT-1520 ', 'quoted_at' => '2026-10-01', 'notes' => ''];

    public function test_a_new_quotation_starts_as_sent(): void
    {
        $repository = $this->quotations();
        $quotation = $this->service($repository)->save(['status' => 'facturada'] + $this->data);

        $this->assertSame(['COT-1520', 'enviada', 5, null], [$quotation->folio, $quotation->status, $quotation->licenseId, $quotation->notes]);
    }

    public function test_it_rejects_a_repeated_folio_ignoring_case_and_spaces(): void
    {
        $repository = $this->quotations(new LicenseQuotation(9, 6, 'COT-1520', '2026-09-01', 'enviada', null));

        $this->expectExceptionMessage('Ya existe una cotización registrada con el folio COT-1520.');
        $this->service($repository)->save(['folio' => ' cot-1520 '] + $this->data);
    }

    public function test_editing_keeps_its_own_folio_and_license(): void
    {
        $repository = $this->quotations(new LicenseQuotation(9, 5, 'COT-1520', '2026-10-01', 'enviada', null));
        $quotation = $this->service($repository)->save(['id' => 9, 'license_id' => 77, 'status' => 'aceptada'] + $this->data);

        $this->assertSame(['aceptada', 5], [$quotation->status, $quotation->licenseId]);
    }

    public function test_it_requires_a_reason_to_reject(): void
    {
        $repository = $this->quotations(new LicenseQuotation(9, 5, 'COT-1520', '2026-10-01', 'enviada', null));

        $this->expectExceptionMessage('motivo');
        $this->service($repository)->save(['id' => 9, 'status' => 'rechazada', 'notes' => '   '] + $this->data);
    }

    public function test_it_saves_a_rejection_with_its_reason(): void
    {
        $repository = $this->quotations(new LicenseQuotation(9, 5, 'COT-1520', '2026-10-01', 'enviada', null));
        $quotation = $this->service($repository)->save(['id' => 9, 'status' => 'rechazada', 'notes' => 'Le pareció caro'] + $this->data);

        $this->assertSame(['rechazada', 'Le pareció caro'], [$quotation->status, $quotation->notes]);
    }

    public function test_it_keeps_the_reason_of_a_rejected_quotation(): void
    {
        $repository = $this->quotations(new LicenseQuotation(9, 5, 'COT-1520', '2026-10-01', 'rechazada', 'Le pareció caro'));

        $this->expectException(InvalidArgumentException::class);
        $this->service($repository)->save(['id' => 9, 'status' => 'rechazada', 'notes' => ''] + $this->data);
    }

    /** @return array<string, array{string, string}> */
    public static function skippedSteps(): array
    {
        return ['enviada a facturada' => ['enviada', 'facturada'], 'rechazada a facturada' => ['rechazada', 'facturada'], 'rechazada a aceptada' => ['rechazada', 'aceptada']];
    }

    #[DataProvider('skippedSteps')]
    public function test_it_rejects_skipped_steps(string $from, string $to): void
    {
        $repository = $this->quotations(new LicenseQuotation(9, 5, 'COT-1520', '2026-10-01', $from, 'Motivo'));
        try {
            $this->service($repository)->save(['id' => 9, 'status' => $to, 'notes' => 'Motivo'] + $this->data);
            $this->fail('Se esperaba que el cambio de estado fuera rechazado.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('solo puede cambiar a', $exception->getMessage());
            $this->assertStringNotContainsString(ucfirst($from) . ',', $exception->getMessage());
            $this->assertSame([], $repository->saved);
        }
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidData(): array
    {
        return [
            'sin folio' => [['folio' => '  ']],
            'folio largo' => [['folio' => str_repeat('A', 51)]],
            'fecha invalida' => [['quoted_at' => '2026-02-30']],
            'sin fecha' => [['quoted_at' => '']],
            'licencia inexistente' => [['license_id' => 99]],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_it_rejects_invalid_data(array $override): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service($this->quotations())->save($override + $this->data);
    }

    private function service(LicenseQuotationRepository $quotations): LicenseQuotationService
    {
        $licenses = new class implements CustomerLicenseRepository {
            public function find(int $licenseId): ?CustomerLicense { return $licenseId === 5 ? new CustomerLicense(5, 7, 'Nóminas', 'anual', 'multi', 1, null, null, '2026-11-18', null) : null; }
            public function findBySerialNumber(string $serialNumber): ?CustomerLicense { return null; }
            public function forCustomer(int $customerId): array { return []; }
            public function count(LicenseListFilter $filter): int { return 0; }
            public function list(LicenseListFilter $filter, int $limit, int $offset): array { return []; }
            public function expirationYears(): array { return []; }
            public function save(array $data): ?CustomerLicense { return null; }
            public function renew(int $licenseId, ?string $expectedExpiresAt, string $newExpiresAt, ?string $notes): bool { return false; }
            public function delete(int $licenseId): bool { return false; }
        };

        return new LicenseQuotationService($quotations, $licenses);
    }

    private function quotations(?LicenseQuotation $existing = null): LicenseQuotationRepository
    {
        return new class($existing) implements LicenseQuotationRepository {
            public array $saved = [];
            public function __construct(private readonly ?LicenseQuotation $existing) {}
            public function find(int $quotationId): ?LicenseQuotation { return $this->existing?->id === $quotationId ? $this->existing : null; }
            // Igual que la intercalación _ci de MySQL: no distingue mayúsculas.
            public function findByFolio(string $folio): ?LicenseQuotation { return $this->existing && strcasecmp($this->existing->folio, $folio) === 0 ? $this->existing : null; }
            public function forLicense(int $licenseId): array { return []; }
            public function save(array $data): ?LicenseQuotation { $this->saved = $data; return LicenseQuotation::fromArray($data); }
            public function delete(int $quotationId): bool { return true; }
        };
    }
}
