<?php

namespace RcnSistemas\Repositories;

use mysqli;

final class MysqliDashboardMetricsRepository implements DashboardMetricsRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function metrics(int $viewerId, string $viewerRole): array
    {
        $metrics = [];
        foreach (['products', 'promotions', 'testimonials', 'customers'] as $table) {
            $result = $this->connection->query('SELECT COUNT(*) AS total FROM ' . $table);
            $metrics[$table] = $result ? (int) $result->fetch_assoc()['total'] : 0;
        }

        $ticketWhere = $viewerRole === 'admin' ? '' : ' WHERE assigned_user_id = ' . (int) $viewerId;
        $result = $this->connection->query('SELECT COUNT(*) AS total FROM tickets' . $ticketWhere);
        $metrics['tickets'] = $result ? (int) $result->fetch_assoc()['total'] : 0;
        $openWhere = $ticketWhere === '' ? " WHERE status <> 'Cerrado'" : $ticketWhere . " AND status <> 'Cerrado'";
        $result = $this->connection->query('SELECT COUNT(*) AS total FROM tickets' . $openWhere);
        $metrics['open_tickets'] = $result ? (int) $result->fetch_assoc()['total'] : 0;

        return $metrics;
    }
}
