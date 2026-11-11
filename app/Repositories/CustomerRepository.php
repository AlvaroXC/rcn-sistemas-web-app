<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Customer;

interface CustomerRepository
{
    public function findByPhone(string $phone): ?Customer;

    public function find(int $customerId): ?Customer;

    public function findByRfc(string $rfc): ?Customer;

    public function save(array $data): ?Customer;
}
