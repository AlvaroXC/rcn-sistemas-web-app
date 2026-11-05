<?php

/*
 * Importa clientes y licencias desde el Excel de renovaciones (hojas "Anual" y "Tradicionales").
 *
 *   php scripts/import_licenses.php "/ruta/Renovaciones AX.xlsx" --dry-run   # simula y muestra el reporte
 *   php scripts/import_licenses.php "/ruta/Renovaciones AX.xlsx"             # registra los datos
 *
 * Los clientes se identifican por RFC y las licencias por número de serie, así que puede
 * ejecutarse varias veces: lo que ya existe no se duplica ni se modifica. Todo corre en una
 * transacción; si una fila falla no se guarda nada.
 */

use RcnSistemas\Models\Customer;
use RcnSistemas\Repositories\MysqliCustomerLicenseRepository;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Licenses\CustomerLicenseService;
use RcnSistemas\Services\Licenses\Import\RenewalSpreadsheetMapper;
use RcnSistemas\Services\Licenses\Import\XlsxReader;

if (PHP_SAPI !== 'cli') {
    exit('Este script solo puede ejecutarse desde la terminal.');
}

$arguments = array_values(array_filter(array_slice($argv, 1), static fn (string $argument): bool => !str_starts_with($argument, '--')));
$dryRun = in_array('--dry-run', $argv, true);
$path = $arguments[0] ?? '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Uso: php scripts/import_licenses.php \"/ruta/archivo.xlsx\" [--dry-run]\n");
    exit(2);
}

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$connection = $application['database']->get();
$requirements = [
    'customer_licenses' => "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'customer_licenses'",
    'customers.rfc' => "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'rfc'",
];
foreach ($requirements as $name => $query) {
    $result = $connection->query($query);
    if (!$result || $result->num_rows === 0) {
        fwrite(STDERR, "Falta {$name} en la base de datos. Ejecuta primero: php scripts/migrate.php\n");
        exit(1);
    }
}

$mapper = new RenewalSpreadsheetMapper();
$groups = $mapper->map((new XlsxReader())->read($path), basename($path));
$warnings = $mapper->warnings();

$customers = new MysqliCustomerRepository($connection);
$licenses = new MysqliCustomerLicenseRepository($connection);
$customerService = new CustomerService($customers, new CustomerIdentity());
$licenseService = new CustomerLicenseService($licenses, $customers);
$report = ['clientes nuevos' => 0, 'clientes existentes (por RFC)' => 0, 'clientes vinculados por teléfono' => 0, 'licencias nuevas' => 0, 'licencias que ya existían' => 0];
$notices = [];
$errors = [];

// Un teléfono provisional solo con dígitos y único, porque el teléfono es obligatorio y único en clientes.
$placeholderPhone = static function (string $rfc) use ($customers): string {
    $base = '000' . preg_replace('/\D+/', '', $rfc);
    for ($suffix = 0; $customers->findByPhone($candidate = $base . ($suffix ?: '')); $suffix++);

    return $candidate;
};

$connection->begin_transaction();
try {
    foreach ($groups as $rfc => $group) {
        $data = $group['customer'];
        $customer = $customers->findByRfc($rfc);
        try {
            if ($customer) {
                $report['clientes existentes (por RFC)']++;
            } else {
                $owner = $data['phone'] !== null ? $customers->findByPhone($data['phone']) : null;
                if ($owner && $owner->rfc === null) {
                    // Mismo teléfono y sin RFC: se asume que es el mismo cliente y solo se le asigna el RFC.
                    $customer = $customerService->save(['id' => $owner->id, 'name' => $owner->name, 'phone' => $owner->phone, 'email' => $owner->email, 'business_name' => $owner->businessName, 'customer_type' => $owner->customerType, 'rfc' => $rfc]);
                    $report['clientes vinculados por teléfono']++;
                    $notices[] = "{$rfc} ({$data['business_name']}): se vinculó al cliente #{$owner->id} \"{$owner->name}\" porque tiene el mismo teléfono {$data['phone']}. Verifica que sea el mismo cliente.";
                } else {
                    if ($data['phone'] === null || $owner) {
                        $reason = $data['phone'] === null ? 'no tiene teléfono en el Excel' : "su teléfono {$data['phone']} ya pertenece al cliente #{$owner->id} \"{$owner->name}\"";
                        $data['phone'] = $placeholderPhone($rfc);
                        $notices[] = "{$rfc} ({$data['business_name']}): {$reason}; se registró el teléfono provisional {$data['phone']}.";
                    }
                    $customer = $customerService->save(['id' => 0] + $data);
                    $report['clientes nuevos']++;
                }
            }
        } catch (InvalidArgumentException $exception) {
            $errors[] = "Cliente {$rfc} ({$data['business_name']}): " . $exception->getMessage();
            continue;
        }

        foreach ($group['licenses'] as $license) {
            $existing = $licenses->findBySerialNumber($license['serial_number']);
            if ($existing) {
                $report['licencias que ya existían']++;
                if ($existing->customerId !== $customer->id) {
                    $notices[] = "Serie {$license['serial_number']}: ya estaba registrada en el cliente #{$existing->customerId}, no en {$rfc}; no se modificó.";
                }
                continue;
            }
            try {
                // El Excel no indica si la licencia es mono o multi-RFC: se usa el valor por defecto del sistema.
                $licenseService->save($license + ['customer_id' => $customer->id, 'rfc_scope' => 'multi']);
                $report['licencias nuevas']++;
            } catch (InvalidArgumentException $exception) {
                $errors[] = "Serie {$license['serial_number']} ({$rfc}): " . $exception->getMessage();
            }
        }
    }

    if ($errors || $dryRun) {
        $connection->rollback();
    } else {
        $connection->commit();
    }
} catch (Throwable $exception) {
    $connection->rollback();
    throw $exception;
}

echo ($dryRun ? "SIMULACIÓN: no se guardó ningún cambio.\n" : ''), "Archivo: {$path}\n\n";
foreach ($report as $label => $count) {
    $label = ucfirst($label) . ':';
    echo $label, str_repeat(' ', max(1, 35 - mb_strlen($label))), $count, "\n";
}
foreach (['Avisos del Excel' => $warnings, 'Revisar' => $notices, 'Errores' => $errors] as $title => $lines) {
    if ($lines) {
        echo "\n{$title} (" . count($lines) . "):\n - " . implode("\n - ", $lines) . "\n";
    }
}
if ($errors) {
    echo "\nNo se guardó nada porque hubo errores. Corrígelos en el Excel y vuelve a ejecutar.\n";
    exit(1);
}
if (!$dryRun) {
    echo "\nImportación completada.\n";
}
