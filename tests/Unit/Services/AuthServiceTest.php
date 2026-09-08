<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\AdminUserRepository;
use RcnSistemas\Services\AuthService;

final class AuthServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        parent::tearDown();
    }

    public function test_it_authenticates_an_active_user_with_a_valid_password(): void
    {
        $repository = new InMemoryAdminUserRepository([
            'admin' => [
                'id' => 7,
                'username' => 'admin',
                'display_name' => 'Administrador',
                'role' => 'admin',
                'active' => 1,
                'password' => password_hash('secret', PASSWORD_DEFAULT),
            ],
        ]);
        $session = new SessionManager(1800, 'rcn_auth_test_' . bin2hex(random_bytes(4)));
        $auth = new AuthService($repository, $session);

        self::assertTrue($auth->authenticate('admin', 'secret'));
        self::assertSame(7, $session->userId());
        self::assertSame('admin', $session->role());
    }

    public function test_it_rejects_an_invalid_password(): void
    {
        $repository = new InMemoryAdminUserRepository([
            'admin' => [
                'id' => 7,
                'username' => 'admin',
                'active' => 1,
                'password' => password_hash('secret', PASSWORD_DEFAULT),
            ],
        ]);
        $auth = new AuthService($repository, new SessionManager(1800, 'rcn_auth_test_' . bin2hex(random_bytes(4))));

        self::assertFalse($auth->authenticate('admin', 'wrong'));
    }
}

final class InMemoryAdminUserRepository implements AdminUserRepository
{
    public function __construct(private readonly array $users)
    {
    }

    public function findByUsername(string $username): ?array
    {
        return $this->users[$username] ?? null;
    }

    public function findById(int $userId): ?array
    {
        foreach ($this->users as $user) {
            if ((int) $user['id'] === $userId) {
                return $user;
            }
        }

        return null;
    }
}
