<?php

namespace Tests\Unit\Services\Tickets;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Tickets\TicketPricing;

final class TicketPricingTest extends TestCase
{
    public function test_it_calculates_iva_and_total_for_an_individual_customer(): void
    {
        $price = (new TicketPricing())->calculate(1000, 'persona_fisica');

        self::assertSame(160.0, $price['iva']);
        self::assertSame(1160.0, $price['total']);
    }
}
