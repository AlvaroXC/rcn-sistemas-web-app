<?php

namespace RcnSistemas\Core\Routing;

final class Url
{
    public static function to(string $path = '/'): string
    {
        $configuredUrl = function_exists('getEnvVar') ? (string) getEnvVar('APP_URL', '') : '';
        $configuredPath = parse_url($configuredUrl, PHP_URL_PATH);
        $basePath = rtrim((string) ($configuredPath ?: ($_SERVER['RCN_BASE_PATH'] ?? '')), '/');
        $normalizedPath = '/' . ltrim($path, '/');

        return ($basePath === '' ? '' : $basePath) . ($normalizedPath === '/' ? '/' : $normalizedPath);
    }
}
