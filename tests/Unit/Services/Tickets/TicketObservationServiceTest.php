<?php

namespace Tests\Unit\Services\Tickets;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketObservationService;

final class TicketObservationServiceTest extends TestCase
{
    public function test_it_trims_observations(): void
    {
        self::assertSame('Nota interna', (new TicketObservationService())->validate(' Nota interna '));
    }
}
