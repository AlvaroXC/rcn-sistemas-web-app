<?php

namespace Tests\Unit\Services\Google;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;
use RcnSistemas\Services\Google\GoogleCalendarService;
use RcnSistemas\Services\Google\TokenEncryption;

final class GoogleCalendarServiceTest extends TestCase
{
    public function test_it_generates_an_offline_authorization_url_with_state(): void
    {
        $service = new GoogleCalendarService([
            'client_id' => 'client-id', 'client_secret' => 'client-secret',
            'redirect_uri' => 'http://localhost/callback', 'scopes' => ['openid'],
        ], new InMemoryGoogleConnectionRepository(), new TokenEncryption('test-secret'));

        $url = $service->authorizationUrl('state-value');

        self::assertStringContainsString('state=state-value', $url);
        self::assertStringContainsString('access_type=offline', $url);
    }
}

final class InMemoryGoogleConnectionRepository implements GoogleCalendarConnectionRepository
{
    public function findByUserId(int $userId): ?GoogleCalendarConnection { return null; }
    public function save(array $data): ?GoogleCalendarConnection { return null; }
    public function markError(int $userId, string $status, string $error): bool { return true; }
    public function disconnect(int $userId): bool { return true; }
}
