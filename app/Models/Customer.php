<?php

namespace RcnSistemas\Models;

final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly string $phone,
        public readonly string $businessName,
        public readonly string $customerType
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            trim((string) ($data['name'] ?? '')),
            ($data['email'] ?? null) !== '' ? ($data['email'] ?? null) : null,
            trim((string) ($data['phone'] ?? '')),
            trim((string) ($data['business_name'] ?? '')),
            trim((string) ($data['customer_type'] ?? 'persona_fisica'))
        );
    }
}
