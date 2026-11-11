<?php

namespace Tests\Unit\Services\Licenses;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Customer;
use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Services\Licenses\CustomerLicenseService;
use RcnSistemas\Services\Licenses\LicenseListFilter;

final class CustomerLicenseServiceTest extends TestCase
{
    private array $data = ['customer_id' => 7, 'product_name' => ' CONTPAQi Contabilidad ', 'license_type' => 'anual', 'rfc_scope' => 'multi', 'users_count' => '3', 'serial_number' => ' ABC-1 ', 'version' => '', 'expires_at' => '2027-01-15', 'notes' => ''];

    public function test_it_normalizes_and_saves_a_license(): void
    {
        $repository = $this->licenses();
        $license = $this->service($repository)->save($this->data);

        $this->assertSame('CONTPAQi Contabilidad', $license->productName);
        $this->assertSame(3, $license->usersCount);
        $this->assertSame('ABC-1', $license->serialNumber);
        $this->assertNull($repository->saved['version']);
    }

    public function test_alias_is_optional_and_trimmed(): void
    {
        $repository = $this->licenses();
        $this->service($repository)->save($this->data);
        $this->assertNull($repository->saved['alias']);

        $license = $this->service($repository)->save(['alias' => '  Oficina centro  '] + $this->data);
        $this->assertSame('Oficina centro', $license->alias);
    }

