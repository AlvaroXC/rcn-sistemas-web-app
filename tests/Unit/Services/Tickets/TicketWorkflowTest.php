<?php

namespace Tests\Unit\Services\Tickets;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketWorkflow;

final class TicketWorkflowTest extends TestCase
{
    public function test_closed_tickets_cannot_be_edited(): void
    {
        self::assertFalse((new TicketWorkflow())->canEdit(['status' => 'Cerrado']));
    }

    public function test_it_rejects_unknown_statuses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TicketWorkflow())->validateStatus('Desconocido');
    }
}
