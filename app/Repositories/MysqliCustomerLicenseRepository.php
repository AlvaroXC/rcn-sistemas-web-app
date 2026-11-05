<?php

namespace RcnSistemas\Repositories;

use DateTimeImmutable;
use mysqli;
use RcnSistemas\Models\CustomerLicense;
use RcnSistemas\Services\Licenses\LicenseCatalog;
use RcnSistemas\Services\Licenses\LicenseListFilter;

final class MysqliCustomerLicenseRepository implements CustomerLicenseRepository
{
    /** Folio y estado de la cotización más reciente de cada licencia (por fecha y luego por registro). */
    private const LAST_QUOTATION = '(SELECT q.folio FROM license_quotations q WHERE q.license_id = l.id ORDER BY q.quoted_at DESC, q.id DESC LIMIT 1) AS last_quotation_folio, '
        . '(SELECT q.status FROM license_quotations q WHERE q.license_id = l.id ORDER BY q.quoted_at DESC, q.id DESC LIMIT 1) AS last_quotation_status';

    public function __construct(private readonly mysqli $connection) {}

    public function find(int $licenseId): ?CustomerLicense
    {
        $statement = $this->connection->prepare('SELECT * FROM customer_licenses WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $licenseId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? CustomerLicense::fromArray($row) : null;
    }

    public function findBySerialNumber(string $serialNumber): ?CustomerLicense
    {
        $statement = $this->connection->prepare('SELECT * FROM customer_licenses WHERE serial_number = ? LIMIT 1');
        $statement->bind_param('s', $serialNumber); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? CustomerLicense::fromArray($row) : null;
    }

    public function forCustomer(int $customerId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM customer_licenses WHERE customer_id = ? ORDER BY product_name ASC, id ASC');
        $statement->bind_param('i', $customerId); $statement->execute(); $result = $statement->get_result();
        $licenses = [];
        while ($result && $row = $result->fetch_assoc()) {
            $licenses[] = CustomerLicense::fromArray($row);
        }
        return $licenses;
    }

    public function count(LicenseListFilter $filter): int
    {
        [$where, $types, $params] = $this->parts($filter);
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM customer_licenses l INNER JOIN customers c ON c.id = l.customer_id' . $where);
        $this->bind($statement, $types, $params);
        $statement->execute();
        return (int) $statement->get_result()->fetch_row()[0];
    }

    public function list(LicenseListFilter $filter, int $limit, int $offset): array
    {
        [$where, $types, $params] = $this->parts($filter);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $statement = $this->connection->prepare('SELECT l.*, c.name AS customer_name, c.business_name, ' . self::LAST_QUOTATION . ' FROM customer_licenses l INNER JOIN customers c ON c.id = l.customer_id' . $where . ' ORDER BY l.expires_at IS NULL ASC, l.expires_at ASC, c.name ASC, l.id ASC LIMIT ? OFFSET ?');
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        $this->bind($statement, $types, $params);
        $statement->execute();
        $result = $statement->get_result();
        $licenses = [];
        while ($result && $row = $result->fetch_assoc()) {
            $licenses[] = $row;
        }
        return $licenses;
    }

    public function expirationYears(): array
    {
        $result = $this->connection->query('SELECT DISTINCT YEAR(expires_at) FROM customer_licenses WHERE expires_at IS NOT NULL ORDER BY 1');
        $years = [];
        while ($result && $row = $result->fetch_row()) {
            $years[] = (int) $row[0];
        }
        return $years;
    }

    public function save(array $data): ?CustomerLicense
    {
        $licenseId = (int) ($data['id'] ?? 0); $customerId = (int) $data['customer_id']; $productName = (string) $data['product_name']; $alias = $data['alias'] ?? null;
        $licenseType = (string) $data['license_type']; $rfcScope = (string) $data['rfc_scope']; $usersCount = (int) $data['users_count'];
        $serialNumber = $data['serial_number']; $version = $data['version']; $expiresAt = $data['expires_at']; $notes = $data['notes'];
        if ($licenseId > 0) {
            $statement = $this->connection->prepare('UPDATE customer_licenses SET product_name=?, alias=?, license_type=?, rfc_scope=?, users_count=?, serial_number=?, version=?, expires_at=?, notes=? WHERE id=?');
            $statement->bind_param('ssssissssi', $productName, $alias, $licenseType, $rfcScope, $usersCount, $serialNumber, $version, $expiresAt, $notes, $licenseId);
            return $statement->execute() ? $this->find($licenseId) : null;
        }
        $statement = $this->connection->prepare('INSERT INTO customer_licenses (customer_id, product_name, alias, license_type, rfc_scope, users_count, serial_number, version, expires_at, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param('issssissss', $customerId, $productName, $alias, $licenseType, $rfcScope, $usersCount, $serialNumber, $version, $expiresAt, $notes);
        return $statement->execute() ? $this->find((int) $this->connection->insert_id) : null;
    }

    public function renew(int $licenseId, ?string $expectedExpiresAt, string $newExpiresAt, ?string $notes): bool
    {
        $statement = $this->connection->prepare('UPDATE customer_licenses SET expires_at = ?, notes = ? WHERE id = ? AND expires_at <=> ?');
        $statement->bind_param('ssis', $newExpiresAt, $notes, $licenseId, $expectedExpiresAt);
        return $statement->execute() && $statement->affected_rows === 1;
    }

    public function delete(int $licenseId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM customer_licenses WHERE id = ?');
        $statement->bind_param('i', $licenseId);
        return $statement->execute() && $statement->affected_rows > 0;
    }

    private function parts(LicenseListFilter $filter): array
    {
        $conditions = [];
        $types = '';
        $params = [];
        if ($filter->search !== '') {
            $like = '%' . addcslashes($filter->search, '%_\\') . '%';
            $conditions[] = '(c.name LIKE ? OR c.business_name LIKE ? OR c.rfc LIKE ? OR l.product_name LIKE ? OR l.alias LIKE ? OR l.serial_number LIKE ?)';
            $types .= 'ssssss';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        $today = $filter->today;
        $shift = static fn (int $days): string => (new DateTimeImmutable($today))->modify('+' . $days . ' days')->format('Y-m-d');
        $recurring = "l.license_type <> 'tradicional'";
        $days = $filter->expiringDays();
        if ($days !== null) {
            $conditions[] = $recurring . ' AND l.expires_at BETWEEN ? AND ?';
            $types .= 'ss';
            array_push($params, $today, $shift($days));
        } elseif ($filter->status === 'vitalicia') {
            $conditions[] = "l.license_type = 'tradicional'";
        } elseif ($filter->status === 'vencida') {
            $conditions[] = $recurring . ' AND l.expires_at < ?';
            $types .= 's';
            $params[] = $today;
        } elseif ($filter->status === 'vigente') {
            // Igual que la etiqueta: vigente es más allá de la ventana de "por vencer".
            $conditions[] = $recurring . ' AND l.expires_at > ?';
            $types .= 's';
            $params[] = $shift(LicenseCatalog::EXPIRING_WINDOW_DAYS);
        }

        // Mes y año sobre el vencimiento. Con ambos se usa un rango, que aprovecha el índice de expires_at.
        if ($filter->month !== null && $filter->year !== null) {
            $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $filter->year, $filter->month));
            $conditions[] = 'l.expires_at BETWEEN ? AND ?';
            $types .= 'ss';
            array_push($params, $first->format('Y-m-d'), $first->format('Y-m-t'));
        } elseif ($filter->month !== null) {
            $conditions[] = 'MONTH(l.expires_at) = ?';
            $types .= 'i';
            $params[] = $filter->month;
        } elseif ($filter->year !== null) {
            $conditions[] = 'l.expires_at BETWEEN ? AND ?';
            $types .= 'ss';
            array_push($params, sprintf('%04d-01-01', $filter->year), sprintf('%04d-12-31', $filter->year));
        }

        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $types, $params];
    }

    private function bind(\mysqli_stmt $statement, string $types, array $params): void
    {
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
    }
}
