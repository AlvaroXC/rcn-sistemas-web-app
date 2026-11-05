<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\CustomerContact;

final class MysqliCustomerContactRepository implements CustomerContactRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function find(int $contactId): ?CustomerContact
    {
        $statement = $this->connection->prepare('SELECT * FROM customer_contacts WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $contactId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? CustomerContact::fromArray($row) : null;
    }

    public function forCustomer(int $customerId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM customer_contacts WHERE customer_id = ? ORDER BY name ASC, id ASC');
        $statement->bind_param('i', $customerId); $statement->execute(); $result = $statement->get_result();
        $contacts = [];
        while ($result && $row = $result->fetch_assoc()) {
            $contacts[] = CustomerContact::fromArray($row);
        }
        return $contacts;
    }

    public function save(array $data): ?CustomerContact
    {
        $contactId = (int) ($data['id'] ?? 0); $customerId = (int) $data['customer_id'];
        $name = (string) $data['name']; $phone = (string) $data['phone']; $email = $data['email'];
        if ($contactId > 0) {
            $statement = $this->connection->prepare('UPDATE customer_contacts SET name = ?, phone = ?, email = ? WHERE id = ?');
            $statement->bind_param('sssi', $name, $phone, $email, $contactId);
            return $statement->execute() ? $this->find($contactId) : null;
        }
        $statement = $this->connection->prepare('INSERT INTO customer_contacts (customer_id, name, phone, email) VALUES (?, ?, ?, ?)');
        $statement->bind_param('isss', $customerId, $name, $phone, $email);
        return $statement->execute() ? $this->find((int) $this->connection->insert_id) : null;
    }

    public function delete(int $contactId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM customer_contacts WHERE id = ?');
        $statement->bind_param('i', $contactId);
        return $statement->execute() && $statement->affected_rows > 0;
    }
}
