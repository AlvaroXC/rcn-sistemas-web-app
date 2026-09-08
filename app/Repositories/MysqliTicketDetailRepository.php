<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliTicketDetailRepository implements TicketDetailRepository
{
    public function __construct(private readonly mysqli $connection) {}
    public function observations(int $ticketId): array { $statement = $this->connection->prepare('SELECT o.*, u.username admin_username, u.display_name admin_display_name FROM ticket_observations o LEFT JOIN users u ON u.id = o.admin_user_id WHERE o.ticket_id = ? ORDER BY o.created_at DESC, o.id DESC'); $statement->bind_param('i', $ticketId); $statement->execute(); $result = $statement->get_result(); $items = []; while ($result && $row = $result->fetch_assoc()) $items[] = $row; return $items; }
    public function addObservation(int $ticketId, string $content, ?int $userId): bool { $statement = $this->connection->prepare('INSERT INTO ticket_observations (ticket_id, admin_user_id, content) VALUES (?, ?, ?)'); $statement->bind_param('iis', $ticketId, $userId, $content); return $statement->execute(); }
    public function updateMessage(int $ticketId, string $message, ?int $userId): bool { $updatedAt = date('Y-m-d H:i:s'); $statement = $this->connection->prepare('UPDATE tickets SET message = ?, message_updated_by = ?, message_updated_at = ? WHERE id = ? AND status <> "Cerrado"'); $statement->bind_param('sisi', $message, $userId, $updatedAt, $ticketId); return $statement->execute(); }
}
