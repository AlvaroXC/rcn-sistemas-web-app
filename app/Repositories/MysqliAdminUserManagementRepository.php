<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\AdminUser;

final class MysqliAdminUserManagementRepository implements AdminUserManagementRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function all(): array
    {
        $result = $this->connection->query('SELECT id, username, display_name, role, active, created_at FROM users ORDER BY display_name, username');
        $users = [];
        while ($result && $row = $result->fetch_assoc()) $users[] = AdminUser::fromArray($row);
        return $users;
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare('SELECT id, username, password, display_name, role, active FROM users WHERE username = ? LIMIT 1');
        $statement->bind_param('s', $username); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->connection->prepare('SELECT id, username, password, display_name, role, active FROM users WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $userId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function save(array $data, int $userId = 0): ?AdminUser
    {
        $username = trim((string) ($data['username'] ?? ''));
        $displayName = trim((string) ($data['display_name'] ?? '')) ?: $username;
        $role = (string) ($data['role'] ?? 'agent');
        $active = (int) ($data['active'] ?? 1);
        $password = (string) ($data['password'] ?? '');

        if ($userId > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $this->connection->prepare('UPDATE users SET username=?, display_name=?, role=?, active=?, password=? WHERE id=?');
                $statement->bind_param('sssisi', $username, $displayName, $role, $active, $hash, $userId);
            } else {
                $statement = $this->connection->prepare('UPDATE users SET username=?, display_name=?, role=?, active=? WHERE id=?');
                $statement->bind_param('sssii', $username, $displayName, $role, $active, $userId);
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $this->connection->prepare('INSERT INTO users (username, password, display_name, role, active) VALUES (?, ?, ?, ?, ?)');
            $statement->bind_param('ssssi', $username, $hash, $displayName, $role, $active);
        }
        if (!$statement->execute()) return null;
        $userId = $userId > 0 ? $userId : (int) $this->connection->insert_id;
        return AdminUser::fromArray($this->findById($userId) ?? []);
    }
}
