<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Benefit;
use RcnSistemas\Models\Product;

final class MysqliProductRepository implements ProductRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function all(?int $limit = null): array
    {
        $sql = 'SELECT * FROM products ORDER BY created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit);
        }

        $result = $this->connection->query($sql);
        $rows = [];
        while ($result && $row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $benefitsByProduct = $this->benefitsForProducts(array_map(static fn (array $row): int => (int) $row['id'], $rows));
        $products = [];
        foreach ($rows as $row) {
            $products[] = $this->hydrate($row, $benefitsByProduct[(int) $row['id']] ?? []);
        }

        return $products;
    }

    public function find(int $productId): ?Product
    {
        $statement = $this->connection->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $productId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(array $data, array $benefits = [], int $productId = 0): ?Product
    {
        $name = trim((string) ($data['name'] ?? ''));
        $shortDescription = trim((string) ($data['short_description'] ?? ''));
        $longDescription = trim((string) ($data['long_description'] ?? ''));
        $videoLink = trim((string) ($data['video_link'] ?? ''));
        $pdfPath = trim((string) ($data['pdf_path'] ?? ''));
        $imagePath = trim((string) ($data['image_path'] ?? ''));

        $this->connection->begin_transaction();
        try {
            if ($productId > 0) {
                $statement = $this->connection->prepare('UPDATE products SET name=?, short_description=?, long_description=?, video_link=?, pdf_path=?, image_path=? WHERE id=?');
                $statement->bind_param('ssssssi', $name, $shortDescription, $longDescription, $videoLink, $pdfPath, $imagePath, $productId);
                $statement->execute();
            } else {
                $statement = $this->connection->prepare('INSERT INTO products (name, short_description, long_description, video_link, pdf_path, image_path) VALUES (?, ?, ?, ?, ?, ?)');
                $statement->bind_param('ssssss', $name, $shortDescription, $longDescription, $videoLink, $pdfPath, $imagePath);
                $statement->execute();
                $productId = (int) $this->connection->insert_id;
            }

            $deleteBenefits = $this->connection->prepare('DELETE FROM benefits WHERE product_id = ?');
            $deleteBenefits->bind_param('i', $productId);
            $deleteBenefits->execute();

            foreach ($benefits as $benefit) {
                $title = trim((string) ($benefit['title'] ?? ''));
                $description = trim((string) ($benefit['description'] ?? ''));
                if ($title === '' && $description === '') {
                    continue;
                }

                $insertBenefit = $this->connection->prepare('INSERT INTO benefits (product_id, title, description) VALUES (?, ?, ?)');
                $insertBenefit->bind_param('iss', $productId, $title, $description);
                $insertBenefit->execute();
            }

            $this->connection->commit();

            return $this->find($productId);
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function delete(int $productId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM products WHERE id = ?');
        $statement->bind_param('i', $productId);

        return $statement->execute();
    }

    private function hydrate(array $row, ?array $benefits = null): Product
    {
        $productId = (int) $row['id'];
        if ($benefits === null) {
            $benefitStatement = $this->connection->prepare('SELECT * FROM benefits WHERE product_id = ? ORDER BY id ASC');
            $benefitStatement->bind_param('i', $productId);
            $benefitStatement->execute();
            $benefitResult = $benefitStatement->get_result();
            $benefits = [];
            while ($benefitResult && $benefitRow = $benefitResult->fetch_assoc()) {
                $benefits[] = Benefit::fromArray($benefitRow);
            }
        }

        return Product::fromArray($row, $benefits);
    }

    /** @return array<int, array<int, Benefit>> */
    private function benefitsForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $statement = $this->connection->prepare('SELECT * FROM benefits WHERE product_id IN (' . $placeholders . ') ORDER BY product_id ASC, id ASC');
        $types = str_repeat('i', count($productIds));
        $statement->bind_param($types, ...$productIds);
        $statement->execute();
        $result = $statement->get_result();
        $benefitsByProduct = [];
        while ($result && $row = $result->fetch_assoc()) {
            $benefitsByProduct[(int) $row['product_id']][] = Benefit::fromArray($row);
        }

        return $benefitsByProduct;
    }
}
