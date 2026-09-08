<?php

return [
    'client_id' => getEnvVar('GOOGLE_CALENDAR_CLIENT_ID', ''),
    'client_secret' => getEnvVar('GOOGLE_CALENDAR_CLIENT_SECRET', ''),
    'redirect_uri' => getEnvVar('GOOGLE_CALENDAR_REDIRECT_URI', ''),
    'scopes' => array_values(array_unique(array_filter([...preg_split('/\s+/', trim((string) getEnvVar('GOOGLE_CALENDAR_SCOPES', 'https://www.googleapis.com/auth/calendar.events'))) ?: [], 'openid', 'https://www.googleapis.com/auth/userinfo.email']))),
    'calendar_id' => getEnvVar('GOOGLE_CALENDAR_ID', 'primary'),
    'timezone' => getEnvVar('APP_TIMEZONE', 'America/Merida'),
    'app_url' => rtrim((string) getEnvVar('APP_URL', ''), '/'),
    'default_duration_minutes' => (int) getEnvVar('GOOGLE_CALENDAR_DEFAULT_DURATION_MINUTES', 60),
];
