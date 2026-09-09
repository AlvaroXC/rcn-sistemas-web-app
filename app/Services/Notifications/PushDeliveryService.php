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
        if (!$this->fcm->enabled()) return ['sent' => 0, 'skipped' => true, 'skip_reason' => 'fcm_disabled', 'results' => [['device_id' => 0, 'result' => 'skipped', 'provider_code' => 'fcm_disabled', 'error_message' => 'FCM está deshabilitado o falta la cuenta de servicio.']]];
        if (!$this->preferences->enabled($userId, $eventType)) return ['sent' => 0, 'skipped' => true, 'skip_reason' => 'preference_disabled', 'results' => [['device_id' => 0, 'result' => 'skipped', 'provider_code' => 'preference_disabled', 'error_message' => 'El usuario tiene desactivada esta categoría de notificación.']]];

        $sent = 0; $results = [];
        $devices = $this->devices->activeForUser($userId);
        if (!$devices) return ['sent' => 0, 'skipped' => true, 'skip_reason' => 'no_active_devices', 'results' => [['device_id' => 0, 'result' => 'skipped', 'provider_code' => 'no_active_devices', 'error_message' => 'El usuario no tiene dispositivos activos.']]];
        foreach ($devices as $device) {
            $token = (string) ($device['token'] ?? '');
            if ($token === '') continue;
            try {
                $result = $this->fcm->send($token, ['title' => $title, 'body' => $message], ['type' => $eventType, 'ticket_id' => (string) $ticketId, 'url' => $url]);
                $sent += ($result['result'] ?? '') === 'sent' ? 1 : 0;
                $results[] = ['device_id' => (int) $device['id'], 'result' => $result['result'] ?? 'unknown', 'provider_code' => $result['provider_code'] ?? null, 'error_message' => $result['error_message'] ?? null];
            } catch (RequestException $exception) {
                $status = $exception->getResponse()?->getStatusCode();
                if ($status === 404 || $status === 400) $this->devices->deactivateToken($token);
                $responseBody = $exception->getResponse() ? (string) $exception->getResponse()->getBody() : '';
                $decoded = json_decode($responseBody, true);
                $providerCode = $decoded['error']['status'] ?? ($status ? 'HTTP_' . $status : null);
                $errorMessage = $decoded['error']['message'] ?? $exception->getMessage();
                $results[] = ['device_id' => (int) $device['id'], 'result' => $status && $status < 500 ? 'invalid' : 'temporary_error', 'provider_code' => $providerCode, 'error_message' => substr((string) $errorMessage, 0, 500)];
            } catch (Throwable $exception) {
                $results[] = ['device_id' => (int) $device['id'], 'result' => 'error', 'provider_code' => get_class($exception), 'error_message' => substr($exception->getMessage(), 0, 500)];
            }
        }
        return ['sent' => $sent, 'skipped' => false, 'results' => $results, 'event_key' => $eventKey];
    }
}
