<?php

$application = require __DIR__ . '/../app/Bootstrap/app.php';

use RcnSistemas\Controllers\Admin\PromotionController;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliPromotionManagementRepository;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Services\PromotionManagementService;
use RcnSistemas\Services\PromotionService;

$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$conn = $application['database']->get();

$views = new ViewRenderer(__DIR__ . '/../app/Views');
$repository = new MysqliPromotionManagementRepository($conn);
$controller = new PromotionController(
    $repository,
    new PromotionManagementService($repository, new PromotionService()),
    new UploadService(),
    $views
);
$controller->index(Request::fromGlobals())->send();
