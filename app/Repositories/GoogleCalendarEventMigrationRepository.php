<?php

namespace RcnSistemas\Repositories;

interface GoogleCalendarEventMigrationRepository
{
    /** @return list<int> */
    public function candidateIds(int $limit): array;

    /** @return array{total:int,pending:int,migrated:int,cleanup_pending:int,cleanup_failed:int,cleanup_failed_ticket_ids:list<int>} */
    public function summary(): array;

    public function updateLegacyCleanup(int $ticketId, string $status, ?string $error, bool $clearLegacyReference): bool;

    public function markNotApplicable(int $ticketId): bool;
}
