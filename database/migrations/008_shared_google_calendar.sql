CREATE TABLE IF NOT EXISTS google_calendar_integrations (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    google_user_id VARCHAR(255) NULL,
    google_email VARCHAR(255) NULL,
    access_token_encrypted TEXT NULL,
    refresh_token_encrypted TEXT NULL,
    token_expires_at DATETIME NULL,
    calendar_id VARCHAR(255) NOT NULL DEFAULT 'primary',
    scopes TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'disconnected',
    last_error TEXT NULL,
    connected_at DATETIME NULL,
    disconnected_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_google_calendar_integrations_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_target_id') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_target_id VARCHAR(255) NULL AFTER google_calendar_event_link', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_migration_status') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_migration_status VARCHAR(30) NOT NULL DEFAULT ''not_applicable'' AFTER google_calendar_target_id', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_legacy_event_id') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_legacy_event_id VARCHAR(255) NULL AFTER google_calendar_migration_status', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_legacy_calendar_id') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_legacy_calendar_id VARCHAR(255) NULL AFTER google_calendar_legacy_event_id', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_legacy_user_id') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_legacy_user_id INT NULL AFTER google_calendar_legacy_calendar_id', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_legacy_cleanup_status') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_legacy_cleanup_status VARCHAR(30) NOT NULL DEFAULT ''not_required'' AFTER google_calendar_legacy_user_id', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'google_calendar_legacy_cleanup_error') = 0, 'ALTER TABLE tickets ADD COLUMN google_calendar_legacy_cleanup_error TEXT NULL AFTER google_calendar_legacy_cleanup_status', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

UPDATE tickets
SET google_calendar_migration_status = 'pending'
WHERE appointment_date IS NOT NULL
  AND appointment_time IS NOT NULL
  AND google_calendar_target_id IS NULL;

UPDATE tickets
SET google_calendar_legacy_event_id = google_calendar_event_id,
    google_calendar_legacy_user_id = assigned_user_id,
    google_calendar_legacy_cleanup_status = 'pending'
WHERE google_calendar_event_id IS NOT NULL
  AND google_calendar_target_id IS NULL
  AND google_calendar_legacy_event_id IS NULL;

UPDATE tickets t
JOIN google_calendar_connections c ON c.user_id = t.assigned_user_id
SET t.google_calendar_legacy_calendar_id = c.calendar_id
WHERE t.google_calendar_legacy_event_id IS NOT NULL
  AND t.google_calendar_legacy_calendar_id IS NULL;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tickets' AND index_name = 'idx_tickets_google_calendar_migration') = 0, 'ALTER TABLE tickets ADD INDEX idx_tickets_google_calendar_migration (google_calendar_migration_status, google_calendar_legacy_cleanup_status, id)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
