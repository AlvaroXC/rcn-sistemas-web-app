<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliCustomerSearchRepository implements CustomerSearchRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function search(string $query, int $limit = 5): array
    {
        $search = '%' . trim($query) . '%'; $limit = max(1, min(50, $limit));
        $statement = $this->connection->prepare('SELECT id, name, business_name, customer_type, email, phone FROM customers WHERE name LIKE ? OR business_name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name LIMIT ?');
        $statement->bind_param('ssssi', $search, $search, $search, $search, $limit); $statement->execute();
        $result = $statement->get_result(); $customers = [];
        while ($result && $row = $result->fetch_assoc()) $customers[] = ['id' => (int) $row['id'], 'name' => trim((string) $row['name']), 'business_name' => trim((string) ($row['business_name'] ?? '')), 'customer_type' => trim((string) ($row['customer_type'] ?? 'persona_fisica')), 'email' => trim((string) ($row['email'] ?? '')), 'phone' => trim((string) $row['phone'])];
        return $customers;
    }
}
