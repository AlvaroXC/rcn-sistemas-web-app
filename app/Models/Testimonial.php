<?php

namespace RcnSistemas\Models;

final class Testimonial
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $message,
        public readonly int $rating,
        public readonly ?string $date
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['message'] ?? '')),
            max(1, min(5, (int) ($data['rating'] ?? 5))),
            $data['date'] ?? null
        );
    }
}
