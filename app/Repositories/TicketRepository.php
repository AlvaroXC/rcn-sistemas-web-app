<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Ticket;

interface TicketRepository
{
    public function create(array $data): ?Ticket;

    public function find(int $ticketId): ?Ticket;

    public function updateStatus(int $ticketId, string $status): bool;
}
