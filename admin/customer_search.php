<?php
require_once __DIR__ . '/../includes/db.php';

start_secure_session(true);

header('Content-Type: application/json; charset=UTF-8');

$query = trim((string) ($_GET['q'] ?? ''));

if (mb_strlen($query) < 3) {
    echo json_encode([
        'ok' => true,
        'query' => $query,
        'results' => [],
        'meta' => [
            'min_length' => 3,
            'returned' => 0,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$results = [];
$customers = getCustomers($conn, $query, 5, 0);

if ($customers && $customers->num_rows > 0) {
    while ($customer = $customers->fetch_assoc()) {
        $results[] = [
            'id' => (int) $customer['id'],
            'name' => trim((string) ($customer['name'] ?? '')),
            'business_name' => trim((string) ($customer['business_name'] ?? '')),
            'email' => trim((string) ($customer['email'] ?? '')),
            'phone' => trim((string) ($customer['phone'] ?? '')),
        ];
    }
}

echo json_encode([
    'ok' => true,
    'query' => $query,
    'results' => $results,
    'meta' => [
        'min_length' => 3,
        'returned' => count($results),
    ],
], JSON_UNESCAPED_UNICODE);
