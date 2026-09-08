<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Bootstrap/app.php';
start_secure_session(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf_or_fail();

use RcnSistemas\Controllers\Admin\CustomerController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerRepository;

$controller = new CustomerController(new MysqliCustomerRepository($conn), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals())->send();
