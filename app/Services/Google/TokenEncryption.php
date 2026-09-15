<?php

namespace RcnSistemas\Services\Google;

use RuntimeException;

final class TokenEncryption
{
    private const NONCE_BYTES = 24;

    private string $key;

    public function __construct(private readonly string $secret) {}

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        $this->ensureSodiumAvailable();
        $nonce = random_bytes(self::NONCE_BYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $this->key()));
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        $this->ensureSodiumAvailable();
        $payload = base64_decode($value, true);
        if ($payload === false || strlen($payload) <= self::NONCE_BYTES) throw new RuntimeException('No se pudo descifrar el token de Google.');
        $plain = sodium_crypto_secretbox_open(substr($payload, self::NONCE_BYTES), substr($payload, 0, self::NONCE_BYTES), $this->key());
        if ($plain === false) throw new RuntimeException('No se pudo descifrar el token de Google.');
        return $plain;
    }

    private function ensureSodiumAvailable(): void
    {
        if (!function_exists('sodium_crypto_secretbox') || !function_exists('sodium_crypto_secretbox_open')) {
            throw new RuntimeException('La extensión Sodium de PHP no está disponible.');
        }
    }

    private function key(): string
    {
        if (trim($this->secret) === '') throw new RuntimeException('APP_ENCRYPTION_KEY no está configurada.');
        return hash('sha256', $this->secret, true);
    }
}
