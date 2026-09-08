<?php

use Dotenv\Dotenv;
use RcnSistemas\Core\Config;
use RcnSistemas\Core\Database\Connection;

$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/vendor/autoload.php';

if (!function_exists('getEnvVar')) {
    function getEnvVar(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $environmentValue = getenv($key);
        if ($environmentValue !== false) {
            return $environmentValue;
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }
}

$dotenv = Dotenv::createImmutable($projectRoot, ['.env.local', '.env']);
$dotenv->safeLoad();

$configuration = [
    'app' => require $projectRoot . '/config/app.php',
    'database' => require $projectRoot . '/config/database.php',
    'mail' => require $projectRoot . '/config/mail.php',
    'uploads' => require $projectRoot . '/config/uploads.php',
];

return [
    'root' => $projectRoot,
    'config' => new Config($configuration),
    'database' => new Connection($configuration['database']),
];
