<?php

namespace RcnSistemas\Controllers\Admin;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\DashboardMetricsRepository;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsRepository $metrics, ViewRenderer $views) { parent::__construct($views); }

    public function index(int $userId, string $role): Response
    {
        return $this->view('admin.dashboard.index', ['pageTitle' => 'Panel administrativo', 'metrics' => $this->metrics->metrics($userId, $role)], 'layouts.admin');
    }
}
