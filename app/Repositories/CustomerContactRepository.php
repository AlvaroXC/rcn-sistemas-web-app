<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\CustomerContact;

interface CustomerContactRepository
{
    public function find(int $contactId): ?CustomerContact;

    /** @return list<CustomerContact> ordenados por nombre */
    public function forCustomer(int $customerId): array;

    public function save(array $data): ?CustomerContact;

    public function delete(int $contactId): bool;
}
