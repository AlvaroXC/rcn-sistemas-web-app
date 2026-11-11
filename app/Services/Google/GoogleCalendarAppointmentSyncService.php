<?php

namespace RcnSistemas\Services\Google;

use DateTimeImmutable;
use Google\Service\Exception as GoogleServiceException;
use RcnSistemas\Models\GoogleCalendarIntegration;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Repositories\GoogleCalendarIntegrationRepository;
use RcnSistemas\Repositories\TicketCalendarRepository;
use Throwable;

final class GoogleCalendarAppointmentSyncService
{
    public function __construct(
        private readonly GoogleCalendarIntegrationRepository $integrations,
        private readonly TicketCalendarRepository $ticketCalendar,
        private readonly GoogleCalendarGateway $calendar,
        private readonly array $config
    ) {}

    public function sync(Ticket $ticket): void
    {
        $integration = $this->integrations->findShared();
        if (!$ticket->appointmentDate || !$ticket->appointmentTime) {
            $this->deleteAppointmentEvent($ticket, $integration);
            return;
        }

        if (!$integration || $integration->status === 'disconnected') {
            $this->ticketCalendar->updateCalendarSync(
                $ticket->id,
                $ticket->googleCalendarEventId,
                $ticket->googleCalendarEventLink,
                'not_configured',
                'La integración común de Google Calendar no está conectada.',
                $ticket->googleCalendarTargetId,
                $ticket->googleCalendarMigrationStatus,
                $ticket->googleCalendarLegacyCleanupStatus,
                $ticket->googleCalendarLegacyCleanupError,
                $ticket->googleCalendarLegacyEventId,
                $ticket->googleCalendarLegacyCalendarId,
                $ticket->googleCalendarLegacyUserId
            );
            return;
        }
        if ($integration->status === 'token_expired') {
            $this->recordError($ticket, 'token_expired', $integration->lastError ?: 'La conexión común de Google Calendar requiere reconexión.');
            return;
        }
        if ($integration->status !== 'connected') {
            $this->recordError($ticket, 'sync_error', $integration->lastError ?: 'La configuración común de Google Calendar requiere atención.');
            return;
        }

        try {
            $eventData = $this->eventData($ticket);
            $existingInTarget = $ticket->googleCalendarTargetId === $integration->calendarId
                && $ticket->googleCalendarEventId !== null;
            $legacyEventId = $ticket->googleCalendarLegacyEventId;
            $legacyCalendarId = $ticket->googleCalendarLegacyCalendarId;
            $legacyUserId = $ticket->googleCalendarLegacyUserId;
            $cleanupStatus = $ticket->googleCalendarLegacyCleanupStatus;

            if (!$existingInTarget && $ticket->googleCalendarEventId && !$legacyEventId) {
                $legacyEventId = $ticket->googleCalendarEventId;
                $legacyCalendarId = $ticket->googleCalendarTargetId ?: $integration->calendarId;
                $legacyUserId = $ticket->googleCalendarTargetId ? null : $ticket->assignedUserId;
                $cleanupStatus = 'pending';
            }

            $stableEventId = $this->stableEventId($ticket->id);
            if ($existingInTarget) {
                try {
                    $event = $this->calendar->updateEvent($integration, $ticket->googleCalendarEventId, $eventData);
                } catch (GoogleServiceException $exception) {
                    if ((int) $exception->getCode() !== 404) throw $exception;
                    $event = $this->calendar->createEvent($integration, $eventData, $stableEventId);
                }
            } else {
                $event = $this->calendar->createEvent($integration, $eventData, $stableEventId);
            }

            $this->ticketCalendar->updateCalendarSync(
                $ticket->id,
                $event->getId(),
                $event->getHtmlLink(),
                'synced',
                null,
                $integration->calendarId,
                'migrated',
                $cleanupStatus,
                $ticket->googleCalendarLegacyCleanupError,
                $legacyEventId,
                $legacyCalendarId,
                $legacyUserId
            );
        } catch (GoogleCalendarAuthorizationException $exception) {
            $this->integrations->markSharedError('token_expired', $exception->getMessage());
            $this->recordError($ticket, 'token_expired', $exception->getMessage());
        } catch (GoogleCalendarConfigurationException $exception) {
            $this->integrations->markSharedError('configuration_error', $exception->getMessage());
            $this->recordError($ticket, 'sync_error', $exception->getMessage());
        } catch (GoogleServiceException $exception) {
            if (in_array((int) $exception->getCode(), [403, 404], true)) {
                $message = 'La cuenta autorizada no tiene acceso al calendario común configurado.';
                $this->integrations->markSharedError('configuration_error', $message);
                $this->recordError($ticket, 'sync_error', $message);
                return;
            }
            $this->recordError($ticket, 'sync_error', 'Google Calendar no pudo completar la operación. Intenta nuevamente.');
        } catch (Throwable $exception) {
            $this->recordError($ticket, 'sync_error', $exception->getMessage());
        }
    }

    public function syncReassignment(Ticket $previous, Ticket $current): void
    {
        $this->sync($current);
    }

