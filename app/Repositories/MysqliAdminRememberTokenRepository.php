<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliAdminRememberTokenRepository implements AdminRememberTokenRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function create(int $userId, string $selector, string $tokenHash, string $expiresAt): bool
    {
        $statement = $this->connection->prepare(
            'INSERT INTO admin_remember_tokens (user_id, selector, token_hash, expires_at, last_used_at) VALUES (?, ?, ?, ?, NULL)'
        );
        $statement->bind_param('isss', $userId, $selector, $tokenHash, $expiresAt);

        return $statement->execute();
    }

    public function findBySelector(string $selector): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM admin_remember_tokens WHERE selector = ? LIMIT 1');
        $statement->bind_param('s', $selector);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();

        return $row ?: null;
    }

    public function rotate(int $tokenId, string $selector, string $tokenHash, string $expiresAt): bool
    {
        $lastUsedAt = date('Y-m-d H:i:s');
        $statement = $this->connection->prepare(
            'UPDATE admin_remember_tokens SET selector = ?, token_hash = ?, expires_at = ?, last_used_at = ? WHERE id = ?'
        );
        $statement->bind_param('ssssi', $selector, $tokenHash, $expiresAt, $lastUsedAt, $tokenId);

        return $statement->execute();
    }

    public function deleteBySelector(string $selector): bool
    {
        $statement = $this->connection->prepare('DELETE FROM admin_remember_tokens WHERE selector = ?');
        $statement->bind_param('s', $selector);

        return $statement->execute();
    }
}
