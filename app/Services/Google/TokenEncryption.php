<?php

namespace RcnSistemas\Services\Google;

use RuntimeException;

final class TokenEncryption
{
    private string $key;

    public function __construct(private readonly string $secret) {}

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $this->key()));
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        $payload = base64_decode($value, true);
        if ($payload === false || strlen($payload) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) throw new RuntimeException('No se pudo descifrar el token de Google.');
        $plain = sodium_crypto_secretbox_open(substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $this->key());
        if ($plain === false) throw new RuntimeException('No se pudo descifrar el token de Google.');
        return $plain;
    }

    private function key(): string
    {
        if (trim($this->secret) === '') throw new RuntimeException('APP_ENCRYPTION_KEY no está configurada.');
        return hash('sha256', $this->secret, true);
    }
}
