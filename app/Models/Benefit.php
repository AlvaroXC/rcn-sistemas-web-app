<?php

namespace RcnSistemas\Models;

final class Benefit
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $title,
        public readonly string $description
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (int) ($data['product_id'] ?? 0),
            trim((string) ($data['title'] ?? '')),
            trim((string) ($data['description'] ?? ''))
        );
    }
}
