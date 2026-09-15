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

    public function test_it_accepts_mysql_time_with_seconds_for_appointments(): void
    {
        (new TicketScheduleService())->validateAppointment('2026-09-15', '10:30:00');
        self::assertTrue(true);
    }
}
