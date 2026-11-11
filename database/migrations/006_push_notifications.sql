CREATE TABLE IF NOT EXISTS push_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(4096) NOT NULL,
    device_key VARCHAR(255) NULL,
    platform VARCHAR(30) NOT NULL DEFAULT 'web',
    user_agent VARCHAR(512) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_devices_token (token(255)),
    KEY idx_push_devices_user_active (user_id, active),
    KEY idx_push_devices_last_used (last_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS push_preferences (
    user_id INT NOT NULL PRIMARY KEY,
    assignments TINYINT(1) NOT NULL DEFAULT 1,
    observations TINYINT(1) NOT NULL DEFAULT 1,
    status_changes TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS push_delivery_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id INT UNSIGNED NULL,
    ticket_id INT UNSIGNED NULL,
    event_key VARCHAR(150) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    result VARCHAR(30) NOT NULL,
    provider_code VARCHAR(100) NULL,
    error_message VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_delivery_event_device (event_key, device_id),
    KEY idx_push_delivery_ticket (ticket_id),
    KEY idx_push_delivery_created (created_at),
    KEY idx_push_delivery_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
