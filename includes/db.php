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
ensure_admin_users_schema($conn);
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

function ensure_admin_users_schema($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(120) NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'admin',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_users_username (username),
            KEY idx_users_active (active),
            KEY idx_users_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $userColumns = [
        'display_name' => "ALTER TABLE users ADD COLUMN display_name VARCHAR(120) NULL AFTER password",
        'role' => "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER display_name",
        'active' => "ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER display_name",
    ];

    foreach ($userColumns as $column => $sql) {
        $escapedColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '{$escapedColumn}'");
        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }

    if (!customer_index_exists($conn, 'users', 'uniq_users_username')) {
        $conn->query('ALTER TABLE users ADD UNIQUE INDEX uniq_users_username (username)');
    }

    if (!customer_index_exists($conn, 'users', 'idx_users_active')) {
        $conn->query('ALTER TABLE users ADD INDEX idx_users_active (active)');
    }

    if (!customer_index_exists($conn, 'users', 'idx_users_role')) {
        $conn->query('ALTER TABLE users ADD INDEX idx_users_role (role)');
    }

    $conn->query("UPDATE users SET display_name = username WHERE (display_name IS NULL OR display_name = '')");
    $conn->query("UPDATE users SET role = 'admin' WHERE role IS NULL OR role = ''");
}

