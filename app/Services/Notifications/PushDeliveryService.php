<?php

namespace RcnSistemas\Services\Notifications;

use GuzzleHttp\Exception\RequestException;
use RcnSistemas\Repositories\PushDeviceRepository;
use RcnSistemas\Repositories\PushPreferenceRepository;
use Throwable;

final class PushDeliveryService
{
    public function __construct(private readonly PushDeviceRepository $devices, private readonly PushPreferenceRepository $preferences, private readonly FcmClient $fcm) {}

    public function sendToUser(int $userId, int $ticketId, string $eventType, string $title, string $message, string $url, string $eventKey): array
    {
        if (!$this->fcm->enabled() || !$this->preferences->enabled($userId, $eventType)) return ['sent' => 0, 'skipped' => true];

        $sent = 0; $results = [];
        foreach ($this->devices->activeForUser($userId) as $device) {
            $token = (string) ($device['token'] ?? '');
            if ($token === '') continue;
            try {
                $result = $this->fcm->send($token, ['title' => $title, 'body' => $message], ['type' => $eventType, 'ticket_id' => (string) $ticketId, 'url' => $url]);
                $sent += ($result['result'] ?? '') === 'sent' ? 1 : 0;
                $results[] = ['device_id' => (int) $device['id'], 'result' => $result['result'] ?? 'unknown', 'provider_code' => $result['provider_code'] ?? null];
            } catch (RequestException $exception) {
                $status = $exception->getResponse()?->getStatusCode();
                if ($status === 404 || $status === 400) $this->devices->deactivateToken($token);
                $results[] = ['device_id' => (int) $device['id'], 'result' => $status && $status < 500 ? 'invalid' : 'temporary_error'];
            } catch (Throwable) {
                $results[] = ['device_id' => (int) $device['id'], 'result' => 'error'];
            }
        }
        return ['sent' => $sent, 'skipped' => false, 'results' => $results, 'event_key' => $eventKey];
    }
}
