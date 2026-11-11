<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Promotion;

final class MysqliPromotionManagementRepository implements PromotionManagementRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM promotions';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())';
        }
        $sql .= ' ORDER BY created_at DESC';
        return $this->fetch($sql);
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

    public function save(array $data, int $promotionId = 0): ?Promotion
    {
        $title = trim((string) ($data['title'] ?? ''));
        $short = trim((string) ($data['short_description'] ?? ''));
        $full = trim((string) ($data['full_description'] ?? ''));
        $terms = trim((string) ($data['terms_conditions'] ?? ''));
        $image = trim((string) ($data['image_path'] ?? ''));
        $start = $data['start_date'] ?? null;
        $end = $data['end_date'] ?? null;
        $active = (int) ($data['active'] ?? 0);

        if ($promotionId > 0) {
            $statement = $this->connection->prepare('UPDATE promotions SET title=?, short_description=?, full_description=?, terms_conditions=?, image_path=?, start_date=?, end_date=?, active=? WHERE id=?');
            $statement->bind_param('sssssssii', $title, $short, $full, $terms, $image, $start, $end, $active, $promotionId);
        } else {
            $statement = $this->connection->prepare('INSERT INTO promotions (title, short_description, full_description, terms_conditions, image_path, start_date, end_date, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->bind_param('sssssssi', $title, $short, $full, $terms, $image, $start, $end, $active);
        }
        if (!$statement->execute()) {
            return null;
        }

        $promotionId = $promotionId > 0 ? $promotionId : (int) $this->connection->insert_id;
        return $this->find($promotionId, false);
    }

    public function delete(int $promotionId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM promotions WHERE id = ?');
        $statement->bind_param('i', $promotionId);
        return $statement->execute();
    }

    private function fetch(string $sql): array
    {
        $result = $this->connection->query($sql);
        $promotions = [];
        while ($result && $row = $result->fetch_assoc()) {
            $promotions[] = Promotion::fromArray($row);
        }
        return $promotions;
    }
}
