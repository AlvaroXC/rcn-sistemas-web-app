<?php

namespace RcnSistemas\Services\Google;

use DateTimeImmutable;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\TicketCalendarRepository;
use Throwable;

final class GoogleCalendarAppointmentSyncService
{
    public function __construct(
        private readonly GoogleCalendarConnectionRepository $connections,
        private readonly TicketCalendarRepository $ticketCalendar,
        private readonly GoogleCalendarService $calendar,
        private readonly array $config
    ) {}

    public function sync(Ticket $ticket): void
    {
        $this->syncTicket($ticket, false);
    }

    public function syncReassignment(Ticket $previous, Ticket $current): void
    {
        if ($previous->assignedUserId === $current->assignedUserId) {
            $this->sync($current);
            return;
        }

        if ($previous->assignedUserId && $previous->googleCalendarEventId) {
            $previousConnection = $this->connections->findByUserId($previous->assignedUserId);
            if ($previousConnection) {
                try { $this->calendar->deleteEvent($previousConnection, $previous->googleCalendarEventId); } catch (Throwable) {}
            }
        }
        $this->ticketCalendar->updateCalendarSync($current->id, null, null, 'pending');
        $this->syncTicket($current, true);
    }

    private function syncTicket(Ticket $ticket, bool $forceCreate): void
    {
        $connection = $ticket->assignedUserId ? $this->connections->findByUserId($ticket->assignedUserId) : null;
        if (!$ticket->appointmentDate || !$ticket->appointmentTime || !$connection || $connection->status !== 'connected') {
            if ($ticket->googleCalendarEventId && $connection) {
                try { $this->calendar->deleteEvent($connection, $ticket->googleCalendarEventId); } catch (Throwable) {}
            }
            $this->ticketCalendar->updateCalendarSync($ticket->id, null, null, 'not_configured');
            return;
        }

        try {
            $timezone = new \DateTimeZone((string) ($this->config['timezone'] ?? 'America/Merida'));
            $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $ticket->appointmentDate . ' ' . substr($ticket->appointmentTime, 0, 5), $timezone);
            if (!$start) throw new \InvalidArgumentException('La fecha de la cita no es válida.');
            $end = $start->modify('+' . max(1, (int) ($this->config['default_duration_minutes'] ?? 60)) . ' minutes');
            $data = [
                'summary' => trim(($ticket->consultationType ?: 'Cita') . ' - ' . ($ticket->customerName ?: 'Cliente')),
                'description' => 'Ticket #' . $ticket->id . "\nCliente: " . ($ticket->customerName ?: 'No disponible') . "\n\n" . $ticket->message . "\n\nVer ticket: " . rtrim((string) ($this->config['app_url'] ?? ''), '/') . '/admin/tickets/' . $ticket->id,
                'start' => $start->format(DATE_RFC3339), 'end' => $end->format(DATE_RFC3339),
            ];
            $event = !$forceCreate && $ticket->googleCalendarEventId
                ? $this->calendar->updateEvent($connection, $ticket->googleCalendarEventId, $data)
                : $this->calendar->createEvent($connection, $data);
            $this->ticketCalendar->updateCalendarSync($ticket->id, $event->getId(), $event->getHtmlLink(), 'synced');
        } catch (Throwable $exception) {
            $status = $exception->getCode() === 401 ? 'token_expired' : 'sync_error';
            if ($status === 'token_expired' && $ticket->assignedUserId) $this->connections->markError($ticket->assignedUserId, $status, $exception->getMessage());
            $this->ticketCalendar->updateCalendarSync($ticket->id, $ticket->googleCalendarEventId, $ticket->googleCalendarEventLink, $status, $exception->getMessage());
        }
    }
}
