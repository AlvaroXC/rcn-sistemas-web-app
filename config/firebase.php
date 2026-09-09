<?php

return [
    'enabled' => filter_var(getEnvVar('FIREBASE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'project_id' => trim((string) getEnvVar('FIREBASE_PROJECT_ID', '')),
    'api_key' => trim((string) getEnvVar('FIREBASE_API_KEY', '')),
    'auth_domain' => trim((string) getEnvVar('FIREBASE_AUTH_DOMAIN', '')),
    'messaging_sender_id' => trim((string) getEnvVar('FIREBASE_MESSAGING_SENDER_ID', '')),
    'app_id' => trim((string) getEnvVar('FIREBASE_APP_ID', '')),
    'vapid_public_key' => trim((string) getEnvVar('FIREBASE_VAPID_PUBLIC_KEY', '')),
    'service_account_path' => ($rawPath = trim((string) getEnvVar('FIREBASE_SERVICE_ACCOUNT_PATH', ''))) !== '' && !is_file($rawPath) && is_file(dirname(__DIR__) . '/' . ltrim($rawPath, '/')) ? dirname(__DIR__) . '/' . ltrim($rawPath, '/') : $rawPath,
    'timeout_seconds' => max(1, min(15, (int) getEnvVar('FIREBASE_TIMEOUT_SECONDS', 5))),
    'web_config' => [
        'apiKey' => trim((string) getEnvVar('FIREBASE_API_KEY', '')),
        'authDomain' => trim((string) getEnvVar('FIREBASE_AUTH_DOMAIN', '')),
        'projectId' => trim((string) getEnvVar('FIREBASE_PROJECT_ID', '')),
        'messagingSenderId' => trim((string) getEnvVar('FIREBASE_MESSAGING_SENDER_ID', '')),
        'appId' => trim((string) getEnvVar('FIREBASE_APP_ID', '')),
    ],
];
