<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Repositories\TicketRepository;

final class TicketService
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketWorkflow $workflow
    ) {
    }

    public function create(array $data): Ticket
    {
        if ((int) ($data['customer_id'] ?? 0) <= 0 || trim((string) ($data['message'] ?? '')) === '') {
            throw new InvalidArgumentException('El cliente y el mensaje son obligatorios.');
        }

        $data['status'] = $data['status'] ?? 'Nuevo';
        $this->workflow->validateStatus((string) $data['status']);
        $ticket = $this->tickets->create($data);

        if (!$ticket) {
            throw new InvalidArgumentException('No se pudo crear el ticket.');
        }

        return $ticket;
    }

    public function changeStatus(Ticket $ticket, string $status): bool
    {
        if (!$this->workflow->canEdit(['status' => $ticket->status])) {
            throw new InvalidArgumentException('Los tickets cerrados ya no admiten cambios.');
        }

        $this->workflow->validateStatus($status);

        return $this->tickets->updateStatus($ticket->id, $status);
    }
}
