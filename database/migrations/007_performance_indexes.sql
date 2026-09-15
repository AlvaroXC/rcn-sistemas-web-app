SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_created_at') = 0, 'ALTER TABLE products ADD INDEX idx_products_created_at (created_at)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'promotions' AND index_name = 'idx_promotions_created_at') = 0, 'ALTER TABLE promotions ADD INDEX idx_promotions_created_at (created_at)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'testimonials' AND index_name = 'idx_testimonials_date') = 0, 'ALTER TABLE testimonials ADD INDEX idx_testimonials_date (date)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'benefits' AND index_name = 'idx_benefits_product_id_id') = 0, 'ALTER TABLE benefits ADD INDEX idx_benefits_product_id_id (product_id, id)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'admin_notifications' AND index_name = 'idx_admin_notifications_user_id_id') = 0, 'ALTER TABLE admin_notifications ADD INDEX idx_admin_notifications_user_id_id (user_id, id)', 'SELECT 1');
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
