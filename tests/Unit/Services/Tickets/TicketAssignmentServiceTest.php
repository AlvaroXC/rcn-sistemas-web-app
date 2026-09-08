<?php

namespace Tests\Unit\Services\Tickets;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketAssignmentService;

final class TicketAssignmentServiceTest extends TestCase
{
    public function test_only_an_admin_can_assign_a_ticket(): void
    {
        $service = new TicketAssignmentService();
        self::assertTrue($service->canAssign('admin', 3));
        self::assertFalse($service->canAssign('agent', 3));
    }
}
