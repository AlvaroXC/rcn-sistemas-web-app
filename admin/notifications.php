<?php

use RcnSistemas\Controllers\Admin\NotificationController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliNotificationRepository;
use RcnSistemas\Repositories\MysqliPushDeviceRepository;
use RcnSistemas\Repositories\MysqliPushPreferenceRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login')); exit; }
$connection = $application['database']->get();
$controller = new NotificationController(new MysqliNotificationRepository($connection), new MysqliPushDeviceRepository($connection), new MysqliPushPreferenceRepository($connection), $application['config']->get('firebase', []), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals(), $session->userId())->send();
