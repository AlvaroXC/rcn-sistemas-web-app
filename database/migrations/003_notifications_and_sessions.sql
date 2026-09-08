CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_user_id INT NULL,
    ticket_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'ticket_assignment',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_notifications_user_id (user_id),
    KEY idx_admin_notifications_ticket_id (ticket_id),
    KEY idx_admin_notifications_is_read (is_read)
);

CREATE TABLE IF NOT EXISTS admin_remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector VARCHAR(24) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_remember_tokens_user_id (user_id),
    KEY idx_admin_remember_tokens_expires_at (expires_at)
);
