<?php

namespace RcnSistemas\Services\Customers;

final class CustomerCatalog
{
    public const TYPES = ['persona_fisica', 'persona_moral'];

    public static function labels(): array
    {
        return ['persona_fisica' => 'Persona física', 'persona_moral' => 'Persona moral'];
    }

    public static function label(?string $type): string
    {
        return self::labels()[$type ?? ''] ?? 'No especificado';
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }
}
