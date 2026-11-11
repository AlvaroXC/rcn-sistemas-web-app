<?php

namespace RcnSistemas\Repositories;

use mysqli;

interface AdminUserRepository
{
    public function findByUsername(string $username): ?array;

    public function findById(int $userId): ?array;
}
