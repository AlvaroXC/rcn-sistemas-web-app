<?php

use RcnSistemas\Controllers\Admin\TicketController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Repositories\MysqliTicketDetailRepository;
use RcnSistemas\Repositories\MysqliAdminUserManagementRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$connection = $application['database']->get();
$tickets = new MysqliTicketRepository($connection);
(new TicketController($tickets, new MysqliCustomerRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views'), new MysqliTicketDetailRepository($connection), null, null, '', new MysqliAdminUserManagementRepository($connection)))->show(Request::fromGlobals(), $session->userId(), $session->role())->send();
