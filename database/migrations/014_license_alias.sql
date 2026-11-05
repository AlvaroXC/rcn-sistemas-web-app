SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customer_licenses' AND column_name = 'alias') = 0, 'ALTER TABLE customer_licenses ADD COLUMN alias VARCHAR(100) NULL AFTER product_name', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
