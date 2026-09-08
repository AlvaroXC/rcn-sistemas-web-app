<?php

namespace RcnSistemas\Services\Communications;

use RcnSistemas\Models\Customer;
use RcnSistemas\Models\Ticket;

final class ContactMailTemplate
{
    public function render(Ticket $ticket, Customer $customer, array $data): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $body = '<h2>Nuevo ticket de contacto</h2><p><strong>Ticket:</strong> #' . $ticket->id . '</p><p><strong>Nombre:</strong> ' . $escape($customer->name) . '</p><p><strong>Email:</strong> ' . $escape($customer->email ?: 'No proporcionado') . '</p><p><strong>Teléfono:</strong> ' . $escape($customer->phone) . '</p><p><strong>Consulta:</strong> ' . $escape($ticket->consultationType) . '</p>';
        if (trim((string) ($data['context_label'] ?? '')) !== '') $body .= '<p><strong>Contexto:</strong> ' . $escape($data['context_label']) . '</p>';
        return '<html><body>' . $body . '<p><strong>Mensaje:</strong><br>' . nl2br($escape($ticket->message)) . '</p></body></html>';
    }
}
