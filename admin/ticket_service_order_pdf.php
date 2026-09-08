<?php

use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Services\Documents\PdfService;
use RcnSistemas\Services\Documents\ServiceOrderDocument;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$ticket = (new MysqliTicketRepository($application['database']->get()))->find((int) ($_GET['id'] ?? 0));
if (!$ticket || ($session->role() !== 'admin' && $ticket->assignedUserId !== $session->userId())) { http_response_code(404); echo 'El ticket solicitado no existe o no está disponible.'; exit; }
(new PdfService())->render((new ServiceOrderDocument())->html($ticket), 'orden-servicio-ticket-' . $ticket->id . '.pdf');
