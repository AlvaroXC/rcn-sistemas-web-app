<?php

namespace Tests\Unit\Core\View;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\View\AssetManager;

final class AssetManagerTest extends TestCase
{
    public function test_it_builds_a_versioned_asset_url(): void
    {
        $assets = new AssetManager('rcn', '2026.09.07');

        self::assertSame('/rcn/css/public.css?v=2026.09.07', $assets->url('css/public.css'));
    }
}
