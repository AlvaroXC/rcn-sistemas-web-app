<?php

namespace Tests\Unit\Services;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\TestimonialService;

final class TestimonialServiceTest extends TestCase
{
    public function test_it_rejects_an_invalid_rating(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TestimonialService())->validate(['name' => 'Cliente', 'message' => 'Comentario', 'rating' => 6]);
    }

    public function test_it_accepts_a_complete_testimonial(): void
    {
        (new TestimonialService())->validate(['name' => 'Cliente', 'message' => 'Comentario', 'rating' => 5]);

        self::assertTrue(true);
    }
}
