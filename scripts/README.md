# Scripts de mantenimiento

`migrate.php` aplica las migraciones de esquema pendientes durante el despliegue.

Al habilitar el calendario compartido de Google en una instalación existente, después de aplicar las migraciones y conectar la cuenta común desde el panel, ejecutar `migrate_google_calendar_events.php` para trasladar las asociaciones de eventos existentes. Revisar primero con `--report-only`; después procesar por lotes con `--limit=100` hasta completar la migración. El procedimiento detallado está en [`database/README.md`](../database/README.md).

`fix_duplicate_customer_phones.php` se utiliza únicamente para diagnosticar y corregir duplicados de teléfonos en una copia o respaldo de la base de datos. Debe ejecutarse desde la terminal, nunca desde una solicitud web.

`seed.php` carga productos iniciales desde `productos.json` mediante los repositorios MVC.

Los archivos de diagnóstico y compatibilidad ubicados en la raíz están bloqueados por `.htaccess` y se conservarán solo hasta completar la limpieza de instalaciones antiguas.
