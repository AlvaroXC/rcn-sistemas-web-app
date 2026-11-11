# Base de datos

Las migraciones deben ejecutarse en orden y una sola vez por ambiente. Antes de aplicarlas sobre una base existente se debe crear un respaldo con `scripts/backup.sh`. El ejecutor toma el nombre de la base desde `DB_NAME`, la crea si no existe y registra las migraciones aplicadas.

```bash
php scripts/migrate.php
```

También puedes usar `database.sql` si ya seleccionaste la base de datos destino con `mysql -D nombre_base`.

## Calendario común de Google

Después de aplicar las migraciones y conectar la integración común desde una sesión administradora, revisa los pendientes con:

```bash
php scripts/migrate_google_calendar_events.php --report-only
```

Procesa hasta 100 tickets por ejecución (puedes ajustar el límite entre 1 y 1000) y repite el comando hasta terminar:

```bash
php scripts/migrate_google_calendar_events.php --limit=100
```

El proceso es reanudable. Un código de salida distinto de cero indica que quedan errores de sincronización o limpieza de eventos antiguos; consulta el reporte y vuelve a ejecutar después de corregir la conexión o el permiso correspondiente. Conserva las filas de `google_calendar_connections` hasta que el reporte confirme que ya no se necesitan para borrar eventos heredados.

Antes del despliegue, genera un respaldo con `scripts/backup.sh`. Configura `GOOGLE_CALENDAR_ID` con el calendario de destino y autoriza desde Google una cuenta que pueda administrar ese calendario. `primary` designa el calendario principal de la cuenta autorizada.

No reviertas el código de sincronización después de que tickets hayan empezado a guardar IDs del calendario común sin restaurar primero las asociaciones anteriores. El rollback no debe borrar automáticamente la tabla `google_calendar_integrations`, los eventos compartidos ni los metadatos de migración; los tickets podrían seguir apuntando a eventos que el código anterior buscaría en otro calendario.

Las solicitudes web no modifican el esquema. Las herramientas de mantenimiento utilizan el bootstrap y las migraciones versionadas.
