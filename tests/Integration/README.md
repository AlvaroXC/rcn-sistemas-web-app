# Pruebas de integración

Estas pruebas usan una base exclusiva para pruebas. Configura `.env.testing.example`, ejecuta `php scripts/migrate.php` contra esa base y define `RUN_DB_TESTS=1` antes de ejecutar PHPUnit.

Las pruebas unitarias no requieren MySQL y son las que ejecuta el CI por defecto. `MigrationFlowTest` verifica creación de cliente/ticket y acceso por asignación cuando se habilita la base de pruebas.
