<?php

 $env = static fn (string $key, mixed $default): mixed => function_exists('getEnvVar') ? getEnvVar($key, $default) : ($_ENV[$key] ?? getenv($key) ?: $default);

return [
    'name' => (string) $env('COMPANY_NAME', 'RCN Sistemas'),
    'phone' => (string) $env('COMPANY_PHONE', '+52 (999) 9942427'),
    'phone_href' => (string) $env('COMPANY_PHONE_HREF', '+529999942427'),
    'email' => (string) $env('COMPANY_EMAIL', 'contacto@rcnsistemas.com'),
    'location' => (string) $env('COMPANY_LOCATION', 'Mérida, Yucatán, México'),
    'facebook_url' => (string) $env('COMPANY_FACEBOOK_URL', 'https://www.facebook.com/profile.php?id=61563445589140'),
    'whatsapp_url' => (string) $env('COMPANY_WHATSAPP_URL', 'https://wa.me/529999942427'),
];
