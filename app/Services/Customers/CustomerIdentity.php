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

    public function normalizeRfc(mixed $rfc): ?string
    {
        $rfc = mb_strtoupper(preg_replace('/[\s-]+/u', '', (string) ($rfc ?? '')) ?? '');
        if ($rfc === '') {
            return null;
        }
        if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc)) {
            throw new InvalidArgumentException('El RFC no es válido.');
        }

        return $rfc;
    }

    public function customerTypeForRfc(string $rfc): string
    {
        return mb_strlen($rfc) === 12 ? 'persona_moral' : 'persona_fisica';
    }
}
