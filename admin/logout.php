<?php
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once '../app/Bootstrap/app.php';

use RcnSistemas\Core\Security\SessionManager;

revoke_current_admin_remember_me($conn);
$session = new SessionManager(ADMIN_SESSION_TIMEOUT);
$session->destroy();

header("Location: login.php");
exit;
?>
