<?php

namespace RcnSistemas\Models;

final class AdminUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $displayName,
        public readonly string $role,
        public readonly bool $active
    ) {}

    public static function fromArray(array $data): self
    {
        return new self((int) ($data['id'] ?? 0), trim((string) ($data['username'] ?? '')), trim((string) ($data['display_name'] ?? '')), trim((string) ($data['role'] ?? 'admin')), (int) ($data['active'] ?? 0) === 1);
    }
}
