<?php

namespace RcnSistemas\Services;

use RcnSistemas\Models\Promotion;
use RcnSistemas\Repositories\PromotionManagementRepository;

final class PromotionManagementService
{
    public function __construct(
        private readonly PromotionManagementRepository $promotions,
        private readonly PromotionService $validation
    ) {}

    public function save(array $data, int $promotionId = 0): Promotion
    {
        $this->validation->validate($data);
        $promotion = $this->promotions->save($data, $promotionId);
        if (!$promotion) {
            throw new \RuntimeException('No se pudo guardar la promoción.');
        }
        return $promotion;
    }

    public function delete(int $promotionId): bool
    {
        return $promotionId > 0 && $this->promotions->delete($promotionId);
    }
}
