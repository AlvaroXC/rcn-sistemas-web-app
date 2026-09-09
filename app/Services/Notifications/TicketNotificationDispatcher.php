<?php

namespace RcnSistemas\Services\Notifications;

use mysqli;
use RcnSistemas\Models\Ticket;

final class TicketNotificationDispatcher
{
    public function __construct(private readonly mysqli $connection, private readonly PushDeliveryService $push, private readonly string $baseUrl = '') {}

    public function assignment(Ticket $ticket, int $actorId, string $actorLabel, bool $created = false): void
    {
        if (!$ticket->assignedUserId || $ticket->status === 'Cerrado') return;
        $type = $created ? 'ticket_assignment_created' : 'ticket_assignment';
        $title = $created ? 'Ticket asignado' : 'Ticket reasignado';
        $message = 'El ticket #' . $ticket->id . ($created ? ' fue asignado' : ' fue reasignado') . ' por ' . trim($actorLabel) . '.';
        $this->dispatch($ticket, $actorId, $type, 'ticket_assignment', $title, $message, $type . ':' . $ticket->id . ':' . ($ticket->assignedAt ?: 'initial'));
    }

    public function observation(Ticket $ticket, int $actorId, string $actorLabel): void
    {
        if (!$ticket->assignedUserId || $ticket->status === 'Cerrado') return;
        $this->dispatch($ticket, $actorId, 'ticket_observation', 'ticket_observation', 'Nueva observación', 'El ticket #' . $ticket->id . ' tiene una nueva observación de ' . trim($actorLabel) . '.', 'ticket_observation:' . $ticket->id . ':' . microtime(true));
    }

    public function status(Ticket $ticket, int $actorId, string $actorLabel): void
    {
        if (!$ticket->assignedUserId || $ticket->status === 'Cerrado') return;
        $this->dispatch($ticket, $actorId, 'ticket_status', 'ticket_status', 'Estado de ticket actualizado', 'El ticket #' . $ticket->id . ' cambió a ' . $ticket->status . '.', 'ticket_status:' . $ticket->id . ':' . $ticket->status . ':' . microtime(true));
    }

    private function dispatch(Ticket $ticket, int $actorId, string $type, string $eventType, string $title, string $message, string $eventKey): void
    {
        $userId = (int) $ticket->assignedUserId;
        $statement = $this->connection->prepare('INSERT INTO admin_notifications (user_id, actor_user_id, ticket_id, type, title, message) VALUES (?, ?, ?, ?, ?, ?)');
        $ticketId = $ticket->id;
        $statement->bind_param('iiisss', $userId, $actorId, $ticketId, $type, $title, $message);
        $statement->execute();
        $delivery = $this->push->sendToUser($userId, $ticket->id, $eventType, $title, $message, $this->baseUrl . '/admin/tickets/' . $ticket->id, $eventKey);
        foreach (($delivery['results'] ?? []) as $result) {
            $log = $this->connection->prepare('INSERT IGNORE INTO push_delivery_log (user_id, device_id, ticket_id, event_key, event_type, result, provider_code) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if (!$log) continue;
            $deviceId = (int) ($result['device_id'] ?? 0); $resultType = (string) ($result['result'] ?? 'unknown'); $providerCode = $result['provider_code'] ?? null;
            $log->bind_param('iiissss', $userId, $deviceId, $ticketId, $eventKey, $eventType, $resultType, $providerCode);
            $log->execute();
        }
    }
}
