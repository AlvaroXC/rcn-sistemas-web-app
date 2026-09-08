<?php

namespace Tests\Unit\Services\Tickets;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketWorkflow;

final class TicketWorkflowStatusesTest extends TestCase
{
    public function test_it_exposes_the_complete_status_catalog(): void
    {
        self::assertSame(['Nuevo', 'Agendado', 'En revision', 'En proceso', 'Pendiente de respuesta', 'Por facturar', 'Resuelto', 'Cerrado'], (new TicketWorkflow())->statuses());
    }
}
