<?php

namespace RcnSistemas\Services\Google;

use Google\Service\Exception as GoogleServiceException;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Models\GoogleCalendarIntegration;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\GoogleCalendarEventMigrationRepository;
use RcnSistemas\Repositories\GoogleCalendarIntegrationRepository;
use RcnSistemas\Repositories\TicketRepository;
use RuntimeException;
use Throwable;

final class GoogleCalendarEventMigrationService
{
    public function __construct(
        private readonly GoogleCalendarEventMigrationRepository $migration,
        private readonly TicketRepository $tickets,
        private readonly GoogleCalendarIntegrationRepository $integrations,
        private readonly GoogleCalendarConnectionRepository $legacyConnections,
        private readonly GoogleCalendarAppointmentSyncService $sync,
        private readonly GoogleCalendarGateway $calendar
    ) {}

    /** @return array{processed:int,migrated:int,failed:int,legacy_cleaned:int,legacy_cleanup_failed:int,report:array<string,mixed>} */
    public function run(int $limit = 100): array
    {
        $integration = $this->integrations->findShared();
        if (!$integration || $integration->status !== 'connected') {
            throw new RuntimeException('Conecta el calendario común antes de ejecutar la migración.');
        }

        $counts = ['processed' => 0, 'migrated' => 0, 'failed' => 0, 'legacy_cleaned' => 0, 'legacy_cleanup_failed' => 0];
        foreach ($this->migration->candidateIds($limit) as $ticketId) {
            $counts['processed']++;
            $ticket = $this->tickets->find($ticketId);
            if (!$ticket) {
                $counts['failed']++;
                continue;
            }

            $this->sync->sync($ticket);
            $updated = $this->tickets->find($ticketId);
            if ($updated && (!$updated->appointmentDate || !$updated->appointmentTime)) {
                $this->cleanupLegacyEvent($ticketId, $updated->googleCalendarLegacyEventId, $updated->googleCalendarLegacyCalendarId, $updated->googleCalendarLegacyUserId, $integration, $counts);
                $this->migration->markNotApplicable($ticketId);
                continue;
            }
            if (!$updated || $updated->googleCalendarSyncStatus !== 'synced' || $updated->googleCalendarTargetId !== $integration->calendarId) {
                $counts['failed']++;
                continue;
            }
            $counts['migrated']++;

            if ($updated->googleCalendarLegacyEventId && $updated->googleCalendarLegacyCleanupStatus !== 'cleaned') {
                $this->cleanupLegacyEvent($ticketId, $updated->googleCalendarLegacyEventId, $updated->googleCalendarLegacyCalendarId, $updated->googleCalendarLegacyUserId, $integration, $counts);
            }
        }

        $counts['report'] = $this->migration->summary();
        return $counts;
    }

    /** @return array{total:int,pending:int,migrated:int,cleanup_pending:int,cleanup_failed:int,cleanup_failed_ticket_ids:list<int>} */
    public function report(): array
    {
        return $this->migration->summary();
    }

    private function cleanupLegacyEvent(int $ticketId, ?string $eventId, ?string $calendarId, ?int $userId, GoogleCalendarIntegration $shared, array &$counts): void
    {
        if (!$eventId) {
            $this->migration->updateLegacyCleanup($ticketId, 'cleaned', null, true);
            return;
        }
        try {
            $legacy = $this->legacyConnection($calendarId, $userId, $shared);
            if (!$legacy) throw new RuntimeException('No hay una conexión anterior disponible para limpiar el evento.');
            $this->calendar->deleteEvent($legacy, $eventId);
            $this->migration->updateLegacyCleanup($ticketId, 'cleaned', null, true);
            $counts['legacy_cleaned']++;
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() === 404) {
                $this->migration->updateLegacyCleanup($ticketId, 'cleaned', null, true);
                $counts['legacy_cleaned']++;
                return;
            }
            $this->migration->updateLegacyCleanup($ticketId, 'failed', 'Google Calendar no pudo eliminar el evento anterior.', false);
            $counts['legacy_cleanup_failed']++;
        } catch (Throwable) {
            $this->migration->updateLegacyCleanup($ticketId, 'failed', 'No se pudo eliminar el evento anterior. Revisa la conexión anterior y vuelve a ejecutar la migración.', false);
            $counts['legacy_cleanup_failed']++;
        }
    }

    private function legacyConnection(?string $calendarId, ?int $userId, GoogleCalendarIntegration $shared): GoogleCalendarConnection|GoogleCalendarIntegration|null
    {
        if ($userId) {
            $connection = $this->legacyConnections->findByUserId($userId);
            if ($connection) {
                return new GoogleCalendarConnection(
                    $connection->id,
                    $connection->userId,
                    $connection->googleUserId,
                    $connection->googleEmail,
                    $connection->accessTokenEncrypted,
                    $connection->refreshTokenEncrypted,
                    $connection->tokenExpiresAt,
                    $calendarId ?: $connection->calendarId,
                    $connection->scopes,
                    $connection->status,
                    $connection->lastError,
                    $connection->connectedAt,
                    $connection->disconnectedAt
                );
            }
        }
        if (!$calendarId) return null;
        return new GoogleCalendarIntegration(
            $shared->googleUserId,
            $shared->googleEmail,
            $shared->accessTokenEncrypted,
            $shared->refreshTokenEncrypted,
            $shared->tokenExpiresAt,
            $calendarId,
            $shared->scopes,
            $shared->status,
            $shared->lastError,
            $shared->connectedAt,
            $shared->disconnectedAt
        );
    }
}
