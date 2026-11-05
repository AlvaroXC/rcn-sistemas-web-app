<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Customer;

final class MysqliCustomerRepository implements CustomerRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function findByPhone(string $phone): ?Customer
    {
        $statement = $this->connection->prepare('SELECT * FROM customers WHERE phone_normalized = ? LIMIT 1');
        $statement->bind_param('s', $phone); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? Customer::fromArray($row) : null;
    }

    public function find(int $customerId): ?Customer
    {
        $statement = $this->connection->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $customerId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? Customer::fromArray($row) : null;
    }

    public function findByRfc(string $rfc): ?Customer
    {
        $statement = $this->connection->prepare('SELECT * FROM customers WHERE rfc = ? LIMIT 1');
        $statement->bind_param('s', $rfc); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? Customer::fromArray($row) : null;
    }

    public function save(array $data): ?Customer
    {
        $customer = $this->saveContact($data);
        if ($customer && array_key_exists('rfc', $data)) {
            $rfc = $data['rfc']; $customerId = $customer->id;
            $statement = $this->connection->prepare('UPDATE customers SET rfc=? WHERE id=?');
            $statement->bind_param('si', $rfc, $customerId);
            return $statement->execute() ? $this->find($customerId) : null;
        }
        return $customer;
    }

    private function saveContact(array $data): ?Customer
    {
        $name = trim((string) ($data['name'] ?? '')); $email = $data['email'] ?? null;
        $emailNormalized = $email !== null ? strtolower((string) $email) : null;
        $phone = trim((string) ($data['phone'] ?? '')); $businessName = trim((string) ($data['business_name'] ?? ''));
        $customerType = (string) ($data['customer_type'] ?? 'persona_fisica'); $customerId = (int) ($data['id'] ?? 0); $existing = $this->findByPhone($phone);
        if ($customerId > 0) {
            $statement = $this->connection->prepare('UPDATE customers SET name=?, business_name=?, customer_type=?, email=?, email_normalized=?, phone=?, phone_normalized=? WHERE id=?');
            $statement->bind_param('sssssssi', $name, $businessName, $customerType, $email, $emailNormalized, $phone, $phone, $customerId); $statement->execute();
            return $this->find($customerId);
        }
        if ($existing) {
            $existingId = $existing->id;
            $statement = $this->connection->prepare('UPDATE customers SET name=?, business_name=?, customer_type=?, email=?, email_normalized=?, phone=? WHERE id=?');
            $statement->bind_param('ssssssi', $name, $businessName, $customerType, $email, $emailNormalized, $phone, $existingId); $statement->execute();
            return $this->find($existingId);
        }
        $statement = $this->connection->prepare('INSERT INTO customers (name, business_name, customer_type, email, email_normalized, phone, phone_normalized) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param('sssssss', $name, $businessName, $customerType, $email, $emailNormalized, $phone, $phone);
        return $statement->execute() ? $this->find((int) $this->connection->insert_id) : null;
    }
}
