<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';

use RcnSistemas\Core\Security\SessionManager;

$session = new SessionManager();
$session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$conn = $application['database']->get();

use RcnSistemas\Controllers\Admin\TicketListController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliTicketQueryRepository;
use RcnSistemas\Services\Tickets\TicketListService;

$controller = new TicketListController(new MysqliTicketQueryRepository($conn), new TicketListService(), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals(), $session->userId(), $session->role())->send();
