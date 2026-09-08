<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliAdminUserRepository implements AdminUserRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare('SELECT id, username, password, display_name, role, active FROM users WHERE username = ? LIMIT 1');
        $statement->bind_param('s', $username);
        $statement->execute();

        $user = $statement->get_result()->fetch_assoc();

        return $user ?: null;
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->connection->prepare('SELECT id, username, password, display_name, role, active FROM users WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();

        $user = $statement->get_result()->fetch_assoc();

        return $user ?: null;
    }
}
