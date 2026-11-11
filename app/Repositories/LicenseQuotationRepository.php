<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\LicenseQuotation;

interface LicenseQuotationRepository
{
    public function find(int $quotationId): ?LicenseQuotation;

    public function findByFolio(string $folio): ?LicenseQuotation;

    /** @return list<LicenseQuotation> la más reciente primero */
    public function forLicense(int $licenseId): array;

    public function save(array $data): ?LicenseQuotation;

    public function delete(int $quotationId): bool;
}
