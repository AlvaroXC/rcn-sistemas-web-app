<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;

final class TicketAssignmentService
{
    public function canAssign(string $role, int $targetUserId): bool
    {
        return trim($role) === 'admin' && $targetUserId > 0;
    }

    public function validate(string $role, int $targetUserId): void
    {
        if (!$this->canAssign($role, $targetUserId)) {
            throw new InvalidArgumentException('No tienes permisos para asignar este ticket.');
        }
    }
}
