<?php
require_once __DIR__ . '/../includes/db.php';

function generate_unique_phone_candidate($basePhone, $customerId, array $reservedPhones) {
    $normalizedBase = normalize_phone_value($basePhone) ?? '9990000000';
    $basePrefix = substr(str_pad($normalizedBase, 10, '0', STR_PAD_RIGHT), 0, 6);
    $seed = max(1, (int) $customerId);

    for ($attempt = 0; $attempt < 1000; $attempt++) {
        $suffix = str_pad((string) (($seed + $attempt) % 10000), 4, '0', STR_PAD_LEFT);
        $candidate = $basePrefix . $suffix;
        $candidateNormalized = normalize_phone_value($candidate);

        if ($candidateNormalized === null) {
            continue;
        }

        if (!isset($reservedPhones[$candidateNormalized])) {
            return $candidate;
        }
    }

    return null;
}

function get_duplicate_customer_phone_groups($conn) {
    $groups = [];
    $sql = "SELECT phone_normalized
            FROM customers
            WHERE phone_normalized IS NOT NULL AND phone_normalized <> ''
            GROUP BY phone_normalized
            HAVING COUNT(*) > 1
            ORDER BY phone_normalized ASC";
    $result = $conn->query($sql);

    if (!$result) {
        return $groups;
    }

    while ($row = $result->fetch_assoc()) {
        $phoneNormalized = (string) $row['phone_normalized'];
        $stmt = $conn->prepare(
            'SELECT id, name, phone, phone_normalized, created_at
             FROM customers
             WHERE phone_normalized = ?
             ORDER BY id ASC'
        );
        $stmt->bind_param('s', $phoneNormalized);
        $stmt->execute();
        $customerResult = $stmt->get_result();
        $customers = [];

        while ($customerResult && $customer = $customerResult->fetch_assoc()) {
            $customers[] = $customer;
        }

        if (count($customers) > 1) {
            $groups[] = [
                'phone_normalized' => $phoneNormalized,
                'customers' => $customers,
            ];
        }
    }

    return $groups;
}

$dryRun = in_array('--dry-run', $argv, true);
$diagnosticsBefore = get_customer_phone_identity_diagnostics($conn);

echo "Diagnóstico antes de migrar:\n";
echo json_encode($diagnosticsBefore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if ($diagnosticsBefore['duplicate_group_count'] === 0) {
    echo "No hay teléfonos duplicados por corregir.\n";
    exit(0);
}

$reservedPhones = [];
$reservedResult = $conn->query("SELECT phone_normalized FROM customers WHERE phone_normalized IS NOT NULL AND phone_normalized <> ''");
while ($reservedResult && $row = $reservedResult->fetch_assoc()) {
    $reservedPhones[(string) $row['phone_normalized']] = true;
}

$duplicateGroups = get_duplicate_customer_phone_groups($conn);
$plannedUpdates = [];

foreach ($duplicateGroups as $group) {
    $customers = $group['customers'];
    array_shift($customers);

    foreach ($customers as $customer) {
        $newPhone = generate_unique_phone_candidate($group['phone_normalized'], (int) $customer['id'], $reservedPhones);
        if ($newPhone === null) {
            fwrite(STDERR, "No se pudo generar un teléfono único para el cliente #{$customer['id']}.\n");
            exit(1);
        }

        $newPhoneNormalized = normalize_phone_value($newPhone);
        $reservedPhones[$newPhoneNormalized] = true;
        $plannedUpdates[] = [
            'id' => (int) $customer['id'],
            'name' => (string) $customer['name'],
            'old_phone' => (string) $customer['phone'],
            'old_phone_normalized' => (string) $customer['phone_normalized'],
            'new_phone' => $newPhone,
            'new_phone_normalized' => $newPhoneNormalized,
        ];
    }
}

echo "Cambios planeados:\n";
echo json_encode($plannedUpdates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if ($dryRun) {
    echo "Ejecución en modo dry-run. No se aplicaron cambios.\n";
    exit(0);
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare('UPDATE customers SET phone = ?, phone_normalized = ? WHERE id = ?');
    foreach ($plannedUpdates as $update) {
        $stmt->bind_param('ssi', $update['new_phone'], $update['new_phone_normalized'], $update['id']);
        if (!$stmt->execute()) {
            throw new RuntimeException('No se pudo actualizar el cliente #' . $update['id']);
        }
    }

    ensure_support_tickets_schema($conn);
    $diagnosticsAfter = get_customer_phone_identity_diagnostics($conn);

    if (!$diagnosticsAfter['ready_for_enforcement']) {
        throw new RuntimeException('La migración terminó pero aún existen conflictos: ' . json_encode($diagnosticsAfter, JSON_UNESCAPED_UNICODE));
    }

    $conn->commit();

    echo "Diagnóstico después de migrar:\n";
    echo json_encode($diagnosticsAfter, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "Migración aplicada correctamente.\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
