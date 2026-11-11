<?php

namespace RcnSistemas\Services\Licenses;

use DateTimeImmutable;
use InvalidArgumentException;
use RcnSistemas\Models\LicenseQuotation;
use RcnSistemas\Repositories\CustomerLicenseRepository;
use RcnSistemas\Repositories\LicenseQuotationRepository;

final class LicenseQuotationService
{
    public function __construct(
        private readonly LicenseQuotationRepository $quotations,
        private readonly CustomerLicenseRepository $licenses
    ) {
    }

    public function save(array $data): LicenseQuotation
    {
        $quotationId = (int) ($data['id'] ?? 0);
        $current = $quotationId > 0 ? $this->quotations->find($quotationId) : null;
        if ($quotationId > 0 && !$current) {
            throw new InvalidArgumentException('La cotización no existe.');
        }
        // Una cotización existente nunca cambia de licencia.
        $licenseId = $current ? $current->licenseId : (int) ($data['license_id'] ?? 0);
        if (!$this->licenses->find($licenseId)) {
            throw new InvalidArgumentException('La licencia de la cotización no existe.');
        }

        $folio = trim((string) ($data['folio'] ?? ''));
        if ($folio === '' || mb_strlen($folio) > 50) {
            throw new InvalidArgumentException('Indica el folio de la cotización (máximo 50 caracteres).');
        }

        $quotedAt = trim((string) ($data['quoted_at'] ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $quotedAt);
        if (!$date || $date->format('Y-m-d') !== $quotedAt) {
            throw new InvalidArgumentException('Indica una fecha de cotización válida.');
        }

        $status = $current ? (string) ($data['status'] ?? $current->status) : QuotationCatalog::INITIAL_STATUS;
        if (!array_key_exists($status, QuotationCatalog::statuses())) {
            throw new InvalidArgumentException('Selecciona un estado de cotización válido.');
        }
        if ($current && !QuotationCatalog::canTransition($current->status, $status)) {
            $allowed = array_map(QuotationCatalog::statusLabel(...), QuotationCatalog::TRANSITIONS[$current->status] ?? []);
            throw new InvalidArgumentException('Una cotización ' . QuotationCatalog::statusLabel($current->status) . ' solo puede cambiar a: ' . implode(', ', $allowed) . '.');
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if (mb_strlen($notes) > 5000) {
            throw new InvalidArgumentException('Las observaciones superan el máximo de 5000 caracteres.');
        }
        if ($status === 'rechazada' && $notes === '') {
            throw new InvalidArgumentException('Registra en observaciones el motivo por el que el cliente rechazó la cotización.');
        }

        $existing = $this->quotations->findByFolio($folio);
        if ($existing && $existing->id !== $quotationId) {
            throw new InvalidArgumentException('Ya existe una cotización registrada con el folio ' . $existing->folio . '.');
        }

        $quotation = $this->quotations->save(['id' => $quotationId, 'license_id' => $licenseId, 'folio' => $folio, 'quoted_at' => $quotedAt, 'status' => $status, 'notes' => $notes !== '' ? $notes : null]);
        if (!$quotation) {
            throw new InvalidArgumentException('No se pudo guardar la cotización.');
        }

        return $quotation;
    }
}
