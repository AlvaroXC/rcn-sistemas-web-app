<?php

namespace RcnSistemas\Core\View;

final class AssetManager
{
    public function __construct(
        private readonly string $basePath = '',
        private readonly string $version = ''
    ) {
    }

    public function url(string $asset): string
    {
        $path = '/' . ltrim($asset, '/');
        $basePath = trim($this->basePath, '/');
        if ($basePath !== '') {
            $path = '/' . $basePath . $path;
        }

        return $path . ($this->version !== '' ? '?v=' . rawurlencode($this->version) : '');
    }
}
