<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliTicketCalendarRepository implements TicketCalendarRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function updateCalendarSync(int $ticketId, ?string $eventId, ?string $eventLink, string $status, ?string $error = null): bool
    {
        $statement = $this->connection->prepare('UPDATE tickets SET google_calendar_event_id=?, google_calendar_event_link=?, google_calendar_sync_status=?, google_calendar_sync_error=?, google_calendar_synced_at=CASE WHEN ? = "synced" THEN CURRENT_TIMESTAMP ELSE google_calendar_synced_at END WHERE id=?');
        $statement->bind_param('sssssi', $eventId, $eventLink, $status, $error, $status, $ticketId);
        return $statement->execute();
    }
}
