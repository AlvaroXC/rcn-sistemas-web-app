<?php

namespace Tests\Unit\Services\Google;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Google\GoogleCalendarConfigurationException;
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

    public function test_it_reports_missing_configuration_instead_of_failing_inside_the_google_client(): void
    {
        $service = new GoogleCalendarService(['client_id' => '', 'client_secret' => '', 'redirect_uri' => ''], new TokenEncryption('test-secret'));

        $this->expectException(GoogleCalendarConfigurationException::class);
        $service->authorizationUrl('state-value');
    }
}
