<?php

namespace RcnSistemas\Services\Communications;

use InvalidArgumentException;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Tickets\TicketService;

final class ContactService
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly TicketService $tickets,
        private readonly UploadService $uploads,
        private readonly MailService $mail
    ) {
    }

    public function createTicket(array $data, ?array $file = null, array $uploadRules = []): Ticket
    {
        if ($file !== null) {
            $this->uploads->validate($file, $uploadRules);
        }

        $customer = $this->customers->save([
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? '',
            'business_name' => $data['business_name'] ?? '',
            'customer_type' => $data['customer_type'] ?? 'persona_fisica',
        ]);

        $ticket = $this->tickets->create([
            'customer_id' => $customer->id,
            'consultation_type' => $data['consultation_type'] ?? 'Informacion',
            'status' => 'Nuevo',
            'origin_type' => $data['origin_type'] ?? 'contact',
            'product_id' => $data['product_id'] ?? null,
            'promo_id' => $data['promo_id'] ?? null,
            'context_label' => $data['context_label'] ?? '',
            'subject' => $data['subject'] ?? 'Consulta desde formulario de contacto',
            'message' => $data['message'] ?? '',
            'attachment_path' => $data['attachment_path'] ?? null,
        ]);

        $this->mail->send(
            'Nuevo ticket de contacto - Web RCN',
            $this->buildMailBody($ticket, $customer->name, $customer->email, $customer->phone, $data),
            $data['attachment_full_path'] ?? null
        );

        return $ticket;
    }

    public function buildMailBody(Ticket $ticket, string $name, ?string $email, string $phone, array $data): string
    {
        if ($ticket->id <= 0) {
            throw new InvalidArgumentException('El ticket no tiene un identificador válido.');
        }

        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $context = trim((string) ($data['context_label'] ?? ''));

        $body = '<h2>Nuevo ticket de contacto</h2>';
        $body .= '<p><strong>Ticket:</strong> #' . $ticket->id . '</p>';
        $body .= '<p><strong>Nombre:</strong> ' . $escape($name) . '</p>';
        $body .= '<p><strong>Email:</strong> ' . $escape($email ?: 'No proporcionado') . '</p>';
        $body .= '<p><strong>Teléfono:</strong> ' . $escape($phone) . '</p>';
        $body .= '<p><strong>Consulta:</strong> ' . $escape($ticket->consultationType) . '</p>';
        if ($context !== '') {
            $body .= '<p><strong>Contexto:</strong> ' . $escape($context) . '</p>';
        }
        $body .= '<p><strong>Mensaje:</strong><br>' . nl2br($escape($ticket->message)) . '</p>';

        return '<html><body>' . $body . '</body></html>';
    }
}
