<?php

namespace RcnSistemas\Core\Security;

final class SessionManager
{
    public function __construct(
        private readonly int $timeout = 1800,
        private readonly string $sessionName = 'rcn_admin_session'
    ) {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->sessionName);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function authenticate(array $user): void
    {
        $this->start();

        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['admin_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['admin_user'] = trim((string) ($user['display_name'] ?? '')) ?: (string) ($user['username'] ?? '');
        $_SESSION['admin_role'] = trim((string) ($user['role'] ?? 'admin')) ?: 'admin';
        $_SESSION['last_activity'] = time();
    }

    public function isAuthenticated(): bool
    {
        $this->start();

        return $this->userId() > 0 && !$this->hasExpired();
    }

    public function userId(): int
    {
        return (int) ($_SESSION['admin_id'] ?? 0);
    }

    public function role(): string
    {
        return trim((string) ($_SESSION['admin_role'] ?? 'admin')) ?: 'admin';
    }

    public function userLabel(): string
    {
        return trim((string) ($_SESSION['admin_user'] ?? ''));
    }

    public function hasExpired(): bool
    {
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

        return $lastActivity > 0 && time() - $lastActivity > $this->timeout;
    }

    public function touch(): void
    {
        $this->start();
        $_SESSION['last_activity'] = time();
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    private function isHttpsRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    }
}
