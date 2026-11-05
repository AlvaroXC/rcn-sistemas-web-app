<?php

namespace RcnSistemas\Services\Licenses\Import;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use RcnSistemas\Services\Customers\CustomerIdentity;

/**
 * Convierte las hojas "Anual" y "Tradicionales" del Excel de renovaciones en clientes
 * agrupados por RFC con sus licencias. Las columnas que el sistema no usa se ignoran.
 */
final class RenewalSpreadsheetMapper
{
    /** Columnas usadas por hoja. Los encabezados se validan para detectar cambios de formato. */
    private const LAYOUTS = [
        'Anual' => ['type' => 'anual', 'columns' => [
            'product' => ['A', 'producto'], 'serial' => ['B', 'serie'], 'kind' => ['C', 'version'], 'expires' => ['D', 'fecha caducidad'],
            'users' => ['E', 'usuarios'], 'rfc' => ['F', 'rfc'], 'company' => ['G', 'nombre empresa'], 'contact' => ['L', 'contacto'],
            'phone' => ['M', 'tel 1'], 'email' => ['N', 'correo contacto'],
        ]],
        'Tradicionales' => ['type' => 'tradicional', 'columns' => [
            'product' => ['A', 'sistema'], 'serial' => ['B', 'serie'], 'version' => ['C', 'version'], 'users' => ['D', 'cantidad usuarios'],
            'rfc' => ['E', 'rfc'], 'company' => ['F', 'nombre empresa'], 'contact' => ['G', 'contacto'], 'phone' => ['H', 'telefono empresa'],
            'email' => ['I', 'correo empresa'],
        ]],
    ];

    private const PRODUCTS = [
        'CONTABILIDAD' => 'CONTPAQi® Contabilidad',
        'NOMINAS' => 'CONTPAQi® Nóminas',
        'BANCOS' => 'CONTPAQi® Bancos',
        'PREMIUM' => 'CONTPAQi® Comercial Premium',
        'COMERCIAL PREMIUM' => 'CONTPAQi® Comercial Premium',
        'COMERCIAL START' => 'CONTPAQi® Comercial Start',
        'COMERCIAL PRO' => 'CONTPAQi® Comercial Pro',
        'FACTURA ELECTRONICA' => 'CONTPAQi® Factura Electrónica',
        'XML EN LINEA +' => 'CONTPAQi® XML En Línea+',
        'XML EN LINEA+' => 'CONTPAQi® XML En Línea+',
    ];

    /** @var list<string> */
    private array $warnings = [];

    public function __construct(private readonly CustomerIdentity $identity = new CustomerIdentity())
    {
    }

