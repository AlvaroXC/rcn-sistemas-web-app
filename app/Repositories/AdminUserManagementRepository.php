<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\AdminUser;

interface AdminUserManagementRepository extends AdminUserRepository
{
    /** @return list<AdminUser> */
    public function all(): array;

    public function save(array $data, int $userId = 0): ?AdminUser;
}
