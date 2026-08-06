<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/security.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../', ['.env.local', '.env']);
$dotenv->safeLoad();

function getEnvVar($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $_SERVER[$key] ?? $default;
}

if (!defined('APP_ASSET_VERSION')) {
    define('APP_ASSET_VERSION', getEnvVar('APP_ASSET_VERSION', '2026.06.27.1'));
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$host = getEnvVar('DB_HOST', 'localhost');
$db   = getEnvVar('DB_NAME', 'rcnsiste_axsoluciones');
$user = getEnvVar('DB_USER', 'root');
$pass = getEnvVar('DB_PASS', '');
$port = getEnvVar('DB_PORT', 3306);
$charset = 'utf8mb4';

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset($charset);
} catch (Exception $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Error al conectar con la base de datos. Por favor, verifica la configuración de entorno (.env).');
}

ensure_promotions_schema($conn);
ensure_support_tickets_schema($conn);

function ensure_promotions_schema($conn) {
    $requiredColumns = [
        'short_description' => "ALTER TABLE promotions ADD COLUMN short_description TEXT NULL AFTER title",
        'full_description' => "ALTER TABLE promotions ADD COLUMN full_description TEXT NULL AFTER short_description",
        'terms_conditions' => "ALTER TABLE promotions ADD COLUMN terms_conditions TEXT NULL AFTER full_description",
    ];

    foreach ($requiredColumns as $column => $sql) {
        $escapedColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM promotions LIKE '{$escapedColumn}'");
        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }

    $legacyDescriptionExists = $conn->query("SHOW COLUMNS FROM promotions LIKE 'description'");
    if ($legacyDescriptionExists && $legacyDescriptionExists->num_rows > 0) {
        $conn->query(
            "UPDATE promotions
             SET short_description = COALESCE(NULLIF(short_description, ''), description),
                 full_description = COALESCE(NULLIF(full_description, ''), description),
                 terms_conditions = COALESCE(NULLIF(terms_conditions, ''), description)
             WHERE description IS NOT NULL AND description <> ''"
        );
    }
}

function normalizePromotionRow(array $promotion) {
    $legacyDescription = trim((string) ($promotion['description'] ?? ''));
    $promotion['short_description'] = trim((string) ($promotion['short_description'] ?? ''));
    $promotion['full_description'] = trim((string) ($promotion['full_description'] ?? ''));
    $promotion['terms_conditions'] = trim((string) ($promotion['terms_conditions'] ?? ''));

    if ($promotion['short_description'] === '') {
        $promotion['short_description'] = $legacyDescription;
    }
    if ($promotion['full_description'] === '') {
        $promotion['full_description'] = $legacyDescription;
    }
    if ($promotion['terms_conditions'] === '') {
        $promotion['terms_conditions'] = $legacyDescription;
    }

    return $promotion;
}

function getProducts($conn, $limit = null) {
    $sql = 'SELECT * FROM products ORDER BY created_at DESC';
    if ($limit) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    return $conn->query($sql);
}

function getPromotions($conn, $activeOnly = true) {
    $sql = 'SELECT * FROM promotions';
    if ($activeOnly) {
        $sql .= ' WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())';
    }
    $sql .= ' ORDER BY created_at DESC';
    return $conn->query($sql);
}

