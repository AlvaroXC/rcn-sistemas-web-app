<?php
require_once '../includes/security.php';
require_once '../includes/db.php';

start_secure_session(false);
revoke_current_admin_remember_me($conn);

$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header("Location: login.php");
exit;
?>
