<?php

namespace RcnSistemas\Repositories;

interface PushPreferenceRepository
{
    public function get(int $userId): array;
    public function save(int $userId, array $preferences): bool;
    public function enabled(int $userId, string $eventType): bool;
}
