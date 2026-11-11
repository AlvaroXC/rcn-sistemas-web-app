<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Services\Licenses\LicenseListFilter;

interface CustomerLicenseRepository
{
    public function find(int $licenseId): ?CustomerLicense;

    public function findBySerialNumber(string $serialNumber): ?CustomerLicense;

    /** @return list<CustomerLicense> */
    public function forCustomer(int $customerId): array;

    public function count(LicenseListFilter $filter): int;

    /** @return list<array<string, mixed>> */
    public function list(LicenseListFilter $filter, int $limit, int $offset): array;

    /** @return list<int> años en los que vence al menos una licencia, ascendente */
    public function expirationYears(): array;

    public function save(array $data): ?CustomerLicense;

    /** Cambia el vencimiento solo si sigue siendo $expectedExpiresAt; devuelve false si otra operación lo modificó. */
    public function renew(int $licenseId, ?string $expectedExpiresAt, string $newExpiresAt, ?string $notes): bool;

    public function delete(int $licenseId): bool;
}
