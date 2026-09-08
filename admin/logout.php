<?php

use RcnSistemas\Core\Security\SessionManager;

require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager();
$session->destroy();
header('Location: login.php');
exit;
