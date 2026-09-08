<?php

namespace RcnSistemas\Services;

use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\AdminUserRepository;

final class AuthService
{
    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly SessionManager $session
    ) {
    }

    public function authenticate(string $username, string $password): bool
    {
        $user = $this->users->findByUsername(trim($username));

        if (!$user || (int) ($user['active'] ?? 0) !== 1 || !password_verify($password, (string) ($user['password'] ?? ''))) {
            return false;
        }

        $this->session->authenticate($user);

        return true;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
