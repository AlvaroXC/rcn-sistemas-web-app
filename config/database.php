<?php

return [
    'host' => getEnvVar('DB_HOST', 'localhost'),
    'database' => getEnvVar('DB_NAME', 'rcnsiste_axsoluciones'),
    'username' => getEnvVar('DB_USER', 'root'),
    'password' => getEnvVar('DB_PASS', ''),
    'port' => (int) getEnvVar('DB_PORT', 3306),
    'charset' => 'utf8mb4',
];
