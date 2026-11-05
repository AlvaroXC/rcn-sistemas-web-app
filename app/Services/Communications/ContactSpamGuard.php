<?php

namespace RcnSistemas\Services\Communications;

use InvalidArgumentException;

/**
 * Filtros anti-spam para el formulario de contacto.
 */
final class ContactSpamGuard
{
    public const HONEYPOT_FIELD = 'sitio_web';

    public function __construct(
        private readonly ?string $storageDirectory = null,
        private readonly int $maxAttempts = 3,
        private readonly int $windowSeconds = 3600
    ) {
    }

    /** Un humano nunca ve ni llena el campo oculto; un bot sí. */
    public function isHoneypotTriggered(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }

    /** @throws InvalidArgumentException */
    public function assertLooksHuman(string $name, string $message): void
    {
        if ($this->containsLinkOrHtml($name)) {
            throw new InvalidArgumentException('El nombre no puede contener enlaces ni código.');
        }
        if ($this->looksLikeGibberishName($name)) {
            throw new InvalidArgumentException('Escribe tu nombre real, por favor.');
        }
        if (preg_match_all('~https?://|www\.~i', $message) > 2) {
            throw new InvalidArgumentException('El mensaje contiene demasiados enlaces.');
        }
    }

    /** Límite de envíos por IP. Lanza excepción si se excede. */
    public function assertWithinRateLimit(string $ip): void
    {
        $directory = $this->storageDirectory ?? sys_get_temp_dir() . '/rcn_contact_limit';
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return; // si no se puede guardar, no bloqueamos a usuarios reales
        }
        $file = $directory . '/' . hash('sha256', $ip) . '.json';
        $now = time();
        $attempts = [];
        if (is_file($file)) {
            $decoded = json_decode((string) @file_get_contents($file), true);
            $attempts = is_array($decoded) ? $decoded : [];
        }
        $attempts = array_values(array_filter($attempts, fn ($t) => is_int($t) && $t > $now - $this->windowSeconds));
        if (count($attempts) >= $this->maxAttempts) {
            throw new InvalidArgumentException('Has enviado demasiados mensajes. Inténtalo de nuevo más tarde.');
        }
        $attempts[] = $now;
        @file_put_contents($file, json_encode($attempts), LOCK_EX);
    }

    private function containsLinkOrHtml(string $value): bool
    {
        return preg_match('~https?://|www\.|<[^>]*>|\.(com|net|org|ru|xyz)\b~i', $value) === 1;
    }

    private function looksLikeGibberishName(string $name): bool
    {
        $name = trim($name);
        if (preg_match('/\d{3,}/', $name)) {
            return true; // varios dígitos seguidos
        }
        if (!str_contains($name, ' ') && mb_strlen($name) >= 12 && preg_match('/\d/', $name)) {
            return true; // una sola palabra larga con números
        }
        if (!str_contains($name, ' ') && mb_strlen($name) >= 15 && $name === mb_strtoupper($name)) {
            return true; // palabra larga toda en mayúsculas
        }
        return false;
    }
}
