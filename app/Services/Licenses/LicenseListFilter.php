<?php

namespace RcnSistemas\Services\Licenses;

/** Filtros ya validados de la lista de licencias. */
final class LicenseListFilter
{
    public function __construct(
        public readonly string $search,
        public readonly string $status,
        public readonly ?int $month,
        public readonly ?int $year,
        public readonly string $today
    ) {
    }

    /** Valores inválidos no producen error: el estado cae al valor por defecto y mes/año a "Todos". */
    public static function fromQuery(array $query, string $today): self
    {
        $month = filter_var($query['month'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $year = preg_match('/^\d{4}$/', (string) ($query['year'] ?? '')) ? (int) $query['year'] : null;

        return new self(
            trim((string) ($query['search'] ?? '')),
            LicenseCatalog::normalizeListFilter((string) ($query['status'] ?? '')),
            $month === false ? null : $month,
            $year,
            $today
        );
    }

    public function expiringDays(): ?int
    {
        return LicenseCatalog::expiringDays($this->status);
    }
}
