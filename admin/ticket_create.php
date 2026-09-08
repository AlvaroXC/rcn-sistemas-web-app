<?php

use RcnSistemas\Controllers\Admin\TicketController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Repositories\MysqliCustomerSearchRepository;
use RcnSistemas\Repositories\MysqliAdminUserManagementRepository;
use RcnSistemas\Services\Communications\UploadService;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$connection = $application['database']->get();
(new TicketController(new MysqliTicketRepository($connection), new MysqliCustomerRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views'), null, new MysqliCustomerSearchRepository($connection), new UploadService(), __DIR__ . '/../uploads/tickets', new MysqliAdminUserManagementRepository($connection)))->create(Request::fromGlobals(), $session->userId(), $session->role())->send();
