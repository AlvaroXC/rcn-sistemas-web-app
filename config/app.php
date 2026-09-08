<?php

return [
    'name' => getEnvVar('APP_NAME', 'RCN Sistemas'),
    'environment' => getEnvVar('APP_ENV', 'production'),
    'debug' => filter_var(getEnvVar('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) getEnvVar('APP_URL', ''), '/'),
    'asset_version' => getEnvVar('APP_ASSET_VERSION', '2026.06.27.1'),
    'encryption_key' => getEnvVar('APP_ENCRYPTION_KEY', ''),
];
