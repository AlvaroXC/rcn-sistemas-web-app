<?php

namespace RcnSistemas\Repositories;

interface TicketCalendarRepository
{
    public function updateCalendarSync(int $ticketId, ?string $eventId, ?string $eventLink, string $status, ?string $error = null): bool;
}
