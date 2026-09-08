<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Bootstrap/app.php';
start_secure_session(true);

use RcnSistemas\Controllers\Admin\TicketListController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliTicketQueryRepository;
use RcnSistemas\Services\Tickets\TicketListService;

$controller = new TicketListController(new MysqliTicketQueryRepository($conn), new TicketListService(), new ViewRenderer(__DIR__ . '/../app/Views'));
$controller->index(Request::fromGlobals(), current_admin_id(), current_admin_role())->send();
