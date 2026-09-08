<?php
require_once '../includes/security.php';
require_once '../includes/db.php';
require_once '../app/Bootstrap/app.php';

use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\MysqliAdminUserRepository;
use RcnSistemas\Services\AuthService;

$session = new SessionManager(ADMIN_SESSION_TIMEOUT);
$session->start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = false;
$loginFlash = get_flash_message('login_notice');
$loginNotice = trim((string) ($loginFlash['message'] ?? ''));
$loginNoticeType = trim((string) ($loginFlash['type'] ?? 'error'));
if (!in_array($loginNoticeType, ['success', 'error'], true)) {
    $loginNoticeType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';

    $auth = new AuthService(new MysqliAdminUserRepository($conn), $session);
    if ($auth->authenticate($username, $password)) {
        $userId = $session->userId();
        if ($rememberMe) {
            issue_admin_remember_me_token($conn, $userId);
        } else {
            revoke_current_admin_remember_me($conn);
        }
        header('Location: index.php');
        exit;
    }
    $error = "Usuario o contraseña incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | RCN Sistemas</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body { background: var(--bg-gray); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 4rem; }
    </style>
</head>
<body>
    <div class="card login-card">
        <h1 style="text-align: center; margin-bottom: 4rem; font-size: 3rem;">Admin<span>RCN</span></h1>

        <?php if($loginNotice !== ''): ?>
            <div class="alert alert--<?php echo e($loginNoticeType); ?>">
                <?php echo e($loginNotice); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert--error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" style="display: grid; gap: 2rem;">
            <div style="display: grid; gap: 0.8rem;">
                <label style="font-weight: 700; font-size: 1.3rem;">Usuario</label>
                <input type="text" name="username" required style="padding: 1.2rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div style="display: grid; gap: 0.8rem;">
                <label style="font-weight: 700; font-size: 1.3rem;">Contraseña</label>
                <input type="password" name="password" required style="padding: 1.2rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <label style="display: inline-flex; align-items: center; gap: 1rem; font-size: 1.4rem; color: var(--text-dark);">
                <input type="checkbox" name="remember_me" value="1" style="width: 1.6rem; height: 1.6rem;">
                Mantener sesión iniciada
            </label>
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Entrar al Panel</button>
        </form>
        <p style="text-align: center; margin-top: 3rem; font-size: 1.3rem; color: var(--text-muted);">
            &copy; <?php echo date('Y'); ?> RCN Sistemas
        </p>
    </div>
</body>
</html>
