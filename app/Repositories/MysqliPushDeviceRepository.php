<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliPushDeviceRepository implements PushDeviceRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function upsert(int $userId, string $token, ?string $deviceKey, string $platform, string $userAgent): bool
    {
        $sql = 'INSERT INTO push_devices (user_id, token, device_key, platform, user_agent, active, last_used_at) VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), device_key=VALUES(device_key), platform=VALUES(platform), user_agent=VALUES(user_agent), active=1, last_used_at=CURRENT_TIMESTAMP';
        $statement = $this->connection->prepare($sql);
        $statement->bind_param('issss', $userId, $token, $deviceKey, $platform, $userAgent);
        return $statement->execute();
    }

    public function activeForUser(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM push_devices WHERE user_id = ? AND active = 1 ORDER BY last_used_at DESC, id DESC');
        $statement->bind_param('i', $userId);
        $statement->execute();
        return $this->rows($statement->get_result());
    }

    public function allForUser(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT id, user_id, device_key, platform, user_agent, active, last_used_at, created_at, updated_at FROM push_devices WHERE user_id = ? ORDER BY active DESC, last_used_at DESC, id DESC');
        $statement->bind_param('i', $userId);
        $statement->execute();
        return $this->rows($statement->get_result());
    }

    public function deactivateById(int $userId, int $deviceId): bool
    {
        $statement = $this->connection->prepare('UPDATE push_devices SET active = 0 WHERE id = ? AND user_id = ?');
        $statement->bind_param('ii', $deviceId, $userId);
        return $statement->execute();
    }

    public function deactivateToken(string $token): bool
    {
        $statement = $this->connection->prepare('UPDATE push_devices SET active = 0 WHERE token = ?');
        $statement->bind_param('s', $token);
        return $statement->execute();
    }

    private function rows(?\mysqli_result $result): array
    {
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) $rows[] = $row;
        return $rows;
    }
}
