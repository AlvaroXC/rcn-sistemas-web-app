<?php

namespace RcnSistemas\Services\Licenses;

use DateTimeImmutable;
use InvalidArgumentException;
use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\CustomerRepository;

final class CustomerLicenseService
{
    public function __construct(
        private readonly CustomerLicenseRepository $licenses,
        private readonly CustomerRepository $customers
    ) {
    }

    public function save(array $data): CustomerLicense
    {
        $licenseId = (int) ($data['id'] ?? 0);
        $customerId = (int) ($data['customer_id'] ?? 0);
        if (!$this->customers->find($customerId)) {
            throw new InvalidArgumentException('El cliente de la licencia no existe.');
        }

        $productName = trim((string) ($data['product_name'] ?? ''));
        if ($productName === '' || mb_strlen($productName) > 150) {
            throw new InvalidArgumentException('Indica el sistema de la licencia (máximo 150 caracteres).');
        }

        $licenseType = (string) ($data['license_type'] ?? '');
        if (!array_key_exists($licenseType, LicenseCatalog::types())) {
            throw new InvalidArgumentException('Selecciona un tipo de licencia válido.');
        }

        $rfcScope = (string) ($data['rfc_scope'] ?? '');
        if (!array_key_exists($rfcScope, LicenseCatalog::rfcScopes())) {
            throw new InvalidArgumentException('Selecciona si la licencia es mono-RFC o multi-RFC.');
        }

        $usersCount = filter_var($data['users_count'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($usersCount === false) {
            throw new InvalidArgumentException('El número de usuarios debe ser al menos 1.');
        }

        $serialNumber = $this->optional($data['serial_number'] ?? null, 100, 'El número de serie');
        $alias = $this->optional($data['alias'] ?? null, 100, 'El alias');
        $version = $this->optional($data['version'] ?? null, 30, 'La versión');
        $notes = $this->optional($data['notes'] ?? null, 5000, 'Las notas');
        $expiresAt = $licenseType === 'tradicional' ? null : $this->date($data['expires_at'] ?? null, 'La fecha de vencimiento');

        if ($licenseType !== 'tradicional' && $expiresAt === null) {
            throw new InvalidArgumentException('Las licencias anuales y mensuales requieren fecha de vencimiento.');
        }

        if ($serialNumber !== null) {
            $existing = $this->licenses->findBySerialNumber($serialNumber);
            if ($existing && $existing->id !== $licenseId) {
                throw new InvalidArgumentException('Ya existe una licencia registrada con ese número de serie.');
            }
        }

        $license = $this->licenses->save([
            'id' => $licenseId,
            'customer_id' => $customerId,
            'product_name' => $productName,
            'alias' => $alias,
            'license_type' => $licenseType,
            'rfc_scope' => $rfcScope,
            'users_count' => $usersCount,
            'serial_number' => $serialNumber,
            'version' => $version,
            'expires_at' => $expiresAt,
            'notes' => $notes,
        ]);

        if (!$license) {
            throw new InvalidArgumentException('No se pudo guardar la licencia.');
        }

        return $license;
    }

    /**
     * Renueva una licencia anual o mensual. $expectedExpiresAt es el vencimiento que vio el usuario:
     * si ya cambió (doble clic, formulario reenviado u otra persona), la renovación se rechaza.
     */
    public function renew(int $licenseId, ?string $expectedExpiresAt, string $today): CustomerLicense
    {
        $license = $this->licenses->find($licenseId);
        if (!$license) {
            throw new InvalidArgumentException('La licencia no existe.');
        }
        $expectedExpiresAt = trim((string) $expectedExpiresAt) ?: null;
        if ($expectedExpiresAt !== $license->expiresAt) {
            throw new InvalidArgumentException('La licencia ya fue modificada; revisa la fecha antes de renovar.');
        }
        $newExpiresAt = LicenseCatalog::nextExpiration($license->licenseType, $license->expiresAt, $today);

        $format = static fn (string $date): string => (new DateTimeImmutable($date))->format('d/m/Y');
        $previous = $license->expiresAt ? 'vencía el ' . $format($license->expiresAt) : 'no tenía vencimiento';
        $line = 'Renovada el ' . $format($today) . ': ' . $previous . ', ahora vence el ' . $format($newExpiresAt) . '.';
        $notes = $license->notes !== null ? $license->notes . "\n" . $line : $line;

        if (!$this->licenses->renew($license->id, $license->expiresAt, $newExpiresAt, $notes)) {
            throw new InvalidArgumentException('La licencia ya fue modificada; revisa la fecha antes de renovar.');
        }

        return $this->licenses->find($license->id) ?? $license;
    }

    private function optional(mixed $value, int $maxLength, string $field): ?string
    {
        $value = trim((string) ($value ?? ''));
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException($field . ' supera el máximo de ' . $maxLength . ' caracteres.');
        }

        return $value !== '' ? $value : null;
    }

    private function date(mixed $value, string $field): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException($field . ' no es válida.');
        }

        return $value;
    }
}
