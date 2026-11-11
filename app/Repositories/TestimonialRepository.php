<?php

namespace RcnSistemas\Repositories;

use RcnSistemas\Models\Testimonial;

interface TestimonialRepository
{
    /** @return list<Testimonial> */
    public function all(): array;

    public function create(array $data): ?Testimonial;
}
