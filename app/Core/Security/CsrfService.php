<?php

namespace RcnSistemas\Core\Security;

use RuntimeException;

final class CsrfService
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly SessionManager $session)
    {
    }

    public function token(): string
    {
        $this->session->start();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="'
            . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public function isValid(?string $token): bool
    {
        $expected = $this->token();

        return is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    public function validate(?string $token): void
    {
        if (!$this->isValid($token)) {
            throw new RuntimeException('El token de seguridad no es válido.');
        }
    }
}