    private function deleteAppointmentEvent(Ticket $ticket, ?GoogleCalendarIntegration $integration): void
    {
        if (!$ticket->googleCalendarEventId) {
            $this->ticketCalendar->updateCalendarSync(
                $ticket->id,
                $ticket->googleCalendarEventId,
                $ticket->googleCalendarEventLink,
                'not_configured',
                null,
                $ticket->googleCalendarTargetId,
                'not_applicable',
                $ticket->googleCalendarLegacyCleanupStatus,
                $ticket->googleCalendarLegacyCleanupError,
                $ticket->googleCalendarLegacyEventId,
                $ticket->googleCalendarLegacyCalendarId,
                $ticket->googleCalendarLegacyUserId
            );
            return;
        }

        if (!$integration || $integration->status !== 'connected' || $ticket->googleCalendarTargetId !== $integration->calendarId) {
            $legacyEventId = $ticket->googleCalendarLegacyEventId ?: $ticket->googleCalendarEventId;
            $legacyCalendarId = $ticket->googleCalendarLegacyCalendarId ?: $ticket->googleCalendarTargetId;
            $legacyUserId = $ticket->googleCalendarLegacyUserId ?: ($ticket->googleCalendarTargetId ? null : $ticket->assignedUserId);
            $this->ticketCalendar->updateCalendarSync(
                $ticket->id,
                $ticket->googleCalendarEventId,
                $ticket->googleCalendarEventLink,
                $integration?->status === 'token_expired' ? 'token_expired' : 'pending',
                'La cita se quitó en RCN; el evento espera acceso al calendario para eliminarse.',
                $ticket->googleCalendarTargetId,
                'pending',
                'pending',
                $ticket->googleCalendarLegacyCleanupError,
                $legacyEventId,
                $legacyCalendarId,
                $legacyUserId
            );
            return;
        }

        try {
            $this->calendar->deleteEvent($integration, $ticket->googleCalendarEventId);
            $this->ticketCalendar->updateCalendarSync(
                $ticket->id,
                null,
                null,
                'not_configured',
                null,
                null,
                'not_applicable',
                $ticket->googleCalendarLegacyCleanupStatus,
                $ticket->googleCalendarLegacyCleanupError,
                $ticket->googleCalendarLegacyEventId,
                $ticket->googleCalendarLegacyCalendarId,
                $ticket->googleCalendarLegacyUserId
            );
        } catch (GoogleCalendarAuthorizationException $exception) {
            $this->integrations->markSharedError('token_expired', $exception->getMessage());
            $this->recordError($ticket, 'token_expired', $exception->getMessage());
        } catch (GoogleCalendarConfigurationException $exception) {
            $this->integrations->markSharedError('configuration_error', $exception->getMessage());
            $this->recordError($ticket, 'sync_error', $exception->getMessage());
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() === 404) {
                $this->ticketCalendar->updateCalendarSync(
                    $ticket->id,
                    null,
                    null,
                    'not_configured',
                    null,
                    null,
                    'not_applicable',
                    $ticket->googleCalendarLegacyCleanupStatus,
                    $ticket->googleCalendarLegacyCleanupError,
                    $ticket->googleCalendarLegacyEventId,
                    $ticket->googleCalendarLegacyCalendarId,
                    $ticket->googleCalendarLegacyUserId
                );
                return;
            }
            $this->recordError($ticket, 'sync_error', 'No se pudo eliminar el evento de Google Calendar.');
        } catch (Throwable) {
            $this->recordError($ticket, 'sync_error', 'No se pudo eliminar el evento de Google Calendar.');
        }
    }

    private function eventData(Ticket $ticket): array
    {
        $timezone = new \DateTimeZone((string) ($this->config['timezone'] ?? 'America/Merida'));
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $ticket->appointmentDate . ' ' . substr($ticket->appointmentTime, 0, 5), $timezone);
        if (!$start) throw new \InvalidArgumentException('La fecha de la cita no es válida.');
        $end = $start->modify('+' . max(1, (int) ($this->config['default_duration_minutes'] ?? 60)) . ' minutes');
        return [
            'summary' => trim(($ticket->consultationType ?: 'Cita') . ' - ' . ($ticket->customerName ?: 'Cliente')),
            'description' => 'Ticket #' . $ticket->id . "\nCliente: " . ($ticket->customerName ?: 'No disponible') . "\n\n" . $ticket->message . "\n\nVer ticket: " . rtrim((string) ($this->config['app_url'] ?? ''), '/') . '/admin/tickets/' . $ticket->id,
            'start' => $start->format(DATE_RFC3339),
            'end' => $end->format(DATE_RFC3339),
        ];
    }

    private function stableEventId(int $ticketId): string
    {
        return 'rcn' . substr(hash('sha256', 'ticket:' . $ticketId), 0, 40);
    }

    private function recordError(Ticket $ticket, string $status, string $error): void
    {
        $this->ticketCalendar->updateCalendarSync(
            $ticket->id,
            $ticket->googleCalendarEventId,
            $ticket->googleCalendarEventLink,
            $status,
            $error,
            $ticket->googleCalendarTargetId,
            $ticket->googleCalendarMigrationStatus,
            $ticket->googleCalendarLegacyCleanupStatus,
            $ticket->googleCalendarLegacyCleanupError,
            $ticket->googleCalendarLegacyEventId,
            $ticket->googleCalendarLegacyCalendarId,
            $ticket->googleCalendarLegacyUserId
        );
    }
}
