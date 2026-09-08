<?php

namespace RcnSistemas\Services\Notifications;

final class NotificationService
{
    public function assignmentMessage(int $ticketId, string $actorLabel): array
    {
        return [
            'type' => 'ticket_assignment',
            'title' => 'Ticket asignado',
            'message' => 'El ticket #' . $ticketId . ' fue asignado por ' . trim($actorLabel) . '.',
            'ticket_id' => $ticketId,
        ];
    }
}
