<?php

namespace Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\Config;

final class ConfigTest extends TestCase
{
    public function test_it_reads_nested_configuration_values(): void
    {
        $config = new Config([
            'app' => [
                'name' => 'RCN Sistemas',
            ],
        ]);

        self::assertSame('RCN Sistemas', $config->get('app.name'));
    }

    public function test_it_returns_a_default_for_an_unknown_value(): void
    {
        $config = new Config();

        self::assertSame('default', $config->get('app.name', 'default'));
    }

    public function test_it_throws_when_a_required_value_is_missing(): void
    {
        $config = new Config();

        $this->expectException(InvalidArgumentException::class);

        $config->require('database.host');
    }
}
