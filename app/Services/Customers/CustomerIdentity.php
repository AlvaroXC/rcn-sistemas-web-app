<?php

namespace RcnSistemas\Services\Customers;

use InvalidArgumentException;

final class CustomerIdentity
{
    public function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
        if ($normalized === '') {
            throw new InvalidArgumentException('El teléfono es obligatorio.');
        }

        return $normalized;
    }

    public function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) ($email ?? ''));
        if ($email === '') {
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        return strtolower($email);
    }
}
