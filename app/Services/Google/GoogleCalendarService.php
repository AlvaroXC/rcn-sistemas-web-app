<?php

namespace RcnSistemas\Services\Google;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Oauth2;
use Google\Service\Exception as GoogleServiceException;
use InvalidArgumentException;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Models\GoogleCalendarIntegration;

final class GoogleCalendarService implements GoogleCalendarGateway
{
    public function __construct(
        private readonly array $config,
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

    /** @return array{connection: array<string, mixed>, refresh_token: string} */
    public function exchangeCode(string $code): array
    {
        $client = $this->newClient();
        $tokens = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($tokens['error'])) throw new InvalidArgumentException('Google no pudo autorizar esta cuenta.');
        if (empty($tokens['refresh_token'])) throw new GoogleCalendarAuthorizationException('Google no entregó un token de renovación. Revoca el acceso anterior y vuelve a autorizar la integración.');
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

    public function createEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, array $data, ?string $eventId = null): Event
    {
        $calendar = $this->calendar($connection);
        $event = $this->event($data);
        if ($eventId !== null) $event->setId($eventId);
        try {
            return $calendar->events->insert($connection->calendarId, $event, ['sendUpdates' => 'none']);
        } catch (GoogleServiceException $exception) {
            if ($eventId !== null && (int) $exception->getCode() === 409) {
                return $this->updateEvent($connection, $eventId, $data);
            }
            throw $exception;
        }
    }

    public function updateEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId, array $data): Event
    {
        return $this->calendar($connection)->events->update($connection->calendarId, $eventId, $this->event($data), ['sendUpdates' => 'none']);
    }

    public function deleteEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId): void
    {
        $this->calendar($connection)->events->delete($connection->calendarId, $eventId, ['sendUpdates' => 'none']);
    }

    public function revoke(GoogleCalendarConnection|GoogleCalendarIntegration $connection): void
    {
        $refreshToken = $this->encryption->decrypt($connection->refreshTokenEncrypted);
        if ($refreshToken) $this->newClient()->revokeToken($refreshToken);
    }

    private function calendar(GoogleCalendarConnection|GoogleCalendarIntegration $connection): Calendar
    {
        $client = $this->newClient();
        try {
            $refreshToken = $this->encryption->decrypt($connection->refreshTokenEncrypted);
        } catch (\Throwable) {
            throw new GoogleCalendarConfigurationException('No se pudo descifrar la conexión. Revisa APP_ENCRYPTION_KEY o vuelve a autorizar Google.');
        }
        if (!$refreshToken) throw new GoogleCalendarAuthorizationException();
        $tokens = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (!empty($tokens['error'])) {
            if ($tokens['error'] === 'invalid_grant') throw new GoogleCalendarAuthorizationException();
            if ($tokens['error'] === 'invalid_client') throw new GoogleCalendarConfigurationException('Las credenciales OAuth de Google no son válidas.');
            throw new InvalidArgumentException('Google no pudo renovar el acceso al calendario.');
        }
        if (!$tokens || empty($tokens['access_token'])) throw new GoogleCalendarAuthorizationException();
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
        $redirectUri = (string) ($this->config['redirect_uri'] ?? '');
        if (trim((string) ($this->config['client_id'] ?? '')) === '' || trim((string) ($this->config['client_secret'] ?? '')) === '' || !preg_match('#^https?://#i', $redirectUri)) {
            throw new GoogleCalendarConfigurationException('Google Calendar no está configurado: agrega GOOGLE_CALENDAR_CLIENT_ID, GOOGLE_CALENDAR_CLIENT_SECRET y GOOGLE_CALENDAR_REDIRECT_URI (dirección completa) al archivo .env.');
        }
        $client = new Client();
        $client->setClientId((string) ($this->config['client_id'] ?? ''));
        $client->setClientSecret((string) ($this->config['client_secret'] ?? ''));
        $client->setRedirectUri((string) ($this->config['redirect_uri'] ?? ''));
        $client->setScopes((array) ($this->config['scopes'] ?? []));
        return $client;
    }
}
