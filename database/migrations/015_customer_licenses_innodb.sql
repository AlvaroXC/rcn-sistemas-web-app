-- 009 creaba customer_licenses sin motor ni juego de caracteres. En servidores cuyo motor por defecto es MyISAM
-- la tabla quedaba en MyISAM/latin1: no admite llaves foráneas (falla 012) ni guarda bien acentos o "®".
-- Ambas sentencias se pueden repetir sin efecto si la tabla ya está en InnoDB/utf8mb4.
ALTER TABLE customer_licenses ENGINE=InnoDB;
ALTER TABLE customer_licenses CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
