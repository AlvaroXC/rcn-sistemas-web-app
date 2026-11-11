<?php

namespace RcnSistemas\Core\Database;

use mysqli;
use RuntimeException;

final class Connection
{
    private ?mysqli $connection = null;

    public function __construct(private readonly array $configuration)
    {
    }

    public function get(bool $selectDatabase = true): mysqli
    {
        if ($this->connection instanceof mysqli) {
            return $this->connection;
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $connection = new mysqli(
            (string) ($this->configuration['host'] ?? 'localhost'),
            (string) ($this->configuration['username'] ?? 'root'),
            (string) ($this->configuration['password'] ?? ''),
            $selectDatabase ? (string) ($this->configuration['database'] ?? '') : '',
            (int) ($this->configuration['port'] ?? 3306)
        );

        if ($connection->connect_error) {
            error_log('Database Connection Error: ' . $connection->connect_error);
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }

        $charset = (string) ($this->configuration['charset'] ?? 'utf8mb4');
        if (!$connection->set_charset($charset)) {
            error_log('Database Charset Error: ' . $connection->error);
            throw new RuntimeException('No se pudo configurar la conexión con la base de datos.');
        }

        $this->connection = $connection;

        return $this->connection;
    }
}
