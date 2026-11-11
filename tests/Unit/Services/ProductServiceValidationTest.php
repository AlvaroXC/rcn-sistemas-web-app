<?php

namespace Tests\Unit\Services;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Product;
use RcnSistemas\Repositories\ProductRepository;
use RcnSistemas\Services\ProductService;

final class ProductServiceValidationTest extends TestCase
{
    public function test_it_rejects_an_invalid_video_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $repository = new class implements ProductRepository {
            public function all(?int $limit = null): array { return []; }
            public function find(int $productId): ?Product { return null; }
            public function save(array $data, array $benefits = [], int $productId = 0): ?Product { return null; }
            public function delete(int $productId): bool { return true; }
        };
        (new ProductService($repository))->save(['name' => 'Producto', 'video_link' => 'not-a-url']);
    }
}
