<?php

namespace RcnSistemas\Models;

final class GoogleCalendarIntegration
{
    public function __construct(
        public readonly ?string $googleUserId,
        public readonly ?string $googleEmail,
        public readonly ?string $accessTokenEncrypted,
        public readonly ?string $refreshTokenEncrypted,
        public readonly ?string $tokenExpiresAt,
        public readonly string $calendarId,
        public readonly string $scopes,
        public readonly string $status,
        public readonly ?string $lastError,
        public readonly ?string $connectedAt,
        public readonly ?string $disconnectedAt
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            self::nullable($data['google_user_id'] ?? null),
            self::nullable($data['google_email'] ?? null),
            self::nullable($data['access_token_encrypted'] ?? null),
            self::nullable($data['refresh_token_encrypted'] ?? null),
            self::nullable($data['token_expires_at'] ?? null),
            (string) ($data['calendar_id'] ?? 'primary'),
            (string) ($data['scopes'] ?? ''),
            (string) ($data['status'] ?? 'disconnected'),
            self::nullable($data['last_error'] ?? null),
            self::nullable($data['connected_at'] ?? null),
            self::nullable($data['disconnected_at'] ?? null)
        );
    }

    private static function nullable(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
