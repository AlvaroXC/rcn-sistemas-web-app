<?php

namespace RcnSistemas\Services\Licenses;

use DateTimeImmutable;
use InvalidArgumentException;

final class LicenseCatalog
{
    public const EXPIRING_WINDOW_DAYS = 60;

    /** Días naturales que agrega una renovación, como los cuenta CONTPAQi. */
    public const PERIOD_DAYS = ['anual' => 365, 'mensual' => 30];

    public static function types(): array
    {
        return ['anual' => 'Anual', 'tradicional' => 'Tradicional', 'mensual' => 'Mensual'];
    }

    public static function rfcScopes(): array
    {
        return ['multi' => 'Multi-RFC', 'mono' => 'Mono-RFC'];
    }

    public static function statuses(): array
    {
        return ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencida' => 'Vencida', 'vitalicia' => 'Vitalicia', 'sin_fecha' => 'Sin fecha'];
    }

    public const DEFAULT_LIST_FILTER = 'por_vencer_60';

    /** Opciones del filtro Estado de la lista de licencias, en el orden en que se muestran. */
    public static function listFilters(): array
    {
        return [
            'por_vencer_60' => 'Por vencer en 60 días',
            'por_vencer_30' => 'Por vencer en 30 días',
            'por_vencer_15' => 'Por vencer en 15 días',
            'vigente' => 'Vigentes',
            'vencida' => 'Vencidas',
            'vitalicia' => 'Tradicionales',
            'todas' => 'Todas',
        ];
    }

    /** Vacío, desconocido o el antiguo "por_vencer" equivalen a la vista por defecto. */
    public static function normalizeListFilter(string $filter): string
    {
        return array_key_exists($filter, self::listFilters()) ? $filter : self::DEFAULT_LIST_FILTER;
    }

    /** Días de la ventana de un filtro "por vencer", o null si el filtro no es de ese tipo. */
    public static function expiringDays(string $filter): ?int
    {
        return preg_match('/^por_vencer_(\d+)$/', $filter, $match) ? (int) $match[1] : null;
    }

    /** @return array<int, string> */
    public static function months(): array
    {
        return [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    }

    public static function typeLabel(string $type): string
    {
        return self::types()[$type] ?? 'No especificado';
    }

    public static function rfcScopeLabel(string $scope): string
    {
        return self::rfcScopes()[$scope] ?? 'No especificado';
    }

    public static function statusLabel(string $status): string
    {
        return self::statuses()[$status] ?? 'No especificado';
    }

    public static function status(string $type, ?string $expiresAt, string $today): string
    {
        if ($type === 'tradicional') {
            return 'vitalicia';
        }
        if ($expiresAt === null || $expiresAt === '') {
            return 'sin_fecha';
        }
        if ($expiresAt < $today) {
            return 'vencida';
        }
        $horizon = (new DateTimeImmutable($today))->modify('+' . self::EXPIRING_WINDOW_DAYS . ' days')->format('Y-m-d');

        return $expiresAt <= $horizon ? 'por_vencer' : 'vigente';
    }

    /**
     * Siguiente vencimiento al renovar. Si la licencia sigue vigente (o vence hoy), el periodo se suma
     * a su vencimiento para conservar los días restantes; si ya venció o no tiene fecha, se cuenta desde hoy.
     */
    public static function nextExpiration(string $type, ?string $expiresAt, string $today): string
    {
        $days = self::PERIOD_DAYS[$type] ?? null;
        if ($days === null) {
            throw new InvalidArgumentException('Las licencias tradicionales no vencen, no se pueden renovar.');
        }
        $base = $expiresAt !== null && $expiresAt !== '' && $expiresAt >= $today ? $expiresAt : $today;

        return (new DateTimeImmutable($base))->modify('+' . $days . ' days')->format('Y-m-d');
    }
}