function ensure_support_tickets_schema($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS customers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            business_name VARCHAR(255) NULL,
            customer_type VARCHAR(30) NOT NULL DEFAULT 'persona_fisica',
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
        'customer_type' => "ALTER TABLE customers ADD COLUMN customer_type VARCHAR(30) NOT NULL DEFAULT 'persona_fisica' AFTER business_name",
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
    $conn->query("ALTER TABLE customers MODIFY COLUMN customer_type VARCHAR(30) NOT NULL DEFAULT 'persona_fisica'");
    $conn->query("UPDATE customers SET customer_type = 'persona_fisica' WHERE customer_type IS NULL OR customer_type = ''");
    $conn->query("UPDATE customers SET customer_type = 'persona_fisica' WHERE customer_type NOT IN ('persona_fisica', 'persona_moral')");

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
            price_base_amount DECIMAL(10,2) NULL,
            price_iva_amount DECIMAL(10,2) NULL,
            price_isr_withholding_amount DECIMAL(10,2) NULL,
            price_total_amount DECIMAL(10,2) NULL,
            price_amount DECIMAL(10,2) NULL,
            price_currency CHAR(3) NULL DEFAULT 'MXN',
            price_updated_by INT UNSIGNED NULL,
            price_updated_at TIMESTAMP NULL DEFAULT NULL,
            service_date DATE NULL,
            service_start_time TIME NULL,
            service_end_time TIME NULL,
            assigned_user_id INT UNSIGNED NULL,
            assigned_by_user_id INT UNSIGNED NULL,
            assigned_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_tickets_customer_id (customer_id),
            KEY idx_tickets_status (status),
            KEY idx_tickets_consultation_type (consultation_type),
            KEY idx_tickets_created_at (created_at),
            KEY idx_tickets_origin_type (origin_type),
            KEY idx_tickets_price_updated_by (price_updated_by),
            KEY idx_tickets_assigned_user_id (assigned_user_id),
            KEY idx_tickets_assigned_by_user_id (assigned_by_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ticketColumns = [
        'price_base_amount' => "ALTER TABLE tickets ADD COLUMN price_base_amount DECIMAL(10,2) NULL AFTER attachment_path",
        'price_iva_amount' => "ALTER TABLE tickets ADD COLUMN price_iva_amount DECIMAL(10,2) NULL AFTER price_base_amount",
        'price_isr_withholding_amount' => "ALTER TABLE tickets ADD COLUMN price_isr_withholding_amount DECIMAL(10,2) NULL AFTER price_iva_amount",
        'price_total_amount' => "ALTER TABLE tickets ADD COLUMN price_total_amount DECIMAL(10,2) NULL AFTER price_isr_withholding_amount",
        'price_amount' => "ALTER TABLE tickets ADD COLUMN price_amount DECIMAL(10,2) NULL AFTER attachment_path",
        'price_currency' => "ALTER TABLE tickets ADD COLUMN price_currency CHAR(3) NULL DEFAULT 'MXN' AFTER price_amount",
        'price_updated_by' => "ALTER TABLE tickets ADD COLUMN price_updated_by INT UNSIGNED NULL AFTER price_currency",
        'price_updated_at' => "ALTER TABLE tickets ADD COLUMN price_updated_at TIMESTAMP NULL DEFAULT NULL AFTER price_updated_by",
        'message_updated_by' => "ALTER TABLE tickets ADD COLUMN message_updated_by INT UNSIGNED NULL AFTER message",
        'message_updated_at' => "ALTER TABLE tickets ADD COLUMN message_updated_at TIMESTAMP NULL DEFAULT NULL AFTER message_updated_by",
        'service_date' => "ALTER TABLE tickets ADD COLUMN service_date DATE NULL AFTER price_updated_at",
        'service_start_time' => "ALTER TABLE tickets ADD COLUMN service_start_time TIME NULL AFTER service_date",
        'service_end_time' => "ALTER TABLE tickets ADD COLUMN service_end_time TIME NULL AFTER service_start_time",
        'assigned_user_id' => "ALTER TABLE tickets ADD COLUMN assigned_user_id INT UNSIGNED NULL AFTER service_end_time",
        'assigned_by_user_id' => "ALTER TABLE tickets ADD COLUMN assigned_by_user_id INT UNSIGNED NULL AFTER assigned_user_id",
        'assigned_at' => "ALTER TABLE tickets ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_by_user_id",
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
    if (!customer_index_exists($conn, 'tickets', 'idx_tickets_message_updated_by')) {
        $conn->query('ALTER TABLE tickets ADD INDEX idx_tickets_message_updated_by (message_updated_by)');
    }
    if (!customer_index_exists($conn, 'tickets', 'idx_tickets_assigned_user_id')) {
        $conn->query('ALTER TABLE tickets ADD INDEX idx_tickets_assigned_user_id (assigned_user_id)');
    }
    if (!customer_index_exists($conn, 'tickets', 'idx_tickets_assigned_by_user_id')) {
        $conn->query('ALTER TABLE tickets ADD INDEX idx_tickets_assigned_by_user_id (assigned_by_user_id)');
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

function normalize_customer_type($customerType = null) {
    $customerType = trim((string) ($customerType ?? ''));

    if ($customerType === '') {
        return 'persona_fisica';
    }

    return in_array($customerType, ['persona_fisica', 'persona_moral'], true)
        ? $customerType
        : 'persona_fisica';
}

function customer_type_label($customerType = null) {
    return normalize_customer_type($customerType) === 'persona_moral'
        ? 'Persona moral'
        : 'Persona fisica';
}

function validate_customer_payload($name, $phone, $businessName = null, $customerType = null) {
    $name = trim((string) $name);
    $businessName = trim((string) ($businessName ?? ''));
    $normalizedPhone = normalize_phone_value($phone);
    $customerType = normalize_customer_type($customerType);

    if ($name === '' || $normalizedPhone === null) {
        return 'Nombre y teléfono son obligatorios.';
    }

    if ($customerType === 'persona_moral' && $businessName === '') {
        return 'La razón social es obligatoria para persona moral.';
    }

    return '';
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

function round_ticket_money($amount) {
    return number_format(round((float) $amount, 2), 2, '.', '');
}

function calculate_ticket_price_components($baseAmount, $customerType = null) {
    $normalizedPrice = normalize_ticket_price_input($baseAmount);

    if (!$normalizedPrice['ok'] || $normalizedPrice['value'] === null) {
        return [
            'ok' => false,
            'error' => $normalizedPrice['error'] ?: 'Ingresa un precio base válido.',
            'components' => null,
        ];
    }

    $customerType = normalize_customer_type($customerType);
    $baseAmountValue = round_ticket_money($normalizedPrice['value']);
    $ivaAmount = round_ticket_money((float) $baseAmountValue * 0.16);
    $subtotalWithIva = round_ticket_money((float) $baseAmountValue + (float) $ivaAmount);
    $isrWithholdingAmount = $customerType === 'persona_moral'
        ? round_ticket_money((float) $baseAmountValue * 0.0125)
        : null;
    $totalAmount = $customerType === 'persona_moral'
        ? round_ticket_money((float) $subtotalWithIva - (float) $isrWithholdingAmount)
        : $subtotalWithIva;

    return [
        'ok' => true,
        'error' => '',
        'components' => [
            'base_amount' => $baseAmountValue,
            'iva_amount' => $ivaAmount,
            'isr_withholding_amount' => $isrWithholdingAmount,
            'total_amount' => $totalAmount,
            'customer_type' => $customerType,
        ],
    ];
}

function get_ticket_effective_price_amount($ticket) {
    if (!is_array($ticket)) {
        return null;
    }

    if (isset($ticket['price_total_amount']) && $ticket['price_total_amount'] !== null && $ticket['price_total_amount'] !== '') {
        return $ticket['price_total_amount'];
    }

    return $ticket['price_amount'] ?? null;
}

function get_ticket_effective_price_base_amount($ticket) {
    if (!is_array($ticket)) {
        return null;
    }

    return $ticket['price_base_amount'] ?? null;
}

function ticket_has_price_breakdown($ticket) {
    if (!is_array($ticket)) {
        return false;
    }

    return isset($ticket['price_base_amount'], $ticket['price_iva_amount'], $ticket['price_total_amount'])
        && $ticket['price_base_amount'] !== null
        && $ticket['price_iva_amount'] !== null
        && $ticket['price_total_amount'] !== null;
}

function format_ticket_price($amount, $currency = 'MXN') {
    if ($amount === null || $amount === '') {
        return 'Sin precio asignado';
    }

    return trim((string) $currency) . ' ' . number_format((float) $amount, 2, '.', ',');
}

function normalize_ticket_service_date_input($serviceDate) {
    $serviceDate = trim((string) $serviceDate);
    if ($serviceDate === '') {
        return ['ok' => true, 'value' => null, 'error' => ''];
    }

    $date = DateTime::createFromFormat('Y-m-d', $serviceDate);
    if (!$date || $date->format('Y-m-d') !== $serviceDate) {
        return ['ok' => false, 'value' => null, 'error' => 'Ingresa una fecha de servicio válida.'];
    }

    return ['ok' => true, 'value' => $serviceDate, 'error' => ''];
}

function normalize_ticket_service_time_input($serviceTime, $fieldLabel) {
    $serviceTime = trim((string) $serviceTime);
    $fieldLabel = trim((string) $fieldLabel);

    if ($serviceTime === '') {
        return ['ok' => true, 'value' => null, 'error' => ''];
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $serviceTime)) {
        return ['ok' => false, 'value' => null, 'error' => 'Ingresa una ' . strtolower($fieldLabel) . ' válida.'];
    }

    $time = DateTime::createFromFormat('H:i', $serviceTime);
    if (!$time || $time->format('H:i') !== $serviceTime) {
        return ['ok' => false, 'value' => null, 'error' => 'Ingresa una ' . strtolower($fieldLabel) . ' válida.'];
    }

    return ['ok' => true, 'value' => $time->format('H:i:s'), 'error' => ''];
}

function normalize_ticket_schedule_inputs($serviceDate, $startTime, $endTime) {
    $normalizedDate = normalize_ticket_service_date_input($serviceDate);
    if (!$normalizedDate['ok']) {
        return ['ok' => false, 'error' => $normalizedDate['error'], 'service_date' => null, 'service_start_time' => null, 'service_end_time' => null];
    }

    $normalizedStart = normalize_ticket_service_time_input($startTime, 'hora de inicio');
    if (!$normalizedStart['ok']) {
        return ['ok' => false, 'error' => $normalizedStart['error'], 'service_date' => null, 'service_start_time' => null, 'service_end_time' => null];
    }

    $normalizedEnd = normalize_ticket_service_time_input($endTime, 'hora de finalización');
    if (!$normalizedEnd['ok']) {
        return ['ok' => false, 'error' => $normalizedEnd['error'], 'service_date' => null, 'service_start_time' => null, 'service_end_time' => null];
    }

    $serviceDateValue = $normalizedDate['value'];
    $startValue = $normalizedStart['value'];
    $endValue = $normalizedEnd['value'];

    if (($startValue === null) !== ($endValue === null)) {
        return ['ok' => false, 'error' => 'Captura tanto la hora de inicio como la hora de finalización para calcular la duración.', 'service_date' => null, 'service_start_time' => null, 'service_end_time' => null];
    }

    if ($startValue !== null && calculate_ticket_service_duration_minutes($startValue, $endValue) === null) {
        return ['ok' => false, 'error' => 'La hora de finalización debe ser posterior o igual a la hora de inicio.', 'service_date' => null, 'service_start_time' => null, 'service_end_time' => null];
    }

    return [
        'ok' => true,
        'error' => '',
        'service_date' => $serviceDateValue,
        'service_start_time' => $startValue,
        'service_end_time' => $endValue,
    ];
}

function ticket_status_requires_programmed_date($status) {
    return trim((string) $status) === 'Agendado';
}

function validate_ticket_programmed_date_requirement($status, $serviceDate) {
    if (ticket_status_requires_programmed_date($status) && trim((string) $serviceDate) === '') {
        return ['ok' => false, 'error' => 'El estado Agendado requiere una fecha programada.'];
    }

    return ['ok' => true, 'error' => ''];
}

function calculate_ticket_service_duration_minutes($startTime, $endTime) {
    $startTime = trim((string) $startTime);
    $endTime = trim((string) $endTime);

    if ($startTime === '' || $endTime === '') {
        return null;
    }

    try {
        $start = new DateTime('1970-01-01 ' . $startTime);
        $end = new DateTime('1970-01-01 ' . $endTime);
    } catch (Exception $exception) {
        return null;
    }

    if ($end < $start) {
        return null;
    }

    return (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);
}

function format_ticket_service_date($serviceDate) {
    $serviceDate = trim((string) $serviceDate);
    if ($serviceDate === '') {
        return 'Pendiente de registrar';
    }

    try {
        return (new DateTime($serviceDate))->format('d/m/Y');
    } catch (Exception $exception) {
        return $serviceDate;
    }
}

function format_ticket_service_time($serviceTime) {
    $serviceTime = trim((string) $serviceTime);
    if ($serviceTime === '') {
        return 'Pendiente de registrar';
    }

    try {
        return (new DateTime($serviceTime))->format('H:i');
    } catch (Exception $exception) {
        return $serviceTime;
    }
}

function format_ticket_service_duration($startTime, $endTime) {
    $minutes = calculate_ticket_service_duration_minutes($startTime, $endTime);
    if ($minutes === null) {
        return 'Pendiente de registrar';
    }

    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;

    if ($hours > 0 && $remainingMinutes > 0) {
        return $hours . ' h ' . $remainingMinutes . ' min';
    }

    if ($hours > 0) {
        return $hours . ' h';
    }

    return $remainingMinutes . ' min';
}

function normalize_admin_username($username) {
    return trim((string) $username);
}

function normalize_admin_display_name($displayName, $fallbackUsername = '') {
    $displayName = trim((string) $displayName);
    $fallbackUsername = trim((string) $fallbackUsername);
    return $displayName !== '' ? $displayName : $fallbackUsername;
}

function normalize_admin_role($role) {
    $role = trim((string) $role);
    return in_array($role, ['admin', 'agent'], true) ? $role : 'agent';
}

function getAdminUserDisplayLabel(array $user) {
    $displayName = trim((string) ($user['display_name'] ?? ''));
    $username = trim((string) ($user['username'] ?? ''));
    return $displayName !== '' ? $displayName : $username;
}

function customer_index_exists($conn, $table, $indexName) {
    $table = $conn->real_escape_string($table);
    $indexName = $conn->real_escape_string($indexName);
    $result = $conn->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");

    return $result && $result->num_rows > 0;
}

function getAdminUserById($conn, $userId) {
    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function findAdminUserByUsername($conn, $username, $excludeUserId = 0) {
    $username = normalize_admin_username($username);
    $excludeUserId = (int) $excludeUserId;

    if ($username === '') {
        return null;
    }

    if ($excludeUserId > 0) {
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $username, $excludeUserId);
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function getAdminUsers($conn, $assignableOnly = false) {
    $sql = 'SELECT * FROM users';
    if ($assignableOnly) {
        $sql .= ' WHERE active = 1';
    }
    $sql .= ' ORDER BY COALESCE(NULLIF(display_name, \'\'), username) ASC, id ASC';
    return $conn->query($sql);
}

function create_admin_user($conn, $username, $password, $displayName = '', $active = 1, $role = 'agent') {
    $username = normalize_admin_username($username);
    $displayName = normalize_admin_display_name($displayName, $username);
    $password = (string) $password;
    $active = (int) $active === 1 ? 1 : 0;
    $role = normalize_admin_role($role);

    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'Usuario y contraseña son obligatorios.', 'user' => null];
    }

    if (findAdminUserByUsername($conn, $username)) {
        return ['ok' => false, 'error' => 'Ya existe una cuenta con ese usuario.', 'user' => null];
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, password, display_name, role, active) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssi', $username, $passwordHash, $displayName, $role, $active);

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo crear la cuenta administrativa.', 'user' => null];
    }

    return ['ok' => true, 'error' => '', 'user' => getAdminUserById($conn, (int) $conn->insert_id)];
}

function update_admin_user_by_id($conn, $userId, $username, $displayName = '', $active = 1, $password = '', $role = 'agent') {
    $userId = (int) $userId;
    $username = normalize_admin_username($username);
    $displayName = normalize_admin_display_name($displayName, $username);
    $password = (string) $password;
    $active = (int) $active === 1 ? 1 : 0;
    $role = normalize_admin_role($role);

    if ($userId <= 0 || $username === '') {
        return ['ok' => false, 'error' => 'El usuario es obligatorio.', 'user' => null];
    }

    $currentUser = getAdminUserById($conn, $userId);
    if (!$currentUser) {
        return ['ok' => false, 'error' => 'La cuenta ya no existe.', 'user' => null];
    }

    if (findAdminUserByUsername($conn, $username, $userId)) {
        return ['ok' => false, 'error' => 'Ya existe otra cuenta con ese usuario.', 'user' => null];
    }

    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET username = ?, display_name = ?, role = ?, active = ?, password = ? WHERE id = ?');
        $stmt->bind_param('sssisi', $username, $displayName, $role, $active, $passwordHash, $userId);
    } else {
        $stmt = $conn->prepare('UPDATE users SET username = ?, display_name = ?, role = ?, active = ? WHERE id = ?');
        $stmt->bind_param('sssii', $username, $displayName, $role, $active, $userId);
    }

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar la cuenta administrativa.', 'user' => null];
    }

    return ['ok' => true, 'error' => '', 'user' => getAdminUserById($conn, $userId)];
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

function create_or_update_customer($conn, $name, $email = null, $phone = null, $businessName = null, $customerType = 'persona_fisica') {
    $normalizedEmail = normalize_email_value($email);
    $normalizedPhone = normalize_phone_value($phone);
    $name = trim((string) $name);
    $email = trim((string) ($email ?? ''));
    $phone = trim((string) ($phone ?? ''));
    $businessName = trim((string) ($businessName ?? ''));
    $customerType = normalize_customer_type($customerType);

    if (validate_customer_payload($name, $phone, $businessName, $customerType) !== '') {
        return null;
    }

    $existing = find_customer_by_phone($conn, $phone);
    if ($existing) {
        $nextName = $existing['name'];
        $nextBusinessName = $existing['business_name'] ?? '';
        $nextPhone = (string) ($existing['phone'] ?? '');
        $nextEmail = (string) ($existing['email'] ?? '');
        $nextNormalizedEmail = normalize_email_value($nextEmail);
        $nextCustomerType = normalize_customer_type($existing['customer_type'] ?? 'persona_fisica');

        if ($name !== '' && $name !== $existing['name']) {
            $nextName = $name;
        }

        if ($phone !== '' && $phone !== (string) $existing['phone']) {
            $nextPhone = $phone;
        }

        if ($businessName !== '' && $businessName !== (string) ($existing['business_name'] ?? '')) {
            $nextBusinessName = $businessName;
        }

        if ($customerType !== $nextCustomerType) {
            $nextCustomerType = $customerType;
        }

        if ($normalizedEmail !== null) {
            $nextEmail = $email;
            $nextNormalizedEmail = $normalizedEmail;
        }

        $nextNormalizedPhone = normalize_phone_value($nextPhone);
        if (validate_customer_payload($nextName, $nextPhone, $nextBusinessName, $nextCustomerType) !== '') {
            return null;
        }

        $stmt = $conn->prepare('UPDATE customers SET name = ?, business_name = ?, customer_type = ?, email = ?, email_normalized = ?, phone = ?, phone_normalized = ? WHERE id = ?');
        $stmt->bind_param('sssssssi', $nextName, $nextBusinessName, $nextCustomerType, $nextEmail, $nextNormalizedEmail, $nextPhone, $nextNormalizedPhone, $existing['id']);
        if (!$stmt->execute()) {
            return null;
        }

        return getCustomerById($conn, (int) $existing['id']);
    }

    $stmt = $conn->prepare('INSERT INTO customers (name, business_name, customer_type, email, email_normalized, phone, phone_normalized) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssss', $name, $businessName, $customerType, $email, $normalizedEmail, $phone, $normalizedPhone);
    if (!$stmt->execute()) {
        return null;
    }

    return getCustomerById($conn, (int) $conn->insert_id);
}

function update_customer_by_id($conn, $customerId, $name, $email = null, $phone = null, $businessName = null, $customerType = 'persona_fisica') {
    $customerId = (int) $customerId;
    $normalizedEmail = normalize_email_value($email);
    $name = trim((string) $name);
    $email = trim((string) ($email ?? ''));
    $phone = trim((string) ($phone ?? ''));
    $businessName = trim((string) ($businessName ?? ''));
    $customerType = normalize_customer_type($customerType);
    $normalizedPhone = normalize_phone_value($phone);

    if ($customerId <= 0) {
        return ['ok' => false, 'error' => 'Nombre y teléfono son obligatorios.', 'customer' => null];
    }

    $validationError = validate_customer_payload($name, $phone, $businessName, $customerType);
    if ($validationError !== '') {
        return ['ok' => false, 'error' => $validationError, 'customer' => null];
    }

    $currentCustomer = getCustomerById($conn, $customerId);
    if (!$currentCustomer) {
        return ['ok' => false, 'error' => 'El cliente ya no existe.', 'customer' => null];
    }

    $conflictCustomer = find_customer_by_phone_excluding_id($conn, $phone, $customerId);
    if ($conflictCustomer) {
        return ['ok' => false, 'error' => 'Ya existe otro cliente con ese número de teléfono.', 'customer' => null];
    }

    $stmt = $conn->prepare('UPDATE customers SET name = ?, business_name = ?, customer_type = ?, email = ?, email_normalized = ?, phone = ?, phone_normalized = ? WHERE id = ?');
    $stmt->bind_param('sssssssi', $name, $businessName, $customerType, $email, $normalizedEmail, $phone, $normalizedPhone, $customerId);

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
                price_base_amount,
                price_iva_amount,
                price_isr_withholding_amount,
                price_total_amount,
                price_amount,
                price_currency,
                price_updated_by,
                price_updated_at,
                service_date,
                service_start_time,
                service_end_time,
                assigned_user_id,
                assigned_by_user_id,
                assigned_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

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
    $customer = getCustomerById($conn, $customerId);
    if (!$customer) {
        return null;
    }

    $priceBaseAmount = null;
    $priceIvaAmount = null;
    $priceIsrWithholdingAmount = null;
    $priceTotalAmount = null;
    $priceAmount = null;
    $priceCurrency = null;
    $priceUpdatedBy = null;
    $priceUpdatedAt = null;

    if (isset($data['price_amount']) && $data['price_amount'] !== '') {
        $priceCalculation = calculate_ticket_price_components($data['price_amount'], $customer['customer_type'] ?? 'persona_fisica');
        if (!$priceCalculation['ok']) {
            return null;
        }

        $components = $priceCalculation['components'];
        $priceBaseAmount = $components['base_amount'];
        $priceIvaAmount = $components['iva_amount'];
        $priceIsrWithholdingAmount = $components['isr_withholding_amount'];
        $priceTotalAmount = $components['total_amount'];
        $priceAmount = $priceTotalAmount;
        $priceCurrency = 'MXN';
        $priceUpdatedBy = !empty($data['price_updated_by']) ? (int) $data['price_updated_by'] : null;
        $priceUpdatedAt = date('Y-m-d H:i:s');
    }
    $scheduleValidation = normalize_ticket_schedule_inputs(
        $data['service_date'] ?? null,
        $data['service_start_time'] ?? null,
        $data['service_end_time'] ?? null
    );
    if (!$scheduleValidation['ok']) {
        return null;
    }

    $programmedDateValidation = validate_ticket_programmed_date_requirement($status, $scheduleValidation['service_date']);
    if (!$programmedDateValidation['ok']) {
        return null;
    }

    $serviceDate = $scheduleValidation['service_date'];
    $serviceStartTime = $scheduleValidation['service_start_time'];
    $serviceEndTime = $scheduleValidation['service_end_time'];
    $assignedUserId = isset($data['assigned_user_id']) && (int) $data['assigned_user_id'] > 0 ? (int) $data['assigned_user_id'] : null;
    $assignedByUserId = $assignedUserId !== null && !empty($data['assigned_by_user_id']) ? (int) $data['assigned_by_user_id'] : null;
    $assignedAt = $assignedUserId !== null ? date('Y-m-d H:i:s') : null;

    $stmt->bind_param(
        'isssiisssssssssssssisiis',
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
        $priceBaseAmount,
        $priceIvaAmount,
        $priceIsrWithholdingAmount,
        $priceTotalAmount,
        $priceAmount,
        $priceCurrency,
        $priceUpdatedBy,
        $priceUpdatedAt,
        $serviceDate,
        $serviceStartTime,
        $serviceEndTime,
        $assignedUserId,
        $assignedByUserId,
        $assignedAt
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
                c.customer_type AS customer_type,
                c.business_name AS customer_business_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                message_editor.username AS message_updated_by_username,
                message_editor.display_name AS message_updated_by_display_name,
                price_editor.username AS price_updated_by_username,
                price_editor.display_name AS price_updated_by_display_name,
                assigned_user.username AS assigned_username,
                assigned_user.display_name AS assigned_display_name,
                assigned_by_user.username AS assigned_by_username,
                assigned_by_user.display_name AS assigned_by_display_name
            FROM tickets t
            INNER JOIN customers c ON c.id = t.customer_id
            LEFT JOIN users message_editor ON message_editor.id = t.message_updated_by
            LEFT JOIN users price_editor ON price_editor.id = t.price_updated_by
            LEFT JOIN users assigned_user ON assigned_user.id = t.assigned_user_id
            LEFT JOIN users assigned_by_user ON assigned_by_user.id = t.assigned_by_user_id
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

function getCustomerTickets($conn, $customerId, $viewerUserId = 0, $viewerRole = 'admin') {
    $customerId = (int) $customerId;
    $viewerUserId = (int) $viewerUserId;
    $viewerRole = trim((string) $viewerRole);

    if ($viewerRole === 'admin') {
        $stmt = $conn->prepare('SELECT * FROM tickets WHERE customer_id = ? ORDER BY created_at DESC, id DESC');
        $stmt->bind_param('i', $customerId);
    } else {
        $stmt = $conn->prepare('SELECT * FROM tickets WHERE customer_id = ? AND assigned_user_id = ? ORDER BY created_at DESC, id DESC');
        $stmt->bind_param('ii', $customerId, $viewerUserId);
    }
    $stmt->execute();

    return $stmt->get_result();
}

function getTicketStats($conn, $viewerUserId = 0, $viewerRole = 'admin') {
    $stats = [
        'total' => 0,
        'open' => 0,
        'customers' => 0,
    ];
    $viewerUserId = (int) $viewerUserId;
    $viewerRole = trim((string) $viewerRole);

    if ($viewerRole === 'admin') {
        if ($result = $conn->query('SELECT COUNT(*) FROM tickets')) {
            $stats['total'] = (int) $result->fetch_row()[0];
        }

        if ($result = $conn->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('Resuelto', 'Cerrado')")) {
            $stats['open'] = (int) $result->fetch_row()[0];
        }
    } else {
        if ($stmt = $conn->prepare('SELECT COUNT(*) FROM tickets WHERE assigned_user_id = ?')) {
            $stmt->bind_param('i', $viewerUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total'] = $result ? (int) $result->fetch_row()[0] : 0;
        }

        if ($stmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_user_id = ? AND status NOT IN ('Resuelto', 'Cerrado')")) {
            $stmt->bind_param('i', $viewerUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['open'] = $result ? (int) $result->fetch_row()[0] : 0;
        }
    }

    if ($result = $conn->query('SELECT COUNT(*) FROM customers')) {
        $stats['customers'] = (int) $result->fetch_row()[0];
    }

    return $stats;
}

function build_support_ticket_filter_parts(array $filters = [], $viewerUserId = 0, $viewerRole = 'admin') {
    $conditions = [];
    $types = '';
    $params = [];
    $viewerUserId = (int) $viewerUserId;
    $viewerRole = trim((string) $viewerRole);

    $scope = trim((string) ($filters['scope'] ?? 'open'));
    if ($scope !== 'all') {
        $conditions[] = "t.status NOT IN ('Resuelto', 'Cerrado')";
    }

    if ($viewerRole !== 'admin') {
        $conditions[] = 't.assigned_user_id = ?';
        $types .= 'i';
        $params[] = $viewerUserId;
    }

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

function getSupportTicketsCount($conn, array $filters = [], $viewerUserId = 0, $viewerRole = 'admin') {
    $parts = build_support_ticket_filter_parts($filters, $viewerUserId, $viewerRole);
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

function getSupportTickets($conn, array $filters = [], $limit = null, $offset = 0, $viewerUserId = 0, $viewerRole = 'admin') {
    $parts = build_support_ticket_filter_parts($filters, $viewerUserId, $viewerRole);
    $sql = "SELECT
                t.*,
                c.name AS customer_name,
                c.customer_type AS customer_type,
                c.business_name AS customer_business_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                price_editor.username AS price_updated_by_username,
                price_editor.display_name AS price_updated_by_display_name,
                assigned_user.username AS assigned_username,
                assigned_user.display_name AS assigned_display_name
            FROM tickets t
            INNER JOIN customers c ON c.id = t.customer_id
            LEFT JOIN users price_editor ON price_editor.id = t.price_updated_by
            LEFT JOIN users assigned_user ON assigned_user.id = t.assigned_user_id";

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

function can_admin_user_access_ticket(array $ticket, $viewerUserId, $viewerRole) {
    $viewerUserId = (int) $viewerUserId;
    $viewerRole = trim((string) $viewerRole);

    if ($viewerRole === 'admin') {
        return true;
    }

    return $viewerUserId > 0 && (int) ($ticket['assigned_user_id'] ?? 0) === $viewerUserId;
}

function update_ticket_status($conn, $ticketId, $status) {
    $ticketId = (int) $ticketId;
    $ticket = getTicketById($conn, $ticketId);

    if (!$ticket) {
        return false;
    }

    $programmedDateValidation = validate_ticket_programmed_date_requirement($status, $ticket['service_date'] ?? null);
    if (!$programmedDateValidation['ok']) {
        return false;
    }

    $stmt = $conn->prepare('UPDATE tickets SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $ticketId);
    return $stmt->execute();
}

function update_ticket_price($conn, $ticketId, $priceAmount, $adminUserId) {
    $ticketId = (int) $ticketId;
    $adminUserId = (int) $adminUserId;
    $ticket = getTicketById($conn, $ticketId);

    if ($ticketId <= 0) {
        return ['ok' => false, 'error' => 'No se pudo identificar el ticket.', 'ticket' => null];
    }

    if (!$ticket) {
        return ['ok' => false, 'error' => 'El ticket ya no existe.', 'ticket' => null];
    }

    $priceCalculation = calculate_ticket_price_components($priceAmount, $ticket['customer_type'] ?? 'persona_fisica');
    if (!$priceCalculation['ok']) {
        return ['ok' => false, 'error' => $priceCalculation['error'] ?: 'Ingresa un precio base válido.', 'ticket' => null];
    }

    $components = $priceCalculation['components'];
    $priceCurrency = 'MXN';
    $updatedAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        'UPDATE tickets
         SET price_base_amount = ?, price_iva_amount = ?, price_isr_withholding_amount = ?, price_total_amount = ?, price_amount = ?, price_currency = ?, price_updated_by = ?, price_updated_at = ?
         WHERE id = ?'
    );

    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar la actualización del precio.', 'ticket' => null];
    }

    $stmt->bind_param(
        'ssssssisi',
        $components['base_amount'],
        $components['iva_amount'],
        $components['isr_withholding_amount'],
        $components['total_amount'],
        $components['total_amount'],
        $priceCurrency,
        $adminUserId,
        $updatedAt,
        $ticketId
    );

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar el precio del ticket.', 'ticket' => null];
    }

    return ['ok' => true, 'error' => '', 'ticket' => getTicketById($conn, $ticketId)];
}

function update_ticket_message($conn, $ticketId, $message, $adminUserId = null) {
    $ticketId = (int) $ticketId;
    $message = trim((string) $message);
    $adminUserId = $adminUserId !== null ? (int) $adminUserId : null;

    if ($ticketId <= 0) {
        return ['ok' => false, 'error' => 'No se pudo identificar el ticket.', 'ticket' => null];
    }

    if ($message === '') {
        return ['ok' => false, 'error' => 'El mensaje del ticket es obligatorio.', 'ticket' => null];
    }

    $ticket = getTicketById($conn, $ticketId);
    if (!$ticket) {
        return ['ok' => false, 'error' => 'El ticket ya no existe.', 'ticket' => null];
    }

    $updatedAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        'UPDATE tickets SET message = ?, message_updated_by = ?, message_updated_at = ? WHERE id = ?'
    );

    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar la actualización del mensaje.', 'ticket' => null];
    }

    $stmt->bind_param('sisi', $message, $adminUserId, $updatedAt, $ticketId);

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar el mensaje del ticket.', 'ticket' => null];
    }

    return ['ok' => true, 'error' => '', 'ticket' => getTicketById($conn, $ticketId)];
}

function update_ticket_assignee($conn, $ticketId, $assignedUserId, $assignedByUserId) {
    $ticketId = (int) $ticketId;
    $assignedByUserId = (int) $assignedByUserId;
    $assignedUserId = (int) $assignedUserId;

    if ($ticketId <= 0) {
        return ['ok' => false, 'error' => 'No se pudo identificar el ticket.', 'ticket' => null];
    }

    if ($assignedUserId <= 0) {
        return ['ok' => false, 'error' => 'Selecciona un responsable válido.', 'ticket' => null];
    }

    $assignee = getAdminUserById($conn, $assignedUserId);
    if (!$assignee || (int) ($assignee['active'] ?? 0) !== 1) {
        return ['ok' => false, 'error' => 'El responsable seleccionado no está disponible para asignación.', 'ticket' => null];
    }

    $assignedAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        'UPDATE tickets SET assigned_user_id = ?, assigned_by_user_id = ?, assigned_at = ? WHERE id = ?'
    );
    $stmt->bind_param('iisi', $assignedUserId, $assignedByUserId, $assignedAt, $ticketId);

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar el responsable del ticket.', 'ticket' => null];
    }

    return ['ok' => true, 'error' => '', 'ticket' => getTicketById($conn, $ticketId)];
}

function update_ticket_service_schedule($conn, $ticketId, $serviceDate, $startTime, $endTime) {
    $ticketId = (int) $ticketId;

    if ($ticketId <= 0) {
        return ['ok' => false, 'error' => 'No se pudo identificar el ticket.', 'ticket' => null];
    }

    $ticket = getTicketById($conn, $ticketId);
    if (!$ticket) {
        return ['ok' => false, 'error' => 'El ticket ya no existe.', 'ticket' => null];
    }

    $scheduleValidation = normalize_ticket_schedule_inputs($serviceDate, $startTime, $endTime);
    if (!$scheduleValidation['ok']) {
        return ['ok' => false, 'error' => $scheduleValidation['error'], 'ticket' => null];
    }

    $programmedDateValidation = validate_ticket_programmed_date_requirement($ticket['status'] ?? '', $scheduleValidation['service_date']);
    if (!$programmedDateValidation['ok']) {
        return ['ok' => false, 'error' => $programmedDateValidation['error'], 'ticket' => null];
    }

    $stmt = $conn->prepare(
        'UPDATE tickets SET service_date = ?, service_start_time = ?, service_end_time = ? WHERE id = ?'
    );

    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar la actualización de la orden de servicio.', 'ticket' => null];
    }

    $stmt->bind_param(
        'sssi',
        $scheduleValidation['service_date'],
        $scheduleValidation['service_start_time'],
        $scheduleValidation['service_end_time'],
        $ticketId
    );

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => 'No se pudo actualizar la ejecución del servicio.', 'ticket' => null];
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
