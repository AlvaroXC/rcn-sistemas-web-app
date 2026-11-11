<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliTicketCalendarRepository implements TicketCalendarRepository
{
    public function __construct(private readonly mysqli $connection) {}

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
    ): bool {
        $statement = $this->connection->prepare('UPDATE tickets SET google_calendar_event_id=?, google_calendar_event_link=?, google_calendar_sync_status=?, google_calendar_sync_error=?, google_calendar_synced_at=CASE WHEN ? = "synced" THEN CURRENT_TIMESTAMP ELSE google_calendar_synced_at END, google_calendar_target_id=?, google_calendar_migration_status=?, google_calendar_legacy_cleanup_status=?, google_calendar_legacy_cleanup_error=?, google_calendar_legacy_event_id=?, google_calendar_legacy_calendar_id=?, google_calendar_legacy_user_id=? WHERE id=?');
        $statement->bind_param('sssssssssssii', $eventId, $eventLink, $status, $error, $status, $targetCalendarId, $migrationStatus, $legacyCleanupStatus, $legacyCleanupError, $legacyEventId, $legacyCalendarId, $legacyUserId, $ticketId);
        return $statement->execute();
    }
}
