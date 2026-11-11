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
        $videoLink = trim((string) ($data['video_link'] ?? ''));
        if ($videoLink !== '' && filter_var($videoLink, FILTER_VALIDATE_URL) === false) throw new InvalidArgumentException('El enlace de video no es válido.');
        foreach (['pdf_path' => ['pdf'], 'image_path' => ['jpg', 'jpeg', 'png', 'webp']] as $field => $extensions) {
            $path = trim((string) ($data[$field] ?? ''));
            if ($path !== '' && (str_contains($path, '..') || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions, true))) throw new InvalidArgumentException('La ruta de archivo del producto no es válida.');
        }

        $product = $this->products->save($data, $benefits, $productId);
        if (!$product) {
            throw new InvalidArgumentException('No se pudo guardar el producto.');
        }

        return $product;
    }
}
