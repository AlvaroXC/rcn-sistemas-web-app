<?php

namespace RcnSistemas\Services\Notifications;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use RuntimeException;

final class FcmClient
{
    public function __construct(private readonly array $config, private readonly ?Client $http = null) {}

    public function enabled(): bool
    {
        return !empty($this->config['enabled'])
            && $this->config['project_id'] !== ''
            && is_file((string) ($this->config['service_account_path'] ?? ''));
    }

    public function send(string $token, array $notification, array $data = []): array
    {
        if (!$this->enabled()) return ['result' => 'disabled'];

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            (string) $this->config['service_account_path']
        );
        $auth = $credentials->fetchAuthToken(HttpHandlerFactory::build());
        $accessToken = (string) ($auth['access_token'] ?? '');
        if ($accessToken === '') throw new RuntimeException('No se pudo autorizar el envío FCM.');

        $client = $this->http ?? new Client(['timeout' => (float) ($this->config['timeout_seconds'] ?? 5)]);
        $link = trim((string) ($data['url'] ?? ''));
        $webpushNotification = [
            'title' => (string) ($notification['title'] ?? 'RCN Sistemas'),
            'body' => (string) ($notification['body'] ?? 'Tienes una actualización de ticket.'),
            'icon' => rtrim((string) ($this->config['app_url'] ?? ''), '/') . '/assets/images/pwa-icon.svg',
        ];
        $response = $client->post('https://fcm.googleapis.com/v1/projects/' . rawurlencode((string) $this->config['project_id']) . '/messages:send', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Content-Type' => 'application/json'],
            'json' => ['message' => [
                'token' => $token,
                'notification' => $notification,
                'data' => array_map('strval', $data),
                'webpush' => [
                    'headers' => ['Urgency' => 'high'],
                    'notification' => $webpushNotification,
                    'fcm_options' => $link !== '' ? ['link' => $link] : [],
                ],
            ]],
        ]);
        $body = json_decode((string) $response->getBody(), true) ?: [];
        return ['result' => 'sent', 'provider_code' => $body['name'] ?? null];
    }
}
