<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Promotion;

interface PromotionManagementRepository extends PromotionRepository
{
    public function save(array $data, int $promotionId = 0): ?Promotion;

    public function delete(int $promotionId): bool;
}
