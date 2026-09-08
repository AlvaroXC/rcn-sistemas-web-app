-- Selecciona la base de datos destino antes de ejecutar este archivo.
-- Desde la raiz del proyecto se recomienda ejecutar: php scripts/migrate.php
SOURCE database/migrations/001_initial_schema.sql;
SOURCE database/migrations/002_support_and_admin_extensions.sql;
SOURCE database/migrations/003_notifications_and_sessions.sql;
SOURCE database/migrations/004_complete_ticket_audit_schema.sql;
