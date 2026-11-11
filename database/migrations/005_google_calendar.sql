CREATE TABLE IF NOT EXISTS google_calendar_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    google_user_id VARCHAR(255) NULL,
    google_email VARCHAR(255) NULL,
    access_token_encrypted TEXT NULL,
    refresh_token_encrypted TEXT NULL,
    token_expires_at DATETIME NULL,
    calendar_id VARCHAR(255) NOT NULL DEFAULT 'primary',
    scopes TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'connected',
    last_error TEXT NULL,
    connected_at DATETIME NULL,
    disconnected_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_google_calendar_connections_user_id (user_id),
    KEY idx_google_calendar_connections_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_event_id') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_event_id VARCHAR(255) NULL AFTER service_end_time', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_event_link') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_event_link VARCHAR(1024) NULL AFTER google_calendar_event_id', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_sync_status') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_sync_status VARCHAR(30) NOT NULL DEFAULT ''not_configured'' AFTER google_calendar_event_id', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_sync_error') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_sync_error TEXT NULL AFTER google_calendar_sync_status', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_synced_at') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_synced_at DATETIME NULL AFTER google_calendar_sync_error', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tickets' AND index_name = 'idx_tickets_google_calendar_event_id') = 0, 'ALTER TABLE tickets ADD INDEX idx_tickets_google_calendar_event_id (google_calendar_event_id)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
