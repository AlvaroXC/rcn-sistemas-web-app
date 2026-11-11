<?php

use RcnSistemas\Controllers\Admin\DashboardController;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliDashboardMetricsRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login')); exit; }
$connection = $application['database']->get();
(new DashboardController(new MysqliDashboardMetricsRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views')))->index($session->userId(), $session->role())->send();
