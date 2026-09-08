<?php

namespace RcnSistemas\Repositories;

interface CustomerQueryRepository
{
    public function count(string $search = ''): int;

    public function list(string $search = '', int $limit = 25, int $offset = 0): array;

    /** @return list<array<string, mixed>> */
    public function tickets(int $customerId): array;
}
