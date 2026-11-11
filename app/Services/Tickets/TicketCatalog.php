<?php

namespace RcnSistemas\Services\Tickets;

final class TicketCatalog
{
    public const TYPES = ['Compra', 'Soporte', 'Informacion'];
    public const ORIGINS = ['contact', 'product', 'promotion', 'admin'];

    public static function labels(): array
    {
        return ['Compra' => 'Compra', 'Soporte' => 'Soporte técnico', 'Informacion' => 'Información general'];
    }
}