    /**
     * @param array<string, array<int, array<string, string>>> $sheets
     * @return array<string, array{customer: array<string, ?string>, licenses: list<array<string, mixed>>}> clientes por RFC
     */
    public function map(array $sheets, string $source): array
    {
        $this->warnings = [];
        $licenses = [];
        foreach (self::LAYOUTS as $sheetName => $layout) {
            if (!isset($sheets[$sheetName])) {
                throw new RuntimeException('El Excel no tiene la hoja "' . $sheetName . '".');
            }
            foreach ($this->records($sheets[$sheetName], $sheetName, $layout) as $record) {
                $serial = $record['license']['serial_number'];
                if (isset($licenses[$serial])) {
                    $licenses[$serial] = $this->merge($licenses[$serial], $record);
                    continue;
                }
                $licenses[$serial] = $record;
            }
        }

        $customers = [];
        foreach ($licenses as $record) {
            $rfc = $record['customer']['rfc'];
            $customers[$rfc] ??= ['customer' => $record['customer'], 'licenses' => []];
            $customers[$rfc]['customer'] = $this->fill($customers[$rfc]['customer'], $record['customer']);
            $customers[$rfc]['licenses'][] = $record['license'] + ['notes' => 'Importada desde ' . $source . ' (hoja ' . $record['sheet'] . ', fila ' . $record['row'] . ').'];
        }

        return $customers;
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /** @return list<array{sheet: string, row: int, customer: array<string, ?string>, license: array<string, mixed>}> */
    private function records(array $rows, string $sheetName, array $layout): array
    {
        $columns = $layout['columns'];
        $header = $rows[array_key_first($rows) ?? 1] ?? [];
        foreach ($columns as [$letter, $expected]) {
            if ($this->normalizeHeader($header[$letter] ?? '') !== $expected) {
                throw new RuntimeException('La hoja "' . $sheetName . '" cambió de formato: se esperaba "' . $expected . '" en la columna ' . $letter . '.');
            }
        }

        $records = [];
        foreach ($rows as $number => $row) {
            $value = static fn (string $field): string => trim(preg_replace('/\s+/u', ' ', (string) ($row[$columns[$field][0]] ?? '')) ?? '');
            if ($this->normalizeHeader($value('product')) === $columns['product'][1] || $value('serial') === '') {
                continue;
            }
            $where = $sheetName . ' fila ' . $number;
            try {
                $rfc = $this->identity->normalizeRfc($value('rfc'));
            } catch (InvalidArgumentException) {
                $rfc = null;
            }
            if ($rfc === null) {
                $this->warnings[] = $where . ': se omitió porque el RFC "' . $value('rfc') . '" no es válido.';
                continue;
            }

            $records[] = [
                'sheet' => $sheetName,
                'row' => $number,
                'customer' => [
                    'rfc' => $rfc,
                    'name' => $this->contactName($value('contact')) ?: $value('company'),
                    'business_name' => $value('company'),
                    'customer_type' => $this->identity->customerTypeForRfc($rfc),
                    'email' => $this->email($value('email'), $where),
                    'phone' => preg_replace('/\D+/', '', $value('phone')) ?: null,
                ],
                'license' => [
                    'serial_number' => mb_strtoupper($value('serial')),
                    'product_name' => $this->productName($value('product')),
                    'license_type' => $layout['type'] === 'anual' ? $this->licenseType($value('kind'), $where) : $layout['type'],
                    'version' => isset($columns['version']) ? ($value('version') ?: null) : null,
                    'users_count' => $this->users($value('users'), $where),
                    'expires_at' => isset($columns['expires']) ? $this->date($value('expires'), $where) : null,
                ],
            ];
        }

        return $records;
    }

    private function merge(array $current, array $duplicate): array
    {
        $current['customer'] = $this->fill($current['customer'], $duplicate['customer']);
        foreach ($duplicate['license'] as $field => $value) {
            if (($current['license'][$field] ?? null) === null && $value !== null) {
                $current['license'][$field] = $value;
            }
        }

        return $current;
    }

    private function fill(array $current, array $other): array
    {
        foreach ($other as $field => $value) {
            if (($current[$field] ?? null) === null || $current[$field] === '') {
                $current[$field] = $value;
            }
        }

        return $current;
    }

    private function productName(string $product): string
    {
        $key = mb_strtoupper($this->withoutAccents($product));

        return self::PRODUCTS[$key] ?? 'CONTPAQi® ' . mb_convert_case(mb_strtolower($product), MB_CASE_TITLE);
    }

    private function licenseType(string $kind, string $where): string
    {
        $kind = mb_strtolower($this->withoutAccents($kind));
        if (in_array($kind, ['anual', 'tradicional', 'mensual'], true)) {
            return $kind;
        }
        $this->warnings[] = $where . ': tipo de licencia "' . $kind . '" desconocido; se registró como anual.';

        return 'anual';
    }

    private function users(string $users, string $where): int
    {
        $count = filter_var($users, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($count === false) {
            $this->warnings[] = $where . ': cantidad de usuarios "' . $users . '" inválida; se registró 1.';

            return 1;
        }

        return $count;
    }

    private function date(string $value, string $where): ?string
    {
        if (preg_match('/^\d{1,5}(\.\d+)?$/', $value)) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+' . (int) $value . ' days')->format('Y-m-d');
        }
        $date = DateTimeImmutable::createFromFormat('!d/m/Y', $value);
        if ($date && $date->format('d/m/Y') === $value) {
            return $date->format('Y-m-d');
        }
        $this->warnings[] = $where . ': fecha de caducidad "' . $value . '" inválida.';

        return null;
    }

    private function email(string $email, string $where): ?string
    {
        $email = mb_strtolower($email);
        if ($email === '') {
            return null;
        }
        if (str_ends_with($email, '.old') || str_ends_with($email, '@sincorreo.com') || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->warnings[] = $where . ': se omitió el correo "' . $email . '" por no ser válido o estar marcado como obsoleto.';

            return null;
        }

        return $email;
    }

    private function contactName(string $name): string
    {
        return trim(preg_replace('/(\s+\.)+$/u', '', $name) ?? '');
    }

    private function normalizeHeader(string $header): string
    {
        return mb_strtolower(trim($this->withoutAccents($header)));
    }

    private function withoutAccents(string $value): string
    {
        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);
    }
}
