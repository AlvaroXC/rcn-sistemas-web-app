<?php

namespace RcnSistemas\Services\Customers;

/** Mensaje de una sola lectura con el resultado de guardar o eliminar un contacto. */
final class ContactNotice
{
    private const KEY = 'contact_notice';

    public static function push(string $message): void
    {
        $_SESSION['_flash_messages'][self::KEY] = ['type' => 'success', 'message' => $message];
    }

    /** @return array{type: string, message: string}|null */
    public static function pull(): ?array
    {
        $notice = $_SESSION['_flash_messages'][self::KEY] ?? null;
        unset($_SESSION['_flash_messages'][self::KEY]);

        return is_array($notice) ? $notice : null;
    }
}
