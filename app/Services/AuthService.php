<?php

namespace RcnSistemas\Services;

use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\AdminUserRepository;
use RcnSistemas\Repositories\AdminRememberTokenRepository;

final class AuthService
{
    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly SessionManager $session,
        private readonly ?AdminRememberTokenRepository $rememberTokens = null
    ) {
    }

    public function authenticate(string $username, string $password, bool $rememberMe = false): bool
    {
        $user = $this->users->findByUsername(trim($username));

        if (!$user || (int) ($user['active'] ?? 0) !== 1 || !password_verify($password, (string) ($user['password'] ?? ''))) {
            return false;
        }

        $this->session->authenticate($user);

        if ($rememberMe && $this->rememberTokens) {
            $this->issueRememberToken((int) $user['id']);
        } else {
            $this->revokeCurrentRememberToken();
        }

        return true;
    }

    public function restoreRememberedSession(): bool
    {
        if (!$this->rememberTokens) {
            return false;
        }

        $this->session->start();
        if ($this->session->userId() > 0 && !$this->session->hasExpired()) {
            return true;
        }
        if ($this->session->hasExpired()) {
            $this->session->clearAuthentication();
        }

        $payload = $this->rememberCookiePayload();
        if (!$payload) {
            $this->clearRememberCookie();
            return false;
        }

        $stored = $this->rememberTokens->findBySelector($payload['selector']);
        $expiresAt = $stored ? strtotime((string) ($stored['expires_at'] ?? '')) : false;
        if (!$stored || !$expiresAt || $expiresAt < time() || !hash_equals((string) $stored['token_hash'], hash('sha256', $payload['token']))) {
            $this->rememberTokens->deleteBySelector($payload['selector']);
            $this->clearRememberCookie();
            return false;
        }

        $user = $this->users->findById((int) $stored['user_id']);
        if (!$user || (int) ($user['active'] ?? 0) !== 1) {
            $this->rememberTokens->deleteBySelector($payload['selector']);
            $this->clearRememberCookie();
            return false;
        }

        $selector = bin2hex(random_bytes(12));
        $token = bin2hex(random_bytes(32));
        $expires = time() + 60 * 60 * 24 * 30;
        if (!$this->rememberTokens->rotate((int) $stored['id'], $selector, hash('sha256', $token), date('Y-m-d H:i:s', $expires))) {
            $this->rememberTokens->deleteBySelector($payload['selector']);
            $this->clearRememberCookie();
            return false;
        }

        $this->session->authenticate($user);
        $this->setRememberCookie($selector, $token, $expires);
        return true;
    }

    public function logout(): void
    {
        $this->revokeCurrentRememberToken();
        $this->session->destroy();
    }

    private function issueRememberToken(int $userId): void
    {
        $this->revokeCurrentRememberToken();
        $selector = bin2hex(random_bytes(12));
        $token = bin2hex(random_bytes(32));
        $expires = time() + 60 * 60 * 24 * 30;
        if ($this->rememberTokens?->create($userId, $selector, hash('sha256', $token), date('Y-m-d H:i:s', $expires))) {
            $this->setRememberCookie($selector, $token, $expires);
        }
    }

    private function revokeCurrentRememberToken(): void
    {
        $payload = $this->rememberCookiePayload();
        if ($payload && $this->rememberTokens) {
            $this->rememberTokens->deleteBySelector($payload['selector']);
        }
        $this->clearRememberCookie();
    }

    private function rememberCookiePayload(): ?array
    {
        $value = trim((string) ($_COOKIE['admin_remember_me'] ?? ''));
        $parts = explode(':', $value, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '' || !ctype_xdigit($parts[0]) || !ctype_xdigit($parts[1])) {
            return null;
        }

        return ['selector' => $parts[0], 'token' => $parts[1]];
    }

    private function setRememberCookie(string $selector, string $token, int $expires): void
    {
        $options = ['expires' => $expires, 'path' => '/', 'secure' => $this->isHttpsRequest(), 'httponly' => true, 'samesite' => 'Lax'];
        setcookie('admin_remember_me', $selector . ':' . $token, $options);
        $_COOKIE['admin_remember_me'] = $selector . ':' . $token;
    }

    private function clearRememberCookie(): void
    {
        if (!array_key_exists('admin_remember_me', $_COOKIE)) {
            return;
        }
        setcookie('admin_remember_me', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $this->isHttpsRequest(), 'httponly' => true, 'samesite' => 'Lax']);
        unset($_COOKIE['admin_remember_me']);
    }

    private function isHttpsRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
