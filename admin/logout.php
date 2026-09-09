<?php

use RcnSistemas\Core\Security\SessionManager;

require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager();
$session->destroy();
header('Location: ' . \RcnSistemas\Core\Routing\Url::to('/admin/login'));
exit;
