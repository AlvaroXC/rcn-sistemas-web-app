<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliPushPreferenceRepository implements PushPreferenceRepository
{
    private const DEFAULTS = ['assignments' => 1, 'observations' => 1, 'status_changes' => 1];

    public function __construct(private readonly mysqli $connection) {}

    public function get(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT assignments, observations, status_changes FROM push_preferences WHERE user_id = ? LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        return $row ? array_map('intval', $row) : self::DEFAULTS;
    }

    public function save(int $userId, array $preferences): bool
    {
        $assignments = !empty($preferences['assignments']) ? 1 : 0;
        $observations = !empty($preferences['observations']) ? 1 : 0;
        $statusChanges = !empty($preferences['status_changes']) ? 1 : 0;
        $statement = $this->connection->prepare('INSERT INTO push_preferences (user_id, assignments, observations, status_changes) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE assignments=VALUES(assignments), observations=VALUES(observations), status_changes=VALUES(status_changes)');
        $statement->bind_param('iiii', $userId, $assignments, $observations, $statusChanges);
        return $statement->execute();
    }

    public function enabled(int $userId, string $eventType): bool
    {
        $preferences = $this->get($userId);
        $key = match ($eventType) {
            'ticket_observation' => 'observations',
            'ticket_status' => 'status_changes',
            default => 'assignments',
        };
        return !empty($preferences[$key]);
    }
}
