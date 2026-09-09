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
if (!$session->isAuthenticated()) { header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login')); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$connection = $application['database']->get();
$tickets = new MysqliTicketRepository($connection);
$pushDevices = new \RcnSistemas\Repositories\MysqliPushDeviceRepository($connection);
$pushPreferences = new \RcnSistemas\Repositories\MysqliPushPreferenceRepository($connection);
$pushDelivery = new \RcnSistemas\Services\Notifications\PushDeliveryService($pushDevices, $pushPreferences, new \RcnSistemas\Services\Notifications\FcmClient($application['config']->get('firebase', [])));
$ticketNotifications = new \RcnSistemas\Services\Notifications\TicketNotificationDispatcher($connection, $pushDelivery, (string) $application['config']->get('app.url', ''));
(new TicketController($tickets, new MysqliCustomerRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views'), new MysqliTicketDetailRepository($connection), null, null, '', new MysqliAdminUserManagementRepository($connection), null, $ticketNotifications))->show(Request::fromGlobals(), $session->userId(), $session->role())->send();
