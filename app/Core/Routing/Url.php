<?php

namespace RcnSistemas\Core\Routing;

final class Url
{
    public static function to(string $path = '/'): string
    {
        $basePath = rtrim((string) ($_SERVER['RCN_BASE_PATH'] ?? ''), '/');
        $normalizedPath = '/' . ltrim($path, '/');

        return ($basePath === '' ? '' : $basePath) . ($normalizedPath === '/' ? '/' : $normalizedPath);
    }
}
