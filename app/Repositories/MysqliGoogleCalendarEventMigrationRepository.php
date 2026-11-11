<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RuntimeException;

final class MysqliGoogleCalendarEventMigrationRepository implements GoogleCalendarEventMigrationRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function candidateIds(int $limit): array
    {
        $limit = max(1, min(1000, $limit));
        $result = $this->connection->query('SELECT id FROM tickets WHERE google_calendar_migration_status = "pending" OR google_calendar_legacy_cleanup_status IN ("pending", "failed") ORDER BY id ASC LIMIT ' . $limit);
        if (!$result) throw new RuntimeException('No se pudieron consultar los tickets pendientes de migración.');
        $ids = [];
        while ($row = $result->fetch_assoc()) $ids[] = (int) $row['id'];
        return $ids;
    }

    public function summary(): array
    {
        $result = $this->connection->query('SELECT '
            . '(SELECT COUNT(*) FROM tickets WHERE appointment_date IS NOT NULL AND appointment_time IS NOT NULL) AS total, '
            . '(SELECT COUNT(*) FROM tickets WHERE appointment_date IS NOT NULL AND appointment_time IS NOT NULL AND google_calendar_migration_status = "pending") AS pending, '
            . '(SELECT COUNT(*) FROM tickets WHERE appointment_date IS NOT NULL AND appointment_time IS NOT NULL AND google_calendar_migration_status = "migrated") AS migrated, '
            . '(SELECT COUNT(*) FROM tickets WHERE google_calendar_legacy_cleanup_status = "pending") AS cleanup_pending, '
            . '(SELECT COUNT(*) FROM tickets WHERE google_calendar_legacy_cleanup_status = "failed") AS cleanup_failed');
        if (!$result) throw new RuntimeException('No se pudo generar el reporte de migración de Google Calendar.');
        $row = $result->fetch_assoc() ?: [];
        $failedResult = $this->connection->query('SELECT id FROM tickets WHERE google_calendar_legacy_cleanup_status = "failed" ORDER BY id ASC');
        if (!$failedResult) throw new RuntimeException('No se pudieron consultar los tickets con limpieza pendiente.');
        $failedIds = [];
        while ($failedRow = $failedResult->fetch_assoc()) $failedIds[] = (int) $failedRow['id'];
        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'migrated' => (int) ($row['migrated'] ?? 0),
            'cleanup_pending' => (int) ($row['cleanup_pending'] ?? 0),
            'cleanup_failed' => (int) ($row['cleanup_failed'] ?? 0),
            'cleanup_failed_ticket_ids' => $failedIds,
        ];
    }

    public function updateLegacyCleanup(int $ticketId, string $status, ?string $error, bool $clearLegacyReference): bool
    {
        if ($clearLegacyReference) {
            $statement = $this->connection->prepare('UPDATE tickets SET google_calendar_legacy_cleanup_status=?, google_calendar_legacy_cleanup_error=?, google_calendar_legacy_event_id=NULL, google_calendar_legacy_calendar_id=NULL, google_calendar_legacy_user_id=NULL WHERE id=?');
        } else {
            $statement = $this->connection->prepare('UPDATE tickets SET google_calendar_legacy_cleanup_status=?, google_calendar_legacy_cleanup_error=? WHERE id=?');
        }
        if (!$statement) throw new RuntimeException('No se pudo actualizar el estado de limpieza del evento anterior.');
        $statement->bind_param('ssi', $status, $error, $ticketId);
        return $statement->execute();
    }

    public function markNotApplicable(int $ticketId): bool
    {
        $statement = $this->connection->prepare('UPDATE tickets SET google_calendar_migration_status="not_applicable" WHERE id=?');
        if (!$statement) throw new RuntimeException('No se pudo actualizar el estado de migración del ticket.');
        $statement->bind_param('i', $ticketId);
        return $statement->execute();
    }
}
