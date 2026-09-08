<?php

namespace RcnSistemas\Services\Google;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Oauth2;
use InvalidArgumentException;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Repositories\GoogleCalendarConnectionRepository;

final class GoogleCalendarService
{
    public function __construct(
        private readonly array $config,
        private readonly GoogleCalendarConnectionRepository $connections,
        private readonly TokenEncryption $encryption
    ) {}

    public function authorizationUrl(string $state): string
    {
        $client = $this->newClient();
        $client->setState($state);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client->createAuthUrl();
    }

    /** @return array{connection: array<string, mixed>, refresh_token: ?string} */
    public function exchangeCode(string $code): array
    {
        $client = $this->newClient();
        $tokens = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($tokens['error'])) throw new InvalidArgumentException('Google no pudo autorizar esta cuenta.');
        $client->setAccessToken($tokens);
        $profile = (new Oauth2($client))->userinfo->get();
        $expiresAt = isset($tokens['expires_in']) ? date('Y-m-d H:i:s', time() + (int) $tokens['expires_in']) : null;
        return [
            'connection' => [
                'google_user_id' => (string) ($profile->getId() ?: ''),
                'google_email' => (string) ($profile->getEmail() ?: ''),
                'access_token_encrypted' => $this->encryption->encrypt((string) ($tokens['access_token'] ?? '')),
                'refresh_token_encrypted' => $this->encryption->encrypt($tokens['refresh_token'] ?? null),
                'token_expires_at' => $expiresAt,
                'calendar_id' => (string) ($this->config['calendar_id'] ?? 'primary'),
                'scopes' => implode(' ', (array) ($this->config['scopes'] ?? [])),
                'status' => 'connected',
            ],
            'refresh_token' => $tokens['refresh_token'] ?? null,
        ];
    }

    public function createEvent(GoogleCalendarConnection $connection, array $data): Event
    {
        return $this->calendar($connection)->events->insert($connection->calendarId, $this->event($data), ['sendUpdates' => 'none']);
    }

    public function updateEvent(GoogleCalendarConnection $connection, string $eventId, array $data): Event
    {
        return $this->calendar($connection)->events->update($connection->calendarId, $eventId, $this->event($data), ['sendUpdates' => 'none']);
    }

    public function deleteEvent(GoogleCalendarConnection $connection, string $eventId): void
    {
        $this->calendar($connection)->events->delete($connection->calendarId, $eventId, ['sendUpdates' => 'none']);
    }

    public function revoke(GoogleCalendarConnection $connection): void
    {
        $refreshToken = $this->encryption->decrypt($connection->refreshTokenEncrypted);
        if ($refreshToken) $this->newClient()->revokeToken($refreshToken);
    }

    private function calendar(GoogleCalendarConnection $connection): Calendar
    {
        $client = $this->newClient();
        $refreshToken = $this->encryption->decrypt($connection->refreshTokenEncrypted);
        if (!$refreshToken) throw new InvalidArgumentException('La conexión de Google requiere reconexión.');
        $tokens = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (!$tokens || empty($tokens['access_token'])) throw new InvalidArgumentException('La conexión de Google requiere reconexión.');
        return new Calendar($client);
    }

    private function event(array $data): Event
    {
        $timezone = (string) ($this->config['timezone'] ?? 'America/Merida');
        $start = new EventDateTime(['dateTime' => $data['start'], 'timeZone' => $timezone]);
        $end = new EventDateTime(['dateTime' => $data['end'], 'timeZone' => $timezone]);
        return new Event([
            'summary' => $data['summary'], 'description' => $data['description'],
            'start' => $start, 'end' => $end, 'visibility' => 'private',
        ]);
    }

    private function newClient(): Client
    {
        $client = new Client();
        $client->setClientId((string) ($this->config['client_id'] ?? ''));
        $client->setClientSecret((string) ($this->config['client_secret'] ?? ''));
        $client->setRedirectUri((string) ($this->config['redirect_uri'] ?? ''));
        $client->setScopes((array) ($this->config['scopes'] ?? []));
        return $client;
    }
}
