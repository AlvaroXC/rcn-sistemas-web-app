<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\LicenseQuotation;

final class MysqliLicenseQuotationRepository implements LicenseQuotationRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function find(int $quotationId): ?LicenseQuotation
    {
        return $this->one('SELECT * FROM license_quotations WHERE id = ? LIMIT 1', 'i', $quotationId);
    }

    public function findByFolio(string $folio): ?LicenseQuotation
    {
        return $this->one('SELECT * FROM license_quotations WHERE folio = ? LIMIT 1', 's', $folio);
    }

    public function forLicense(int $licenseId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM license_quotations WHERE license_id = ? ORDER BY quoted_at DESC, id DESC');
        $statement->bind_param('i', $licenseId); $statement->execute(); $result = $statement->get_result();
        $quotations = [];
        while ($result && $row = $result->fetch_assoc()) {
            $quotations[] = LicenseQuotation::fromArray($row);
        }
        return $quotations;
    }

    public function save(array $data): ?LicenseQuotation
    {
        $quotationId = (int) ($data['id'] ?? 0); $licenseId = (int) $data['license_id']; $folio = (string) $data['folio'];
        $quotedAt = (string) $data['quoted_at']; $status = (string) $data['status']; $notes = $data['notes'];
        if ($quotationId > 0) {
            $statement = $this->connection->prepare('UPDATE license_quotations SET folio = ?, quoted_at = ?, status = ?, notes = ? WHERE id = ?');
            $statement->bind_param('ssssi', $folio, $quotedAt, $status, $notes, $quotationId);
            return $statement->execute() ? $this->find($quotationId) : null;
        }
        $statement = $this->connection->prepare('INSERT INTO license_quotations (license_id, folio, quoted_at, status, notes) VALUES (?, ?, ?, ?, ?)');
        $statement->bind_param('issss', $licenseId, $folio, $quotedAt, $status, $notes);
        return $statement->execute() ? $this->find((int) $this->connection->insert_id) : null;
    }

    public function delete(int $quotationId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM license_quotations WHERE id = ?');
        $statement->bind_param('i', $quotationId);
        return $statement->execute() && $statement->affected_rows > 0;
    }

    private function one(string $sql, string $type, int|string $value): ?LicenseQuotation
    {
        $statement = $this->connection->prepare($sql);
        $statement->bind_param($type, $value); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? LicenseQuotation::fromArray($row) : null;
    }
}
