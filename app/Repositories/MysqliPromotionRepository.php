<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Promotion;

final class MysqliPromotionRepository implements PromotionRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM promotions';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())';
        }
        $sql .= ' ORDER BY created_at DESC';
        $result = $this->connection->query($sql);
        $promotions = [];
        while ($result && $row = $result->fetch_assoc()) {
            $promotions[] = Promotion::fromArray($row);
        }

        return $promotions;
    }

    public function find(int $promotionId, bool $activeOnly = true): ?Promotion
    {
        $sql = 'SELECT * FROM promotions WHERE id = ?';
        if ($activeOnly) {
            $sql .= ' AND active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())';
        }
        $statement = $this->connection->prepare($sql);
        $statement->bind_param('i', $promotionId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();

        return $row ? Promotion::fromArray($row) : null;
    }
}
