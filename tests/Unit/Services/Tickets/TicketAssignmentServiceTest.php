<?php

namespace Tests\Unit\Services\Tickets;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketAssignmentService;

final class TicketAssignmentServiceTest extends TestCase
{
    public function test_an_assigned_operator_can_transfer_a_ticket(): void
    {
        $service = new TicketAssignmentService();
        self::assertTrue($service->canAssign('admin', 3));
        self::assertTrue($service->canAssign('agent', 3));
        self::assertFalse($service->canAssign('agent', 0));
    }
}
