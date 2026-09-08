<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$connection = $application['database']->get();
$connection->query(
    'CREATE TABLE IF NOT EXISTS app_migrations ('
    . 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, '
    . 'migration VARCHAR(255) NOT NULL UNIQUE, '
    . 'executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($migrationFiles, SORT_STRING);
$executed = [];
$result = $connection->query('SELECT migration FROM app_migrations');
while ($result && $row = $result->fetch_assoc()) {
    $executed[$row['migration']] = true;
}

foreach ($migrationFiles as $migrationFile) {
    $migration = basename($migrationFile);
    if (isset($executed[$migration])) {
        continue;
    }

    $sql = file_get_contents($migrationFile);
    if ($sql === false || !$connection->multi_query($sql)) {
        throw new RuntimeException('No se pudo ejecutar la migración: ' . $migration);
    }
    while ($connection->more_results() && $connection->next_result()) {
    }

    $statement = $connection->prepare('INSERT INTO app_migrations (migration) VALUES (?)');
    $statement->bind_param('s', $migration);
    $statement->execute();
    echo "Migración ejecutada: {$migration}" . PHP_EOL;
}

echo "Migraciones al día." . PHP_EOL;
