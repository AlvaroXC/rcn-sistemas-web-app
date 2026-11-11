<?php

namespace RcnSistemas\Repositories;

interface DashboardMetricsRepository
{
    public function metrics(int $viewerId, string $viewerRole): array;
}
