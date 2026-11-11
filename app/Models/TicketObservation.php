<?php

namespace RcnSistemas\Models;

final class TicketObservation
{
    public function __construct(public readonly int $id, public readonly int $ticketId, public readonly string $content, public readonly ?int $userId, public readonly ?string $createdAt)
    {
    }

    public static function fromArray(array $data): self
    {
        return new self((int) ($data['id'] ?? 0), (int) ($data['ticket_id'] ?? 0), trim((string) ($data['content'] ?? '')), isset($data['admin_user_id']) ? (int) $data['admin_user_id'] : null, $data['created_at'] ?? null);
    }
}
