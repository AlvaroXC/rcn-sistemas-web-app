<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';

use RcnSistemas\Core\Security\SessionManager;

$session = new SessionManager();
$session->start();
if (!$session->isAuthenticated()) { RcnSistemas\Core\Http\Response::json(['ok' => false, 'error' => 'No autorizado'], 401)->send(); exit; }
$conn = $application['database']->get();

use RcnSistemas\Controllers\Admin\CustomerSearchController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Repositories\MysqliCustomerSearchRepository;

(new CustomerSearchController(new MysqliCustomerSearchRepository($conn)))->index(Request::fromGlobals())->send();
