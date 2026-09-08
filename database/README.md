# Base de datos

Las migraciones deben ejecutarse en orden y una sola vez por ambiente. Antes de aplicarlas sobre una base existente se debe crear un respaldo con `scripts/backup.sh`. El ejecutor toma el nombre de la base desde `DB_NAME`, la crea si no existe y registra las migraciones aplicadas.

```bash
php scripts/migrate.php
```

También puedes usar `database.sql` si ya seleccionaste la base de datos destino con `mysql -D nombre_base`.

Las solicitudes web no modifican el esquema. Las herramientas de mantenimiento utilizan el bootstrap y las migraciones versionadas.
