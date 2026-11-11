<?php

namespace RcnSistemas\Services\Google;

use Google\Service\Calendar\Event;
use RcnSistemas\Models\GoogleCalendarConnection;
use RcnSistemas\Models\GoogleCalendarIntegration;

interface GoogleCalendarGateway
{
    public function authorizationUrl(string $state): string;

    /** @return array{connection: array<string, mixed>, refresh_token: string} */
    public function exchangeCode(string $code): array;

    public function createEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, array $data, ?string $eventId = null): Event;

    public function updateEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId, array $data): Event;

    public function deleteEvent(GoogleCalendarConnection|GoogleCalendarIntegration $connection, string $eventId): void;

    public function revoke(GoogleCalendarConnection|GoogleCalendarIntegration $connection): void;
}
