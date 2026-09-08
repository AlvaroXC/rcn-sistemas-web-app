<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliCustomerQueryRepository implements CustomerQueryRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function count(string $search = ''): int
    {
        [$where, $types, $params] = $this->parts($search);
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM customers' . $where);
        $this->bind($statement, $types, $params);
        $statement->execute();
        return (int) $statement->get_result()->fetch_row()[0];
    }

    public function list(string $search = '', int $limit = 25, int $offset = 0): array
    {
        [$where, $types, $params] = $this->parts($search);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $statement = $this->connection->prepare('SELECT id, name, business_name, customer_type, email, phone, created_at FROM customers' . $where . ' ORDER BY name ASC, id ASC LIMIT ? OFFSET ?');
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        $this->bind($statement, $types, $params);
        $statement->execute();
        $result = $statement->get_result();
        $customers = [];
        while ($result && $row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        return $customers;
    }

    public function tickets(int $customerId): array
    {
        $statement = $this->connection->prepare('SELECT id, consultation_type, status, origin_type, context_label, price_total_amount, price_amount, price_currency, appointment_date, appointment_time, created_at FROM tickets WHERE customer_id = ? ORDER BY created_at DESC, id DESC');
        $statement->bind_param('i', $customerId);
        $statement->execute();
        $result = $statement->get_result();
        $tickets = [];
        while ($result && $row = $result->fetch_assoc()) $tickets[] = $row;
        return $tickets;
    }

    private function parts(string $search): array
    {
        $search = trim($search);
        if ($search === '') return ['', '', []];
        $value = '%' . $search . '%';
        return [' WHERE name LIKE ? OR business_name LIKE ? OR email LIKE ? OR phone LIKE ?', 'ssss', [$value, $value, $value, $value]];
    }

    private function bind(object $statement, string $types, array $params): void
    {
        if ($types !== '') $statement->bind_param($types, ...$params);
    }
}
