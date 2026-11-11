<?php

namespace Tests\Support;

use mysqli;

final class DatabaseCleaner
{
    public function __construct(private readonly mysqli $connection) {}

    public function clean(): void
    {
        foreach (['ticket_observations', 'admin_notifications', 'tickets', 'customers'] as $table) {
            $this->connection->query('DELETE FROM ' . $table);
        }
    }
}
