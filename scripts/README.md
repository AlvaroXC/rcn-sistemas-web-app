# Scripts de mantenimiento

`migrate.php` es el único script requerido durante el despliegue y aplica las migraciones pendientes.

`fix_duplicate_customer_phones.php` se utiliza únicamente para diagnosticar y corregir duplicados de teléfonos en una copia o respaldo de la base de datos. Debe ejecutarse desde la terminal, nunca desde una solicitud web.

`seed.php` carga productos iniciales desde `productos.json` mediante los repositorios MVC.

Los archivos de diagnóstico y compatibilidad ubicados en la raíz están bloqueados por `.htaccess` y se conservarán solo hasta completar la limpieza de instalaciones antiguas.
