<?php

namespace Tests\Unit\Core\Security;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;

final class CsrfServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        parent::tearDown();
    }

    public function test_it_accepts_the_generated_token_and_rejects_another_one(): void
    {
        $csrf = new CsrfService(new SessionManager(1800, 'rcn_test_' . bin2hex(random_bytes(4))));
        $token = $csrf->token();

        self::assertTrue($csrf->isValid($token));
        self::assertFalse($csrf->isValid('invalid-token'));
    }
}