function getPromotionById($conn, $id, $activeOnly = true) {
    $sql = 'SELECT * FROM promotions WHERE id = ?';
    if ($activeOnly) {
        $sql .= ' AND active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())';
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promotion = $result ? $result->fetch_assoc() : null;

    return $promotion ? normalizePromotionRow($promotion) : null;
}

function getTestimonials($conn) {
    $sql = 'SELECT * FROM testimonials ORDER BY date DESC';
    return $conn->query($sql);
}

function ensure_support_tickets_schema($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS customers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            business_name VARCHAR(255) NULL,
            email VARCHAR(255) NULL,
            email_normalized VARCHAR(255) NULL,
            phone VARCHAR(50) NOT NULL,
            phone_normalized VARCHAR(50) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_customers_email_normalized (email_normalized),
            UNIQUE KEY uniq_customers_phone_normalized (phone_normalized),
            KEY idx_customers_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $customerColumns = [
        'business_name' => "ALTER TABLE customers ADD COLUMN business_name VARCHAR(255) NULL AFTER name",
        'phone_normalized' => "ALTER TABLE customers ADD COLUMN phone_normalized VARCHAR(50) NULL AFTER phone",
    ];

    foreach ($customerColumns as $column => $sql) {
        $escapedColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM customers LIKE '{$escapedColumn}'");
        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }

    $conn->query("ALTER TABLE customers MODIFY COLUMN email VARCHAR(255) NULL");
    $conn->query("ALTER TABLE customers MODIFY COLUMN email_normalized VARCHAR(255) NULL");

    if (customer_index_exists($conn, 'customers', 'uniq_customers_email_normalized')) {
        $conn->query('ALTER TABLE customers DROP INDEX uniq_customers_email_normalized');
    }

    if (!customer_index_exists($conn, 'customers', 'idx_customers_email_normalized')) {
        $conn->query('ALTER TABLE customers ADD INDEX idx_customers_email_normalized (email_normalized)');
    }

    backfill_customer_identity_columns($conn);

    $diagnostics = get_customer_phone_identity_diagnostics($conn);
    if ($diagnostics['missing_phone_count'] === 0 && $diagnostics['duplicate_group_count'] === 0) {
        $conn->query("ALTER TABLE customers MODIFY COLUMN phone VARCHAR(50) NOT NULL");
        $conn->query("ALTER TABLE customers MODIFY COLUMN phone_normalized VARCHAR(50) NOT NULL");

        if (!customer_index_exists($conn, 'customers', 'uniq_customers_phone_normalized')) {
            $conn->query('ALTER TABLE customers ADD UNIQUE INDEX uniq_customers_phone_normalized (phone_normalized)');
        }
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS tickets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED NOT NULL,
            consultation_type VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Nuevo',
            origin_type VARCHAR(50) NOT NULL DEFAULT 'contact',
            product_id INT UNSIGNED NULL,
            promo_id INT UNSIGNED NULL,
            context_label VARCHAR(255) NULL,
            subject VARCHAR(255) NULL,
            message TEXT NOT NULL,
            attachment_path VARCHAR(255) NULL,
            price_amount DECIMAL(10,2) NULL,
            price_currency CHAR(3) NULL DEFAULT 'MXN',
            price_updated_by INT UNSIGNED NULL,
            price_updated_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_tickets_customer_id (customer_id),
            KEY idx_tickets_status (status),
            KEY idx_tickets_consultation_type (consultation_type),
            KEY idx_tickets_created_at (created_at),
            KEY idx_tickets_origin_type (origin_type),
            KEY idx_tickets_price_updated_by (price_updated_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ticketColumns = [
        'price_amount' => "ALTER TABLE tickets ADD COLUMN price_amount DECIMAL(10,2) NULL AFTER attachment_path",
        'price_currency' => "ALTER TABLE tickets ADD COLUMN price_currency CHAR(3) NULL DEFAULT 'MXN' AFTER price_amount",
        'price_updated_by' => "ALTER TABLE tickets ADD COLUMN price_updated_by INT UNSIGNED NULL AFTER price_currency",
        'price_updated_at' => "ALTER TABLE tickets ADD COLUMN price_updated_at TIMESTAMP NULL DEFAULT NULL AFTER price_updated_by",
    ];

    foreach ($ticketColumns as $column => $sql) {
        $escapedColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM tickets LIKE '{$escapedColumn}'");
        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }

    if (!customer_index_exists($conn, 'tickets', 'idx_tickets_price_updated_by')) {
        $conn->query('ALTER TABLE tickets ADD INDEX idx_tickets_price_updated_by (price_updated_by)');
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS ticket_observations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT UNSIGNED NOT NULL,
            admin_user_id INT UNSIGNED NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ticket_observations_ticket_id (ticket_id),
            KEY idx_ticket_observations_admin_user_id (admin_user_id),
            KEY idx_ticket_observations_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function normalize_email_value($email) {
    $email = strtolower(trim((string) $email));
    return $email !== '' ? $email : null;
}

function normalize_phone_value($phone) {
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return null;
    }

    if (strlen($digits) === 13 && strpos($digits, '521') === 0) {
        $digits = substr($digits, 3);
    } elseif (strlen($digits) === 12 && strpos($digits, '52') === 0) {
        $digits = substr($digits, 2);
    }

    return $digits !== '' ? $digits : null;
}

function is_valid_optional_email($email) {
    $email = trim((string) $email);
    return $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL);
}

function normalize_ticket_price_input($price) {
    $price = trim((string) $price);
    if ($price === '') {
        return ['ok' => true, 'value' => null, 'error' => ''];
    }

    $normalized = str_replace(['$', ',', ' '], '', $price);
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
        return ['ok' => false, 'value' => null, 'error' => 'Ingresa un precio válido en MXN con hasta 2 decimales.'];
    }

    $amount = (float) $normalized;
    if ($amount < 0) {
        return ['ok' => false, 'value' => null, 'error' => 'El precio no puede ser negativo.'];
    }

    return [
        'ok' => true,
        'value' => number_format($amount, 2, '.', ''),
        'error' => '',
    ];
}

function format_ticket_price($amount, $currency = 'MXN') {
    if ($amount === null || $amount === '') {
        return 'Sin precio asignado';
    }

    return trim((string) $currency) . ' ' . number_format((float) $amount, 2, '.', ',');
}

function customer_index_exists($conn, $table, $indexName) {
    $table = $conn->real_escape_string($table);
    $indexName = $conn->real_escape_string($indexName);
    $result = $conn->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");

    return $result && $result->num_rows > 0;
}

function backfill_customer_identity_columns($conn) {
    if ($result = $conn->query('SELECT id, email, phone FROM customers')) {
        while ($row = $result->fetch_assoc()) {
            $normalizedEmail = normalize_email_value($row['email'] ?? '');
            $normalizedPhone = normalize_phone_value($row['phone'] ?? '');
            $stmt = $conn->prepare('UPDATE customers SET email_normalized = ?, phone_normalized = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('ssi', $normalizedEmail, $normalizedPhone, $row['id']);
                $stmt->execute();
            }
        }
    }
}

function get_customer_phone_identity_diagnostics($conn) {
    $diagnostics = [
        'missing_phone_count' => 0,
        'missing_phone_customers' => [],
        'duplicate_group_count' => 0,
        'duplicate_phone_groups' => [],
        'ready_for_enforcement' => false,
    ];

    if ($missingStmt = $conn->prepare(
        "SELECT id, name, business_name, email, phone
         FROM customers
         WHERE phone_normalized IS NULL OR phone_normalized = ''
         ORDER BY created_at DESC, id DESC
         LIMIT 10"
    )) {
        $missingStmt->execute();
        $missingResult = $missingStmt->get_result();
        while ($missingResult && $row = $missingResult->fetch_assoc()) {
            $diagnostics['missing_phone_customers'][] = $row;
        }
    }

    if ($missingCountResult = $conn->query(
        "SELECT COUNT(*) AS total
         FROM customers
         WHERE phone_normalized IS NULL OR phone_normalized = ''"
    )) {
        $diagnostics['missing_phone_count'] = (int) ($missingCountResult->fetch_assoc()['total'] ?? 0);
    }

    if ($duplicateStmt = $conn->prepare(
        "SELECT phone_normalized, COUNT(*) AS total, GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ', ') AS customer_ids
         FROM customers
         WHERE phone_normalized IS NOT NULL AND phone_normalized <> ''
         GROUP BY phone_normalized
         HAVING COUNT(*) > 1
         ORDER BY total DESC, phone_normalized ASC
         LIMIT 10"
    )) {
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result();
        while ($duplicateResult && $row = $duplicateResult->fetch_assoc()) {
            $diagnostics['duplicate_phone_groups'][] = $row;
        }
    }

    if ($duplicateCountResult = $conn->query(
        "SELECT COUNT(*) AS total
         FROM (
             SELECT phone_normalized
             FROM customers
             WHERE phone_normalized IS NOT NULL AND phone_normalized <> ''
             GROUP BY phone_normalized
             HAVING COUNT(*) > 1
         ) duplicate_groups"
    )) {
        $diagnostics['duplicate_group_count'] = (int) ($duplicateCountResult->fetch_assoc()['total'] ?? 0);
    }

    $diagnostics['ready_for_enforcement'] =
        $diagnostics['missing_phone_count'] === 0
        && $diagnostics['duplicate_group_count'] === 0;

    return $diagnostics;
}

function ensure_directory_exists($path) {
    if (is_dir($path)) {
        return true;
    }

    return mkdir($path, 0775, true);
}

function save_ticket_attachment($field, $destinationDir, $publicPrefix, array $rules) {
    if (!ensure_directory_exists($destinationDir)) {
        return ['ok' => false, 'path' => null, 'error' => 'No se pudo preparar el directorio del archivo.'];
    }

    return save_uploaded_file($field, $destinationDir, $publicPrefix, $rules);
}

function find_customer_by_email($conn, $email) {
    $normalized = normalize_email_value($email);
    if ($normalized === null) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM customers WHERE email_normalized = ? LIMIT 1');
    $stmt->bind_param('s', $normalized);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function find_customer_by_email_excluding_id($conn, $email, $excludeCustomerId) {
    $normalized = normalize_email_value($email);
    $excludeCustomerId = (int) $excludeCustomerId;

    if ($normalized === null || $excludeCustomerId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM customers WHERE email_normalized = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $normalized, $excludeCustomerId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function find_customer_by_phone($conn, $phone) {
    $normalizedPhone = normalize_phone_value($phone);
    if ($normalizedPhone === null) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM customers WHERE phone_normalized = ? LIMIT 1');
    $stmt->bind_param('s', $normalizedPhone);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function find_customer_by_phone_excluding_id($conn, $phone, $excludeCustomerId) {
    $normalizedPhone = normalize_phone_value($phone);
    $excludeCustomerId = (int) $excludeCustomerId;

    if ($normalizedPhone === null || $excludeCustomerId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM customers WHERE phone_normalized = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $normalizedPhone, $excludeCustomerId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function create_or_update_customer($conn, $name, $email = null, $phone = null, $businessName = null) {
    $normalizedEmail = normalize_email_value($email);
    $normalizedPhone = normalize_phone_value($phone);
    $name = trim((string) $name);
    $email = trim((string) ($email ?? ''));
    $phone = trim((string) ($phone ?? ''));
    $businessName = trim((string) ($businessName ?? ''));

    if ($name === '' || $normalizedPhone === null) {
        return null;
    }

    $existing = find_customer_by_phone($conn, $phone);
    if ($existing) {
        $nextName = $existing['name'];
        $nextBusinessName = $existing['business_name'] ?? '';
        $nextPhone = (string) ($existing['phone'] ?? '');
        $nextEmail = (string) ($existing['email'] ?? '');
        $nextNormalizedEmail = normalize_email_value($nextEmail);

        if ($name !== '' && $name !== $existing['name']) {
            $nextName = $name;
        }

        if ($phone !== '' && $phone !== (string) $existing['phone']) {
            $nextPhone = $phone;
        }

        if ($businessName !== '' && $businessName !== (string) ($existing['business_name'] ?? '')) {
            $nextBusinessName = $businessName;
        }

        if ($normalizedEmail !== null) {
            $nextEmail = $email;
            $nextNormalizedEmail = $normalizedEmail;
        }

        $nextNormalizedPhone = normalize_phone_value($nextPhone);
        $stmt = $conn->prepare('UPDATE customers SET name = ?, business_name = ?, email = ?, email_normalized = ?, phone = ?, phone_normalized = ? WHERE id = ?');
        $stmt->bind_param('ssssssi', $nextName, $nextBusinessName, $nextEmail, $nextNormalizedEmail, $nextPhone, $nextNormalizedPhone, $existing['id']);
        if (!$stmt->execute()) {
            return null;
        }

        return getCustomerById($conn, (int) $existing['id']);
    }

    $stmt = $conn->prepare('INSERT INTO customers (name, business_name, email, email_normalized, phone, phone_normalized) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $name, $businessName, $email, $normalizedEmail, $phone, $normalizedPhone);
    if (!$stmt->execute()) {
        return null;
    }

    return getCustomerById($conn, (int) $conn->insert_id);
}

function update_customer_by_id($conn, $customerId, $name, $email = null, $phone = null, $businessName = null) {
    $customerId = (int) $customerId;
    $normalizedEmail = normalize_email_value($email);
    $normalizedPhone = normalize_phone_value($phone);
    $name = trim((string) $name);
    $email = trim((string) ($email ?? ''));
    $phone = trim((string) ($phone ?? ''));
    $businessName = trim((string) ($businessName ?? ''));

    if ($customerId <= 0 || $name === '' || $normalizedPhone === null) {
        return ['ok' => false, 'error' => 'Nombre y teléfono son obligatorios.', 'customer' => null];
    }

    $currentCustomer = getCustomerById($conn, $customerId);
    if (!$currentCustomer) {
        return ['ok' => false, 'error' => 'El cliente ya no existe.', 'customer' => null];
    }

    $conflictCustomer = find_customer_by_phone_excluding_id($conn, $phone, $customerId);
    if ($conflictCustomer) {
        return ['ok' => false, 'error' => 'Ya existe otro cliente con ese número de teléfono.', 'customer' => null];
    }

    $stmt = $conn->prepare('UPDATE customers SET name = ?, business_name = ?, email = ?, email_normalized = ?, phone = ?, phone_normalized = ? WHERE id = ?');
    $stmt->bind_param('ssssssi', $name, $businessName, $email, $normalizedEmail, $phone, $normalizedPhone, $customerId);

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar el cliente.', 'customer' => null];
    }

    return ['ok' => true, 'error' => '', 'customer' => getCustomerById($conn, $customerId)];
}

function create_support_ticket($conn, array $data) {
    $sql = 'INSERT INTO tickets (
                customer_id,
                consultation_type,
                status,
                origin_type,
                product_id,
                promo_id,
                context_label,
                subject,
                message,
                attachment_path,
                price_amount,
                price_currency,
                price_updated_by,
                price_updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $customerId = (int) $data['customer_id'];
    $consultationType = (string) $data['consultation_type'];
    $status = (string) ($data['status'] ?? 'Nuevo');
    $originType = (string) ($data['origin_type'] ?? 'contact');
    $productId = isset($data['product_id']) && (int) $data['product_id'] > 0 ? (int) $data['product_id'] : null;
    $promoId = isset($data['promo_id']) && (int) $data['promo_id'] > 0 ? (int) $data['promo_id'] : null;
    $contextLabel = $data['context_label'] !== '' ? (string) $data['context_label'] : null;
    $subject = $data['subject'] !== '' ? (string) $data['subject'] : null;
    $message = (string) $data['message'];
    $attachmentPath = $data['attachment_path'] !== '' ? (string) $data['attachment_path'] : null;
    $priceAmount = isset($data['price_amount']) && $data['price_amount'] !== '' ? (string) $data['price_amount'] : null;
    $priceCurrency = $priceAmount !== null ? 'MXN' : null;
    $priceUpdatedBy = $priceAmount !== null && !empty($data['price_updated_by']) ? (int) $data['price_updated_by'] : null;
    $priceUpdatedAt = $priceAmount !== null ? date('Y-m-d H:i:s') : null;

    $stmt->bind_param(
        'isssiissssssis',
        $customerId,
        $consultationType,
        $status,
        $originType,
        $productId,
        $promoId,
        $contextLabel,
        $subject,
        $message,
        $attachmentPath,
        $priceAmount,
        $priceCurrency,
        $priceUpdatedBy,
        $priceUpdatedAt
    );

    if (!$stmt->execute()) {
        return null;
    }

    return getTicketById($conn, (int) $conn->insert_id);
}

function getCustomerById($conn, $customerId) {
    $stmt = $conn->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function getTicketById($conn, $ticketId) {
    $sql = "SELECT
                t.*,
                c.name AS customer_name,
                c.business_name AS customer_business_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                price_editor.username AS price_updated_by_username
            FROM tickets t
            INNER JOIN customers c ON c.id = t.customer_id
            LEFT JOIN users price_editor ON price_editor.id = t.price_updated_by
            WHERE t.id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_assoc() : null;
}

function create_ticket_observation($conn, $ticketId, $content, $adminUserId = null) {
    $ticketId = (int) $ticketId;
    $content = trim((string) $content);
    $adminUserId = $adminUserId !== null ? (int) $adminUserId : null;

    if ($ticketId <= 0 || $content === '') {
        return null;
    }

    $stmt = $conn->prepare(
        'INSERT INTO ticket_observations (ticket_id, admin_user_id, content) VALUES (?, ?, ?)'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('iis', $ticketId, $adminUserId, $content);

    if (!$stmt->execute()) {
        return null;
    }

    return (int) $conn->insert_id;
}

function getTicketObservations($conn, $ticketId) {
    $sql = "SELECT
                o.*,
                u.username AS admin_username
            FROM ticket_observations o
            LEFT JOIN users u ON u.id = o.admin_user_id
            WHERE o.ticket_id = ?
            ORDER BY o.created_at DESC, o.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();

    return $stmt->get_result();
}

function getCustomerTickets($conn, $customerId) {
    $stmt = $conn->prepare('SELECT * FROM tickets WHERE customer_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();

    return $stmt->get_result();
}

function getTicketStats($conn) {
    $stats = [
        'total' => 0,
        'open' => 0,
        'customers' => 0,
    ];

    if ($result = $conn->query('SELECT COUNT(*) FROM tickets')) {
        $stats['total'] = (int) $result->fetch_row()[0];
    }

    if ($result = $conn->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('Resuelto', 'Cerrado')")) {
        $stats['open'] = (int) $result->fetch_row()[0];
    }

    if ($result = $conn->query('SELECT COUNT(*) FROM customers')) {
        $stats['customers'] = (int) $result->fetch_row()[0];
    }

    return $stats;
}

function build_support_ticket_filter_parts(array $filters = []) {
    $conditions = [];
    $types = '';
    $params = [];

    if (!empty($filters['customer'])) {
        $conditions[] = '(c.name LIKE ? OR c.business_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
        $search = '%' . $filters['customer'] . '%';
        $types .= 'ssss';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['status'])) {
        $conditions[] = 't.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    if (!empty($filters['consultation_type'])) {
        $conditions[] = 't.consultation_type = ?';
        $types .= 's';
        $params[] = $filters['consultation_type'];
    }

    if (!empty($filters['date_from'])) {
        $conditions[] = 'DATE(t.created_at) >= ?';
        $types .= 's';
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $conditions[] = 'DATE(t.created_at) <= ?';
        $types .= 's';
        $params[] = $filters['date_to'];
    }

    return [
        'conditions' => $conditions,
        'types' => $types,
        'params' => $params,
    ];
}

function getSupportTicketsCount($conn, array $filters = []) {
    $parts = build_support_ticket_filter_parts($filters);
    $sql = "SELECT COUNT(*)
            FROM tickets t
            INNER JOIN customers c ON c.id = t.customer_id";

    if ($parts['conditions']) {
        $sql .= ' WHERE ' . implode(' AND ', $parts['conditions']);
    }

    $stmt = $conn->prepare($sql);

    if ($parts['types'] !== '') {
        $stmt->bind_param($parts['types'], ...$parts['params']);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? (int) $result->fetch_row()[0] : 0;
}

function getSupportTickets($conn, array $filters = [], $limit = null, $offset = 0) {
    $parts = build_support_ticket_filter_parts($filters);
    $sql = "SELECT
                t.*,
                c.name AS customer_name,
                c.business_name AS customer_business_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                price_editor.username AS price_updated_by_username
            FROM tickets t
            INNER JOIN customers c ON c.id = t.customer_id
            LEFT JOIN users price_editor ON price_editor.id = t.price_updated_by";

    if ($parts['conditions']) {
        $sql .= ' WHERE ' . implode(' AND ', $parts['conditions']);
    }

    $types = $parts['types'];
    $params = $parts['params'];

    $sql .= ' ORDER BY t.created_at DESC, t.id DESC';

    if ($limit !== null) {
        $sql .= ' LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = (int) $limit;
        $params[] = max(0, (int) $offset);
    }

    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

function update_ticket_status($conn, $ticketId, $status) {
    $stmt = $conn->prepare('UPDATE tickets SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $ticketId);
    return $stmt->execute();
}

function update_ticket_price($conn, $ticketId, $priceAmount, $adminUserId) {
    $ticketId = (int) $ticketId;
    $adminUserId = (int) $adminUserId;
    $normalizedPrice = normalize_ticket_price_input($priceAmount);

    if ($ticketId <= 0) {
        return ['ok' => false, 'error' => 'No se pudo identificar el ticket.', 'ticket' => null];
    }

    if (!$normalizedPrice['ok'] || $normalizedPrice['value'] === null) {
        return ['ok' => false, 'error' => $normalizedPrice['error'] ?: 'Ingresa un precio válido.', 'ticket' => null];
    }

    $priceAmountValue = $normalizedPrice['value'];
    $priceCurrency = 'MXN';
    $updatedAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        'UPDATE tickets SET price_amount = ?, price_currency = ?, price_updated_by = ?, price_updated_at = ? WHERE id = ?'
    );

    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar la actualización del precio.', 'ticket' => null];
    }

    $stmt->bind_param('ssisi', $priceAmountValue, $priceCurrency, $adminUserId, $updatedAt, $ticketId);

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar el precio del ticket.', 'ticket' => null];
    }

    return ['ok' => true, 'error' => '', 'ticket' => getTicketById($conn, $ticketId)];
}

function build_customer_filter_parts($search = '') {
    $search = trim((string) $search);

    if ($search === '') {
        return [
            'conditions' => [],
            'types' => '',
            'params' => [],
        ];
    }

    $wildcard = '%' . $search . '%';

    return [
        'conditions' => ['(name LIKE ? OR business_name LIKE ? OR email LIKE ? OR phone LIKE ?)'],
        'types' => 'ssss',
        'params' => [$wildcard, $wildcard, $wildcard, $wildcard],
    ];
}

function getCustomersCount($conn, $search = '') {
    $parts = build_customer_filter_parts($search);
    $sql = 'SELECT COUNT(*) FROM customers';

    if ($parts['conditions']) {
        $sql .= ' WHERE ' . implode(' AND ', $parts['conditions']);
    }

    $stmt = $conn->prepare($sql);
    if ($parts['types'] !== '') {
        $stmt->bind_param($parts['types'], ...$parts['params']);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? (int) $result->fetch_row()[0] : 0;
}

function getCustomers($conn, $search = '', $limit = null, $offset = 0) {
    $parts = build_customer_filter_parts($search);
    $sql = 'SELECT * FROM customers';

    if ($parts['conditions']) {
        $sql .= ' WHERE ' . implode(' AND ', $parts['conditions']);
    }

    $types = $parts['types'];
    $params = $parts['params'];
    $sql .= ' ORDER BY created_at DESC, id DESC';

    if ($limit !== null) {
        $sql .= ' LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = (int) $limit;
        $params[] = max(0, (int) $offset);
    }

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}
?>
