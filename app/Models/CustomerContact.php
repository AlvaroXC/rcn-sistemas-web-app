<?php

namespace RcnSistemas\Models;

final class CustomerContact
{
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $name,
        public readonly string $phone,
        public readonly ?string $email
    ) {
    }

    public static function fromArray(array $data): self
    {
        $email = trim((string) ($data['email'] ?? ''));

        return new self((int) ($data['id'] ?? 0), (int) ($data['customer_id'] ?? 0), trim((string) ($data['name'] ?? '')), trim((string) ($data['phone'] ?? '')), $email !== '' ? $email : null);
    }
}
