<?php

namespace Tests\Unit\Services\Documents;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\Ticket;
use RcnSistemas\Services\Documents\ServiceOrderDocument;

final class ServiceOrderDocumentTest extends TestCase
{
    public function test_it_includes_the_complete_service_order_details(): void
    {
        $ticket = Ticket::fromArray([
            'id' => 18,
            'customer_name' => 'Cliente Demo',
            'customer_business_name' => 'Empresa Demo',
            'customer_email' => 'demo@example.com',
            'customer_phone' => '9991234567',
            'consultation_type' => 'Soporte',
            'status' => 'Programado',
            'assigned_display_name' => 'Ana',
            'subject' => 'Asunto',
            'context_label' => 'Contexto',
            'message' => "Detalle importante\nsegunda línea",
            'price_total_amount' => 1250,
            'price_currency' => 'MXN',
            'service_date' => '2026-09-10',
            'service_start_time' => '09:00:00',
            'service_end_time' => '11:30:00',
            'created_at' => '2026-09-09 10:00:00',
        ]);

        $html = (new ServiceOrderDocument())->html($ticket);

        foreach (['Cliente Demo', 'Empresa Demo', 'demo@example.com', '9991234567', 'MXN 1,250.00', '09:00', '11:30', '2 h 30 min', 'Detalle importante', 'Firma del cliente'] as $value) {
            self::assertStringContainsString($value, $html);
        }
    }
}
