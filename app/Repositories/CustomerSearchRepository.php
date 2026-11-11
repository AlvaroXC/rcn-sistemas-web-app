<?php

namespace RcnSistemas\Repositories;

interface CustomerSearchRepository
{
    public function search(string $query, int $limit = 5): array;
}