    public function test_it_rejects_a_long_alias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service($this->licenses())->save(['alias' => str_repeat('a', 101)] + $this->data);
    }

    public function test_traditional_licenses_never_store_an_expiration(): void
    {
        $repository = $this->licenses();
        $this->service($repository)->save(['license_type' => 'tradicional'] + $this->data);

        $this->assertNull($repository->saved['expires_at']);
    }

    public function test_it_ignores_a_submitted_activation_date(): void
    {
        $repository = $this->licenses();
        $this->service($repository)->save(['activated_at' => '2099-12-31'] + $this->data);

        $this->assertArrayNotHasKey('activated_at', $repository->saved);
    }

    public function test_it_renews_keeping_the_remaining_days_and_records_it_in_notes(): void
    {
        $repository = $this->licenses(new CustomerLicense(5, 7, 'Nóminas', 'anual', 'mono', 2, 'ABC-1', '15.0', '2026-11-06', 'Cliente frecuente'));
        $license = $this->service($repository)->renew(5, '2026-11-06', '2026-11-01');

        $this->assertSame('2027-11-06', $license->expiresAt);
        $this->assertSame("Cliente frecuente\nRenovada el 01/11/2026: vencía el 06/11/2026, ahora vence el 06/11/2027.", $license->notes);
        $this->assertSame(['Nóminas', 'anual', 'mono', 2, 'ABC-1', '15.0'], [$license->productName, $license->licenseType, $license->rfcScope, $license->usersCount, $license->serialNumber, $license->version]);
        $this->assertSame('2026-11-06', $repository->renewed['expected']);
    }

    public function test_renewing_a_license_without_date_counts_from_today(): void
    {
        $repository = $this->licenses(new CustomerLicense(5, 7, 'Nóminas', 'mensual', 'multi', 1, null, null, null, null));
        $license = $this->service($repository)->renew(5, '', '2026-11-04');

        $this->assertSame('2026-12-04', $license->expiresAt);
        $this->assertSame('Renovada el 04/11/2026: no tenía vencimiento, ahora vence el 04/12/2026.', $license->notes);
    }

    /** @return array<string, array{?CustomerLicense, string}> */
    public static function rejectedRenewals(): array
    {
        return [
            'doble envio: el vencimiento ya cambio' => [new CustomerLicense(5, 7, 'Nóminas', 'anual', 'multi', 1, null, null, '2027-11-06', null), '2026-11-06'],
            'tradicional' => [new CustomerLicense(5, 7, 'Nóminas', 'tradicional', 'multi', 1, null, null, null, null), ''],
            'inexistente' => [null, '2026-11-06'],
        ];
    }

    #[DataProvider('rejectedRenewals')]
    public function test_it_rejects_invalid_renewals(?CustomerLicense $existing, string $expected): void
    {
        $repository = $this->licenses($existing);
        try {
            $this->service($repository)->renew(5, $expected, '2026-11-01');
            $this->fail('Se esperaba que la renovación fuera rechazada.');
        } catch (InvalidArgumentException) {
            $this->assertSame([], $repository->renewed);
        }
    }

    public function test_it_rejects_a_renewal_that_lost_the_race(): void
    {
        $repository = $this->licenses(new CustomerLicense(5, 7, 'Nóminas', 'anual', 'multi', 1, null, null, '2026-11-06', null));
        $repository->renewSucceeds = false;

        $this->expectException(InvalidArgumentException::class);
        $this->service($repository)->renew(5, '2026-11-06', '2026-11-01');
    }

    public function test_it_accepts_keeping_the_same_serial_when_editing(): void
    {
        $repository = $this->licenses(new CustomerLicense(5, 7, 'Nóminas', 'anual', 'multi', 1, 'ABC-1', null, '2027-01-15', null));
        $this->service($repository)->save(['id' => 5] + $this->data);

        $this->assertSame(5, $repository->saved['id']);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidData(): array
    {
        return [
            'sin sistema' => [['product_name' => '  ']],
            'tipo invalido' => [['license_type' => 'semanal']],
            'rfc invalido' => [['rfc_scope' => 'varios']],
            'sin usuarios' => [['users_count' => '0']],
            'usuarios no numericos' => [['users_count' => 'dos']],
            'anual sin vencimiento' => [['expires_at' => '']],
            'mensual sin vencimiento' => [['license_type' => 'mensual', 'expires_at' => '']],
            'fecha inexistente' => [['expires_at' => '2027-02-30']],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_it_rejects_invalid_data(array $override): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service($this->licenses())->save($override + $this->data);
    }

    public function test_it_rejects_a_missing_customer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service($this->licenses())->save(['customer_id' => 99] + $this->data);
    }

    public function test_it_rejects_a_serial_used_by_another_license(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $repository = $this->licenses(new CustomerLicense(5, 8, 'Nóminas', 'anual', 'multi', 1, 'ABC-1', null, '2027-01-15', null));
        $this->service($repository)->save($this->data);
    }

    private function service(CustomerLicenseRepository $licenses): CustomerLicenseService
    {
        $customers = new class implements CustomerRepository {
            public function findByPhone(string $phone): ?Customer { return null; }
            public function find(int $customerId): ?Customer { return $customerId === 7 ? new Customer(7, 'Cliente', null, '9999999999', '', 'persona_fisica') : null; }
            public function findByRfc(string $rfc): ?Customer { return null; }
            public function save(array $data): ?Customer { return null; }
        };

        return new CustomerLicenseService($licenses, $customers);
    }

    private function licenses(?CustomerLicense $existing = null): CustomerLicenseRepository
    {
        return new class($existing) implements CustomerLicenseRepository {
            public array $saved = [];
            public array $renewed = [];
            public bool $renewSucceeds = true;
            public function __construct(private ?CustomerLicense $existing) {}
            public function find(int $licenseId): ?CustomerLicense { return $this->existing; }
            public function findBySerialNumber(string $serialNumber): ?CustomerLicense { return $this->existing; }
            public function forCustomer(int $customerId): array { return []; }
            public function count(LicenseListFilter $filter): int { return 0; }
            public function list(LicenseListFilter $filter, int $limit, int $offset): array { return []; }
            public function expirationYears(): array { return []; }
            public function save(array $data): ?CustomerLicense { $this->saved = $data; return CustomerLicense::fromArray($data); }
            public function renew(int $licenseId, ?string $expectedExpiresAt, string $newExpiresAt, ?string $notes): bool
            {
                if (!$this->renewSucceeds) return false;
                $this->renewed = ['expected' => $expectedExpiresAt, 'expires_at' => $newExpiresAt, 'notes' => $notes];
                $e = $this->existing;
                $this->existing = new CustomerLicense($e->id, $e->customerId, $e->productName, $e->licenseType, $e->rfcScope, $e->usersCount, $e->serialNumber, $e->version, $newExpiresAt, $notes);
                return true;
            }
            public function delete(int $licenseId): bool { return true; }
        };
    }
}
