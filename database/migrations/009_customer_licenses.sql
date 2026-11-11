CREATE TABLE IF NOT EXISTS customer_licenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    license_type VARCHAR(20) NOT NULL DEFAULT 'anual',
    rfc_scope VARCHAR(10) NOT NULL DEFAULT 'multi',
    users_count INT UNSIGNED NOT NULL DEFAULT 1,
    serial_number VARCHAR(100) NULL,
    version VARCHAR(30) NULL,
    activated_at DATE NULL,
    expires_at DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_customer_licenses_serial_number (serial_number),
    KEY idx_customer_licenses_customer_id (customer_id),
    KEY idx_customer_licenses_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
