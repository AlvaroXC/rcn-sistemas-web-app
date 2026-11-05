<?php

namespace Tests\Unit\Services\Google;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Google\GoogleCalendarService;
use RcnSistemas\Services\Google\TokenEncryption;

final class GoogleCalendarServiceTest extends TestCase
{
    public function test_it_generates_an_offline_authorization_url_with_state(): void
    {
        $service = new GoogleCalendarService([
            'client_id' => 'client-id', 'client_secret' => 'client-secret',
            'redirect_uri' => 'http://localhost/callback', 'scopes' => ['openid'],
        ], new TokenEncryption('test-secret'));

        $url = $service->authorizationUrl('state-value');

        self::assertStringContainsString('state=state-value', $url);
        self::assertStringContainsString('access_type=offline', $url);
    }
}
