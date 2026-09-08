<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;

final class TicketWorkflow
{
    public const CLOSED = 'Cerrado';
    private const STATUSES = ['Nuevo', 'Agendado', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'];

    public function statuses(): array { return self::STATUSES; }

    public function validateStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('El estado del ticket no es válido.');
        }
    }

    public function canEdit(array $ticket): bool
    {
        return (string) ($ticket['status'] ?? '') !== self::CLOSED;
    }
}
