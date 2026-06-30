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
?>
