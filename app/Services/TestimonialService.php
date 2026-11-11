<?php

namespace RcnSistemas\Services;

use InvalidArgumentException;

final class TestimonialService
{
    public function validate(array $data): void
    {
        if (trim((string) ($data['name'] ?? '')) === '' || trim((string) ($data['message'] ?? '')) === '') {
            throw new InvalidArgumentException('El nombre y el comentario son obligatorios.');
        }

        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('La calificación debe estar entre 1 y 5.');
        }
    }
}
