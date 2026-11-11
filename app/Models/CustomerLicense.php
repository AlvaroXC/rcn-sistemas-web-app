<?php

namespace RcnSistemas\Models;

final class CustomerLicense
{
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $productName,
        public readonly string $licenseType,
        public readonly string $rfcScope,
        public readonly int $usersCount,
        public readonly ?string $serialNumber,
        public readonly ?string $version,
        public readonly ?string $expiresAt,
        public readonly ?string $notes,
        public readonly ?string $alias = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (int) ($data['customer_id'] ?? 0),
            trim((string) ($data['product_name'] ?? '')),
            trim((string) ($data['license_type'] ?? 'anual')),
            trim((string) ($data['rfc_scope'] ?? 'multi')),
            (int) ($data['users_count'] ?? 1),
            self::nullable($data['serial_number'] ?? null),
            self::nullable($data['version'] ?? null),
            self::nullable($data['expires_at'] ?? null),
            self::nullable($data['notes'] ?? null),
            self::nullable($data['alias'] ?? null)
        );
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
