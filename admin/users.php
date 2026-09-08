<?php

require_once __DIR__ . '/../includes/security.php';
require_admin_role();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Bootstrap/app.php';

use RcnSistemas\Controllers\Admin\UserController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliAdminUserManagementRepository;
use RcnSistemas\Services\AdminUserService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf_or_fail();
$repository = new MysqliAdminUserManagementRepository($conn);
$controller = new UserController($repository, new AdminUserService($repository), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals())->send();
