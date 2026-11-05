<?php

namespace RcnSistemas\Models;

final class LicenseQuotation
{
    public function __construct(
        public readonly int $id,
        public readonly int $licenseId,
        public readonly string $folio,
        public readonly string $quotedAt,
        public readonly string $status,
        public readonly ?string $notes
    ) {
    }

    public static function fromArray(array $data): self
    {
        $notes = trim((string) ($data['notes'] ?? ''));

        return new self(
            (int) ($data['id'] ?? 0),
            (int) ($data['license_id'] ?? 0),
            trim((string) ($data['folio'] ?? '')),
            (string) ($data['quoted_at'] ?? ''),
            (string) ($data['status'] ?? 'enviada'),
            $notes !== '' ? $notes : null
        );
    }
}
