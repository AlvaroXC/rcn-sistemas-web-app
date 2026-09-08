<?php

use RcnSistemas\Controllers\Admin\ProductController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliProductRepository;
use RcnSistemas\Services\ProductService;
use RcnSistemas\Services\Communications\UploadService;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager(); $session->start();
if (!$session->isAuthenticated()) { header('Location: login.php'); exit; }
$csrf = new CsrfService($session); $csrf->token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') $csrf->validate($_POST['_csrf_token'] ?? null);
$connection = $application['database']->get();
$repository = new MysqliProductRepository($connection);
(new ProductController($repository, new ProductService($repository), new ViewRenderer(__DIR__ . '/../app/Views'), new UploadService(), __DIR__ . '/../uploads/images', __DIR__ . '/../uploads/pdfs'))->index(Request::fromGlobals())->send();
