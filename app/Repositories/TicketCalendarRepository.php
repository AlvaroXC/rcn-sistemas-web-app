<?php

namespace RcnSistemas\Repositories;

interface TicketCalendarRepository
{
    public function updateCalendarSync(
        int $ticketId,
        ?string $eventId,
        ?string $eventLink,
        string $status,
        ?string $error = null,
        ?string $targetCalendarId = null,
        ?string $migrationStatus = null,
        ?string $legacyCleanupStatus = null,
        ?string $legacyCleanupError = null,
        ?string $legacyEventId = null,
        ?string $legacyCalendarId = null,
        ?int $legacyUserId = null
    ): bool;
}
