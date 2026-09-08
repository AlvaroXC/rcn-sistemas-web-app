<?php

namespace RcnSistemas\Models;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $shortDescription,
        public readonly string $longDescription,
        public readonly string $videoLink,
        public readonly string $pdfPath,
        public readonly string $imagePath,
        public readonly array $benefits = []
    ) {
    }

    public static function fromArray(array $data, array $benefits = []): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['short_description'] ?? '')),
            trim((string) ($data['long_description'] ?? '')),
            trim((string) ($data['video_link'] ?? '')),
            trim((string) ($data['pdf_path'] ?? '')),
            trim((string) ($data['image_path'] ?? '')),
            $benefits
        );
    }
}
