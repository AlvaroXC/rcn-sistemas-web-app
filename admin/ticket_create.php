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
use RcnSistemas\Repositories\MysqliGoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\MysqliTicketCalendarRepository;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Services\Google\GoogleCalendarAppointmentSyncService;
use RcnSistemas\Services\Google\GoogleCalendarService;
use RcnSistemas\Services\Google\TokenEncryption;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login')); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$connection = $application['database']->get();
$pushDevices = new \RcnSistemas\Repositories\MysqliPushDeviceRepository($connection);
$pushPreferences = new \RcnSistemas\Repositories\MysqliPushPreferenceRepository($connection);
$pushDelivery = new \RcnSistemas\Services\Notifications\PushDeliveryService($pushDevices, $pushPreferences, new \RcnSistemas\Services\Notifications\FcmClient($application['config']->get('firebase', [])));
$ticketNotifications = new \RcnSistemas\Services\Notifications\TicketNotificationDispatcher($connection, $pushDelivery, (string) $application['config']->get('app.url', ''));
$calendarConnections = new MysqliGoogleCalendarConnectionRepository($connection);
$calendarSync = new GoogleCalendarAppointmentSyncService(
    $calendarConnections,
    new MysqliTicketCalendarRepository($connection),
    new GoogleCalendarService(
        $application['config']->get('google', []),
        $calendarConnections,
        new TokenEncryption((string) $application['config']->get('app.encryption_key', ''))
    ),
    $application['config']->get('google', [])
);
(new TicketController(new MysqliTicketRepository($connection), new MysqliCustomerRepository($connection), new ViewRenderer(__DIR__ . '/../app/Views'), null, new MysqliCustomerSearchRepository($connection), new UploadService(), __DIR__ . '/../uploads/tickets', new MysqliAdminUserManagementRepository($connection), $calendarSync, $ticketNotifications))->create(Request::fromGlobals(), $session->userId(), $session->role())->send();
