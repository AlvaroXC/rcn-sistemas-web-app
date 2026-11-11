<?php

 $env = static fn (string $key, mixed $default): mixed => function_exists('getEnvVar') ? getEnvVar($key, $default) : ($_ENV[$key] ?? getenv($key) ?: $default);

return [
    'iva_rate' => (float) $env('TAX_IVA_RATE', '0.16'),
    'isr_withholding_rate' => (float) $env('TAX_ISR_WITHHOLDING_RATE', '0.0125'),
    'currency' => (string) $env('APP_CURRENCY', 'MXN'),
];
