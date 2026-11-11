<?php

namespace Tests\Unit\Services;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\PromotionService;

final class PromotionServiceTest extends TestCase
{
    public function test_it_rejects_dates_in_the_wrong_order(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PromotionService())->validate([
            'title' => 'Oferta',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
        ]);
    }

    public function test_it_accepts_a_valid_promotion(): void
    {
        (new PromotionService())->validate([
            'title' => 'Oferta',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        self::assertTrue(true);
    }
}
