<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';

use RcnSistemas\Controllers\Admin\UserController;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliAdminUserManagementRepository;
use RcnSistemas\Services\AdminUserService;

$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated() || $session->role() !== 'admin') { header('Location: login.php'); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$conn = $application['database']->get();
$repository = new MysqliAdminUserManagementRepository($conn);
$controller = new UserController($repository, new AdminUserService($repository), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals())->send();
