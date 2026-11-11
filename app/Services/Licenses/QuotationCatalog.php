<?php

namespace RcnSistemas\Services\Licenses;

/** Estados de las cotizaciones de renovación y los cambios permitidos entre ellos. */
final class QuotationCatalog
{
    public const INITIAL_STATUS = 'enviada';

    /** Avances en orden y, para corregir errores, regreso solo al estado anterior. */
    public const TRANSITIONS = [
        'enviada' => ['aceptada', 'rechazada'],
        'aceptada' => ['facturada', 'enviada'],
        'facturada' => ['aceptada'],
        'rechazada' => ['enviada'],
    ];

    public static function statuses(): array
    {
        return ['enviada' => 'Enviada', 'aceptada' => 'Aceptada', 'facturada' => 'Facturada', 'rechazada' => 'Rechazada'];
    }

    public static function statusLabel(string $status): string
    {
        return self::statuses()[$status] ?? 'No especificado';
    }

    public static function canTransition(string $from, string $to): bool
    {
        return isset(self::TRANSITIONS[$from]) && ($from === $to || in_array($to, self::TRANSITIONS[$from], true));
    }

    /** @return array<string, string> estado actual y los que se pueden elegir desde él */
    public static function options(string $current): array
    {
        $allowed = [$current, ...(self::TRANSITIONS[$current] ?? [])];

        return array_intersect_key(self::statuses(), array_flip($allowed));
    }
}
