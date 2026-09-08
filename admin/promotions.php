<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';

use RcnSistemas\Controllers\Admin\PromotionController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliPromotionManagementRepository;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Services\PromotionManagementService;
use RcnSistemas\Services\PromotionService;

start_secure_session(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
}

$views = new ViewRenderer(__DIR__ . '/../app/Views');
$repository = new MysqliPromotionManagementRepository($conn);
$controller = new PromotionController(
    $repository,
    new PromotionManagementService($repository, new PromotionService()),
    new UploadService(),
    $views
);
$controller->index(Request::fromGlobals())->send();
