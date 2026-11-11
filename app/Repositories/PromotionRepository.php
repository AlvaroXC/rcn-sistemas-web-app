<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Promotion;

interface PromotionRepository
{
    /** @return list<Promotion> */
    public function all(bool $activeOnly = true): array;

    public function find(int $promotionId, bool $activeOnly = true): ?Promotion;
}
