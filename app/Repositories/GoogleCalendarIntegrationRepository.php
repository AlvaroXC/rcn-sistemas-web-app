<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\GoogleCalendarIntegration;

interface GoogleCalendarIntegrationRepository
{
    public function findShared(): ?GoogleCalendarIntegration;

    public function saveShared(array $data): ?GoogleCalendarIntegration;

    public function markSharedError(string $status, string $error): bool;

    public function disconnectShared(): bool;
}
