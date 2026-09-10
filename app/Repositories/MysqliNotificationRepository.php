<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliNotificationRepository implements NotificationRepository
{
    public function __construct(private readonly mysqli $connection) {}
    private const TYPES = "('ticket_assignment', 'ticket_assignment_created', 'ticket_observation', 'ticket_status')";
    public function unreadCount(int $userId): int { $statement = $this->connection->prepare("SELECT COUNT(*) FROM admin_notifications n INNER JOIN tickets t ON t.id = n.ticket_id WHERE n.user_id = ? AND n.is_read = 0 AND n.type IN " . self::TYPES . " AND t.status <> 'Cerrado'"); $statement->bind_param('i', $userId); $statement->execute(); return (int) $statement->get_result()->fetch_row()[0]; }
    public function latestId(int $userId): int { $statement = $this->connection->prepare("SELECT COALESCE(MAX(n.id), 0) FROM admin_notifications n INNER JOIN tickets t ON t.id = n.ticket_id WHERE n.user_id = ? AND n.type IN " . self::TYPES . " AND t.status <> 'Cerrado'"); $statement->bind_param('i', $userId); $statement->execute(); return (int) $statement->get_result()->fetch_row()[0]; }
    public function recent(int $userId, int $limit = 50): array { $limit = max(1, min(100, $limit)); $statement = $this->connection->prepare("SELECT n.*, actor.username actor_username, actor.display_name actor_display_name FROM admin_notifications n INNER JOIN tickets t ON t.id = n.ticket_id LEFT JOIN users actor ON actor.id = n.actor_user_id WHERE n.user_id = ? AND n.type IN " . self::TYPES . " AND t.status <> 'Cerrado' ORDER BY n.is_read ASC, n.created_at DESC, n.id DESC LIMIT ?"); $statement->bind_param('ii', $userId, $limit); $statement->execute(); $result = $statement->get_result(); $items = []; while ($result && $row = $result->fetch_assoc()) $items[] = $row; return $items; }
    public function after(int $userId, int $lastId, int $limit = 20): array { $limit = max(1, min(50, $limit)); $statement = $this->connection->prepare("SELECT n.*, actor.username actor_username, actor.display_name actor_display_name FROM admin_notifications n INNER JOIN tickets t ON t.id = n.ticket_id LEFT JOIN users actor ON actor.id = n.actor_user_id WHERE n.user_id = ? AND n.id > ? AND n.type IN " . self::TYPES . " AND t.status <> 'Cerrado' ORDER BY n.id ASC LIMIT ?"); $statement->bind_param('iii', $userId, $lastId, $limit); $statement->execute(); $result = $statement->get_result(); $items = []; while ($result && $row = $result->fetch_assoc()) $items[] = $row; return $items; }
    public function markTicketAsRead(int $userId, int $ticketId): bool { $statement = $this->connection->prepare("UPDATE admin_notifications SET is_read = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP) WHERE user_id = ? AND ticket_id = ? AND type IN " . self::TYPES . " AND is_read = 0"); $statement->bind_param('ii', $userId, $ticketId); return $statement->execute(); }
}
