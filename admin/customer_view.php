<?php
use RcnSistemas\Controllers\Admin\CustomerController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerQueryRepository;
use RcnSistemas\Repositories\MysqliCustomerRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$connection = $application['database']->get();
(new CustomerController(new MysqliCustomerRepository($connection), new MysqliCustomerQueryRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views')))->show(Request::fromGlobals())->send();
