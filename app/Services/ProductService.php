<?php

namespace RcnSistemas\Services;

use InvalidArgumentException;
use RcnSistemas\Models\Product;
use RcnSistemas\Repositories\ProductRepository;

final class ProductService
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function save(array $data, array $benefits = [], int $productId = 0): Product
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }

        $product = $this->products->save($data, $benefits, $productId);
        if (!$product) {
            throw new InvalidArgumentException('No se pudo guardar el producto.');
        }

        return $product;
    }
}
