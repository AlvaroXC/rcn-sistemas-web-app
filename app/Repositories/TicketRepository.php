<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Ticket;

interface TicketRepository
{
    public function create(array $data): ?Ticket;

    public function find(int $ticketId): ?Ticket;

    public function updateStatus(int $ticketId, string $status): bool;

    public function updatePrice(int $ticketId, array $price, int $userId): bool;

    public function updateSchedule(int $ticketId, ?string $date, ?string $time, ?string $serviceDate, ?string $serviceStart, ?string $serviceEnd): bool;

    public function updateAssignee(int $ticketId, int $userId, int $actorId): bool;
}
