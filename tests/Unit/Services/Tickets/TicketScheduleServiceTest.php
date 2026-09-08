<?php

namespace Tests\Unit\Services\Tickets;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketScheduleService;

final class TicketScheduleServiceTest extends TestCase
{
    public function test_it_calculates_service_duration(): void
    {
        self::assertSame(90, (new TicketScheduleService())->durationMinutes('09:00', '10:30'));
    }
}
