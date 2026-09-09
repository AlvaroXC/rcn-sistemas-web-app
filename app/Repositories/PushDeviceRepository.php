<?php

namespace RcnSistemas\Repositories;

interface PushDeviceRepository
{
    public function upsert(int $userId, string $token, ?string $deviceKey, string $platform, string $userAgent): bool;
    public function activeForUser(int $userId): array;
    public function deactivateById(int $userId, int $deviceId): bool;
    public function deactivateToken(string $token): bool;
    public function allForUser(int $userId): array;
}
