<?php

namespace RcnSistemas\Repositories;

interface TicketQueryRepository
{
    public function count(array $filters, int $viewerId, string $viewerRole): int;

    public function list(array $filters, int $viewerId, string $viewerRole, int $limit, int $offset): array;
}
