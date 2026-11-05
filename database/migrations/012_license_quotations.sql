CREATE TABLE IF NOT EXISTS license_quotations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id INT UNSIGNED NOT NULL,
    folio VARCHAR(50) NOT NULL,
    quoted_at DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'enviada',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_license_quotations_folio (folio),
    KEY idx_license_quotations_license (license_id, quoted_at),
    CONSTRAINT fk_license_quotations_license FOREIGN KEY (license_id) REFERENCES customer_licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
