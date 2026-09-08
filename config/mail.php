<?php

return [
    'host' => getEnvVar('SMTP_HOST', 'localhost'),
    'port' => (int) getEnvVar('SMTP_PORT', 587),
    'username' => getEnvVar('SMTP_USER', ''),
    'password' => getEnvVar('SMTP_PASS', ''),
    'secure' => getEnvVar('SMTP_SECURE', 'tls'),
    'from_address' => getEnvVar('MAIL_FROM_ADDRESS', 'contacto@rcnsistemas.com'),
    'from_name' => getEnvVar('MAIL_FROM_NAME', 'RCN Sistemas'),
    'contact_address' => getEnvVar('CONTACT_ADDRESS', 'contacto@rcnsistemas.com'),
];
