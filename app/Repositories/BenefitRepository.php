<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Benefit;

interface BenefitRepository
{
    /** @return list<Benefit> */
    public function forProduct(int $productId): array;
}
