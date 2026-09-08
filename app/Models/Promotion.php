<?php

namespace RcnSistemas\Models;

final class Promotion
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $shortDescription,
        public readonly string $fullDescription,
        public readonly string $termsConditions,
        public readonly string $imagePath,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly bool $active
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            trim((string) ($data['title'] ?? '')),
            trim((string) ($data['short_description'] ?? $data['description'] ?? '')),
            trim((string) ($data['full_description'] ?? $data['description'] ?? '')),
            trim((string) ($data['terms_conditions'] ?? $data['description'] ?? '')),
            trim((string) ($data['image_path'] ?? '')),
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            (bool) ($data['active'] ?? false)
        );
    }
}
