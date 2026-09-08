<?php

namespace RcnSistemas\Services\Documents;

use RcnSistemas\Models\Ticket;

final class ServiceOrderDocument
{
    public function html(Ticket $ticket): string
    {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html><meta charset="utf-8"><body><h1>Orden de servicio</h1><p><strong>Ticket:</strong> #' . $ticket->id . '</p><p><strong>Cliente:</strong> ' . $ticket->customerId . '</p><p><strong>Tipo:</strong> ' . $escape($ticket->consultationType) . '</p><p><strong>Estado:</strong> ' . $escape($ticket->status) . '</p><hr><p>' . nl2br($escape($ticket->message)) . '</p></body></html>';
    }
}
