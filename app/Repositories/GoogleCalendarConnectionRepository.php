<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\GoogleCalendarConnection;

interface GoogleCalendarConnectionRepository
{
    public function findByUserId(int $userId): ?GoogleCalendarConnection;
    public function save(array $data): ?GoogleCalendarConnection;
    public function markError(int $userId, string $status, string $error): bool;
    public function disconnect(int $userId): bool;
}
