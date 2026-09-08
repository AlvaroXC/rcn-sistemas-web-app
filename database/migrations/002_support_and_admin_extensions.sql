CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    business_name VARCHAR(255) NULL,
    customer_type VARCHAR(30) NOT NULL DEFAULT 'persona_fisica',
    email VARCHAR(255) NULL,
    email_normalized VARCHAR(255) NULL,
    phone VARCHAR(50) NOT NULL,
    phone_normalized VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customers_email_normalized (email_normalized),
    UNIQUE KEY uniq_customers_phone_normalized (phone_normalized),
    KEY idx_customers_name (name)
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
    appointment_date DATE NULL,
    appointment_time TIME NULL,
    service_date DATE NULL,
    service_start_time TIME NULL,
    service_end_time TIME NULL,
    assigned_user_id INT UNSIGNED NULL,
    assigned_by_user_id INT UNSIGNED NULL,
    assigned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_tickets_customer_id (customer_id),
    KEY idx_tickets_status (status),
    KEY idx_tickets_created_at (created_at),
    KEY idx_tickets_assigned_user_id (assigned_user_id)
);

CREATE TABLE IF NOT EXISTS ticket_observations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    admin_user_id INT UNSIGNED NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ticket_observations_ticket_id (ticket_id)
);
