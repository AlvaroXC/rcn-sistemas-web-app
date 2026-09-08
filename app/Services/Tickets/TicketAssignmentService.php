<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;

final class TicketAssignmentService
{
    public function canAssign(string $role, int $targetUserId): bool
    {
        return in_array(trim($role), ['admin', 'agent'], true) && $targetUserId > 0;
    }

    public function validate(string $role, int $targetUserId): void
    {
        if (!$this->canAssign($role, $targetUserId)) {
            throw new InvalidArgumentException('Selecciona un responsable válido para este ticket.');
        }
    }
}
