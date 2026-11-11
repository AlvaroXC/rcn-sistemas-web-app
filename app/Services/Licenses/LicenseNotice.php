<?php

namespace RcnSistemas\Services\Licenses;

/** Mensaje de una sola lectura para mostrar el resultado de una renovación después de redirigir. */
final class LicenseNotice
{
    private const KEY = 'license_notice';

    public static function push(string $type, string $message): void
    {
        $_SESSION['_flash_messages'][self::KEY] = ['type' => $type === 'error' ? 'error' : 'success', 'message' => $message];
    }

    /** @return array{type: string, message: string}|null */
    public static function pull(): ?array
    {
        $notice = $_SESSION['_flash_messages'][self::KEY] ?? null;
        unset($_SESSION['_flash_messages'][self::KEY]);

        return is_array($notice) ? $notice : null;
    }
}
