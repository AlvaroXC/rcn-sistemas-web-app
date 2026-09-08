<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Product;

interface ProductRepository
{
    /** @return list<Product> */
    public function all(?int $limit = null): array;

    public function find(int $productId): ?Product;

    public function save(array $data, array $benefits = [], int $productId = 0): ?Product;

    public function delete(int $productId): bool;
}
