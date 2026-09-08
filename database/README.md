# Base de datos

Las migraciones deben ejecutarse en orden y una sola vez por ambiente. Antes de aplicarlas sobre una base existente se debe crear un respaldo.

```bash
mysql -u usuario -p < database/migrations/001_initial_schema.sql
mysql -u usuario -p < database/migrations/002_support_and_admin_extensions.sql
mysql -u usuario -p < database/migrations/003_notifications_and_sessions.sql
```

`includes/db.php` todavía contiene compatibilidad temporal para instalaciones antiguas. Esa compatibilidad se retirará cuando todas las instalaciones utilicen estas migraciones.
