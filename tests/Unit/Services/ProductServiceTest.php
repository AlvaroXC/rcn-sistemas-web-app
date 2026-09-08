<?php

namespace Tests\Unit\Services;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Product;
use RcnSistemas\Repositories\ProductRepository;
use RcnSistemas\Services\ProductService;

final class ProductServiceTest extends TestCase
{
    public function test_it_rejects_a_product_without_a_name(): void
    {
        $service = new ProductService(new InMemoryProductRepository());

        $this->expectException(InvalidArgumentException::class);
        $service->save([]);
    }

    public function test_it_delegates_a_valid_product_to_the_repository(): void
    {
        $repository = new InMemoryProductRepository();
        $service = new ProductService($repository);

        $product = $service->save(['name' => 'Producto de prueba']);

        self::assertSame('Producto de prueba', $product->name);
        self::assertSame('Producto de prueba', $repository->savedData['name']);
    }
}

final class InMemoryProductRepository implements ProductRepository
{
    public array $savedData = [];

    public function all(?int $limit = null): array
    {
        return [];
    }

    public function find(int $productId): ?Product
    {
        return null;
    }

    public function save(array $data, array $benefits = [], int $productId = 0): ?Product
    {
        $this->savedData = $data;
        return Product::fromArray(['name' => $data['name']]);
    }

    public function delete(int $productId): bool
    {
        return true;
    }
}
