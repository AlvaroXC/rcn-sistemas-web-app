<?php

namespace Tests\Integration;

use mysqli;
use PHPUnit\Framework\TestCase;

final class DatabaseConnectivityTest extends TestCase
{
    public function test_the_testing_database_is_available_when_enabled(): void
    {
        if (getenv('RUN_DB_TESTS') !== '1') {
            self::markTestSkipped('Define RUN_DB_TESTS=1 para ejecutar pruebas de integración.');
        }

        $connection = new mysqli((string) getenv('DB_HOST') ?: '127.0.0.1', (string) getenv('DB_USER') ?: 'root', (string) getenv('DB_PASS'), (string) getenv('DB_NAME') ?: 'rcnsistemas_testing', (int) (getenv('DB_PORT') ?: 3306));
        self::assertSame('', $connection->connect_error);
        self::assertTrue((bool) $connection->query('SELECT 1'));
    }
}
