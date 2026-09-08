<?php

namespace RcnSistemas\Models;

final class Ticket
{
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $consultationType,
        public readonly string $status,
        public readonly string $originType,
        public readonly string $message,
        public readonly ?int $assignedUserId = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (int) ($data['customer_id'] ?? 0),
            trim((string) ($data['consultation_type'] ?? '')),
            trim((string) ($data['status'] ?? 'Nuevo')),
            trim((string) ($data['origin_type'] ?? 'contact')),
            trim((string) ($data['message'] ?? '')),
            isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null
        );
    }
}
