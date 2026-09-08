<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliTicketQueryRepository implements TicketQueryRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function count(array $filters, int $viewerId, string $viewerRole): int
    {
        [$where, $types, $params] = $this->queryParts($filters, $viewerId, $viewerRole);
        $sql = 'SELECT COUNT(*) FROM tickets t INNER JOIN customers c ON c.id = t.customer_id' . $where;
        $statement = $this->connection->prepare($sql);
        $this->bind($statement, $types, $params); $statement->execute();
        return (int) $statement->get_result()->fetch_row()[0];
    }

    public function list(array $filters, int $viewerId, string $viewerRole, int $limit, int $offset): array
    {
        [$where, $types, $params] = $this->queryParts($filters, $viewerId, $viewerRole);
        $sql = 'SELECT t.*, c.name customer_name, c.email customer_email, c.phone customer_phone FROM tickets t INNER JOIN customers c ON c.id = t.customer_id' . $where . ' ORDER BY t.created_at DESC, t.id DESC LIMIT ? OFFSET ?';
        $types .= 'ii'; $params[] = max(1, $limit); $params[] = max(0, $offset);
        $statement = $this->connection->prepare($sql); $this->bind($statement, $types, $params); $statement->execute();
        $result = $statement->get_result(); $tickets = [];
        while ($result && $row = $result->fetch_assoc()) $tickets[] = $row;
        return $tickets;
    }

    private function queryParts(array $filters, int $viewerId, string $viewerRole): array
    {
        $conditions = []; $types = ''; $params = [];
        if ($viewerRole !== 'admin') { $conditions[] = 't.assigned_user_id = ?'; $types .= 'i'; $params[] = $viewerId; }
        if (($filters['scope'] ?? 'open') === 'open') $conditions[] = "t.status <> 'Cerrado'";
        foreach (['status' => 't.status', 'consultation_type' => 't.consultation_type'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') { $conditions[] = $column . ' = ?'; $types .= 's'; $params[] = $filters[$key]; }
        }
        if (($filters['assigned_user_id'] ?? '') !== '' && $viewerRole === 'admin') { $conditions[] = 't.assigned_user_id = ?'; $types .= 'i'; $params[] = (int) $filters['assigned_user_id']; }
        if (($filters['customer'] ?? '') !== '') { $conditions[] = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)'; $types .= 'sss'; $search = '%' . $filters['customer'] . '%'; array_push($params, $search, $search, $search); }
        if (($filters['date_from'] ?? '') !== '') { $conditions[] = 'DATE(t.created_at) >= ?'; $types .= 's'; $params[] = $filters['date_from']; }
        if (($filters['date_to'] ?? '') !== '') { $conditions[] = 'DATE(t.created_at) <= ?'; $types .= 's'; $params[] = $filters['date_to']; }
        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $types, $params];
    }

    private function bind(object $statement, string $types, array $params): void
    {
        if ($types !== '') $statement->bind_param($types, ...$params);
    }
}
