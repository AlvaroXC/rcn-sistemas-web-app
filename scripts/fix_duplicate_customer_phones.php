<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$connection = $application['database']->get();
$dryRun = in_array('--dry-run', $argv, true);
$normalizePhone = static function (string $phone): ?string { $digits = preg_replace('/\D+/', '', $phone); return $digits !== null && strlen($digits) >= 7 ? '+' . $digits : null; };
$groups = [];
$result = $connection->query("SELECT id, name, phone, phone_normalized FROM customers WHERE phone_normalized IS NOT NULL AND phone_normalized <> '' ORDER BY id ASC");
while ($result && $row = $result->fetch_assoc()) $groups[(string) $row['phone_normalized']][] = $row;
$reserved = array_fill_keys(array_keys($groups), true); $updates = [];
foreach ($groups as $normalized => $customers) {
    if (count($customers) < 2) continue;
    array_shift($customers);
    foreach ($customers as $customer) {
        $base = substr((string) $normalized, 0, 7); $candidate = null;
        for ($suffix = 1; $suffix < 10000; $suffix++) { $value = $base . str_pad((string) $suffix, 4, '0', STR_PAD_LEFT); $candidate = $normalizePhone($value); if ($candidate && !isset($reserved[$candidate])) break; $candidate = null; }
        if (!$candidate) throw new RuntimeException('No se pudo generar un teléfono único.');
        $reserved[$candidate] = true; $updates[] = ['id' => (int) $customer['id'], 'phone' => $candidate];
    }
}
printf("Cambios planeados: %d\n", count($updates));
if ($dryRun || !$updates) exit(0);
$connection->begin_transaction();
try {
    $statement = $connection->prepare('UPDATE customers SET phone = ?, phone_normalized = ? WHERE id = ?');
    foreach ($updates as $update) { $statement->bind_param('ssi', $update['phone'], $update['phone'], $update['id']); if (!$statement->execute()) throw new RuntimeException('No se pudo actualizar el cliente #' . $update['id']); }
    $connection->commit(); echo "Migración aplicada correctamente.\n";
} catch (Throwable $exception) { $connection->rollback(); throw $exception; }
