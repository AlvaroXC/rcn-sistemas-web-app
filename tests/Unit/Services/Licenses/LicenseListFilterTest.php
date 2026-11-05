<?php

namespace Tests\Unit\Services\Licenses;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Licenses\LicenseCatalog;
use RcnSistemas\Services\Licenses\LicenseListFilter;

final class LicenseListFilterTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function filters(): array
    {
        return [
            'sin estado' => ['', 'por_vencer_60'],
            'desconocido' => ['semanal', 'por_vencer_60'],
            'enlace antiguo' => ['por_vencer', 'por_vencer_60'],
            '15 dias' => ['por_vencer_15', 'por_vencer_15'],
            'todas' => ['todas', 'todas'],
            'tradicionales' => ['vitalicia', 'vitalicia'],
        ];
    }

    #[DataProvider('filters')]
    public function test_it_normalizes_the_status_filter(string $filter, string $expected): void
    {
        $this->assertSame($expected, LicenseCatalog::normalizeListFilter($filter));
    }

    public function test_it_returns_the_window_days_of_expiring_filters(): void
    {
        $this->assertSame([60, 30, 15, null, null], array_map(LicenseCatalog::expiringDays(...), ['por_vencer_60', 'por_vencer_30', 'por_vencer_15', 'vencida', 'todas']));
    }

    public function test_it_builds_a_valid_filter_from_the_query(): void
    {
        $filter = LicenseListFilter::fromQuery(['search' => ' acme ', 'status' => 'por_vencer_30', 'month' => '11', 'year' => '2026'], '2026-11-04');

        $this->assertSame(['acme', 'por_vencer_30', 11, 2026, '2026-11-04', 30], [$filter->search, $filter->status, $filter->month, $filter->year, $filter->today, $filter->expiringDays()]);
    }

    public function test_it_defaults_to_expiring_in_60_days_with_all_months_and_years(): void
    {
        $filter = LicenseListFilter::fromQuery([], '2026-11-04');

        $this->assertSame(['', 'por_vencer_60', null, null], [$filter->search, $filter->status, $filter->month, $filter->year]);
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidPeriods(): array
    {
        return [
            'mes cero' => [['month' => '0', 'year' => '26']],
            'mes trece' => [['month' => '13', 'year' => '20261']],
            'texto' => [['month' => 'nov', 'year' => 'dos mil']],
            'todos' => [['month' => '', 'year' => '']],
        ];
    }

    #[DataProvider('invalidPeriods')]
    public function test_it_ignores_invalid_month_and_year(array $query): void
    {
        $filter = LicenseListFilter::fromQuery($query, '2026-11-04');

        $this->assertNull($filter->month);
        $this->assertNull($filter->year);
    }
}
