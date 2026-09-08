<?php

namespace Tests\Unit\Services\Google;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Google\TokenEncryption;

final class TokenEncryptionTest extends TestCase
{
    public function test_it_encrypts_and_decrypts_a_token(): void
    {
        $encryption = new TokenEncryption('test-secret');
        $encrypted = $encryption->encrypt('refresh-token');

        self::assertNotSame('refresh-token', $encrypted);
        self::assertSame('refresh-token', $encryption->decrypt($encrypted));
    }

    public function test_it_rejects_an_empty_encryption_key_when_used(): void
    {
        $this->expectException(\RuntimeException::class);
        (new TokenEncryption(''))->encrypt('token');
    }
}
