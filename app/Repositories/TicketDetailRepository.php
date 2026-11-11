<?php

namespace RcnSistemas\Repositories;

interface TicketDetailRepository
{
    public function observations(int $ticketId): array;
    public function addObservation(int $ticketId, string $content, ?int $userId): bool;
    public function updateMessage(int $ticketId, string $message, ?int $userId): bool;
}
