<?php

use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\MysqliAdminUserRepository;
use RcnSistemas\Repositories\MysqliAdminRememberTokenRepository;
use RcnSistemas\Services\AuthService;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager();
$connection = $application['database']->get();
(new AuthService(new MysqliAdminUserRepository($connection), $session, new MysqliAdminRememberTokenRepository($connection)))->logout();
header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login'));
exit;
