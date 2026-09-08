<?php

namespace Tests\Unit\Services\Customers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Customers\CustomerIdentity;

final class CustomerIdentityTest extends TestCase
{
    public function test_it_normalizes_a_phone(): void
    {
        self::assertSame('+529999942427', (new CustomerIdentity())->normalizePhone('+52 (999) 994-2427'));
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CustomerIdentity())->normalizeEmail('invalid');
    }
}
