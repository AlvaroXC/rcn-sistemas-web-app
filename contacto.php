<?php

use RcnSistemas\Controllers\Public\ContactController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliCustomerRepository;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Repositories\MysqliProductRepository;
use RcnSistemas\Repositories\MysqliPromotionRepository;
use RcnSistemas\Services\Communications\ContactService;
use RcnSistemas\Services\Communications\MailService;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Tickets\TicketService;
use RcnSistemas\Services\Tickets\TicketWorkflow;

$application = require __DIR__ . '/app/Bootstrap/app.php';
$session = new SessionManager();
$session->start();
$connection = $application['database']->get();
$customers = new MysqliCustomerRepository($connection);
$contact = new ContactService(new CustomerService($customers, new CustomerIdentity()), new TicketService(new MysqliTicketRepository($connection), new TicketWorkflow()), new UploadService(), new MailService($application['config']->get('mail', [])));
$uploadDirectory = __DIR__ . '/uploads/tickets';
$products = new MysqliProductRepository($connection);
$promotions = new MysqliPromotionRepository($connection);
(new ContactController($contact, new CsrfService($session), new UploadService(), $uploadDirectory, new ViewRenderer(__DIR__ . '/app/Views'), $products, $promotions))->index(Request::fromGlobals())->send();
