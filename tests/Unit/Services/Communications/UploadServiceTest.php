<?php

namespace Tests\Unit\Services\Communications;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Communications\UploadService;

final class UploadServiceTest extends TestCase
{
    public function test_it_rejects_an_unapproved_extension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new UploadService())->validate(['error' => UPLOAD_ERR_OK, 'size' => 20, 'name' => 'file.exe'], [
            'extensions' => ['jpg'], 'max_size' => 100,
        ]);
    }

    public function test_it_generates_a_random_safe_name(): void
    {
        $name = (new UploadService())->safeName('../../image.png');
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $name);
    }
}
