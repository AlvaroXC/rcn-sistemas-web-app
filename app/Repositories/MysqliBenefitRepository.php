<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Benefit;

final class MysqliBenefitRepository implements BenefitRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function forProduct(int $productId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM benefits WHERE product_id = ? ORDER BY id ASC');
        $statement->bind_param('i', $productId);
        $statement->execute();
        $result = $statement->get_result();
        $benefits = [];

        while ($result && $row = $result->fetch_assoc()) {
            $benefits[] = Benefit::fromArray($row);
        }

        return $benefits;
    }
}
