<?php
use RcnSistemas\Controllers\Admin\CustomerController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerLicenseRepository;
use RcnSistemas\Repositories\MysqliCustomerQueryRepository;
use RcnSistemas\Repositories\MysqliCustomerRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login')); exit; }
$connection = $application['database']->get();
(new CustomerController(new MysqliCustomerRepository($connection), new MysqliCustomerQueryRepository($connection), new MysqliCustomerLicenseRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views')))->show(Request::fromGlobals())->send();
