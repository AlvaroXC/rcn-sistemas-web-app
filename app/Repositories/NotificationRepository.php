<?php

namespace RcnSistemas\Repositories;

interface NotificationRepository
{
    public function unreadCount(int $userId): int;

    public function latestId(int $userId): int;
    public function recent(int $userId, int $limit = 50): array;
    public function after(int $userId, int $lastId, int $limit = 20): array;
}
