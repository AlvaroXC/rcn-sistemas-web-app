<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';

use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;

$session = new SessionManager();
$session->start();
if (!$session->isAuthenticated()) { header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login')); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') (new CsrfService($session))->validate($_POST['_csrf_token'] ?? null);
$conn = $application['database']->get();
(new CsrfService($session))->token();

use RcnSistemas\Controllers\Admin\CustomerController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Repositories\MysqliCustomerLicenseRepository;
use RcnSistemas\Repositories\MysqliCustomerQueryRepository;

$controller = new CustomerController(new MysqliCustomerRepository($conn), new MysqliCustomerQueryRepository($conn), new MysqliCustomerLicenseRepository($conn), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals())->send();
