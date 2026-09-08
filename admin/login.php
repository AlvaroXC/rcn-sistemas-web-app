<?php

use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Repositories\MysqliAdminUserRepository;
use RcnSistemas\Services\AuthService;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$session = new SessionManager();
$session->start();
if ($session->isAuthenticated()) { header('Location: index.php'); exit; }

$csrf = new CsrfService($session);
$csrf->token();
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->validate($_POST['_csrf_token'] ?? null);
        $auth = new AuthService(new MysqliAdminUserRepository($application['database']->get()), $session);
        if ($auth->authenticate(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) { header('Location: index.php'); exit; }
        $error = 'Usuario o contraseña incorrectos.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
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
        <?php if ($error): ?><div class="alert alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form action="login.php" method="post" style="display: grid; gap: 2rem;">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div style="display: grid; gap: 0.8rem;"><label style="font-weight: 700; font-size: 1.3rem;">Usuario</label><input type="text" name="username" required autofocus style="padding: 1.2rem; border: 1px solid var(--border); border-radius: 8px;"></div>
            <div style="display: grid; gap: 0.8rem;"><label style="font-weight: 700; font-size: 1.3rem;">Contraseña</label><input type="password" name="password" required style="padding: 1.2rem; border: 1px solid var(--border); border-radius: 8px;"></div>
            <label style="display: inline-flex; align-items: center; gap: 1rem; font-size: 1.4rem; color: var(--text-dark);"><input type="checkbox" name="remember_me" value="1" style="width: 1.6rem; height: 1.6rem;">Mantener sesión iniciada</label>
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Entrar al Panel</button>
        </form>
        <p style="text-align: center; margin-top: 3rem; font-size: 1.3rem; color: var(--text-muted);">&copy; <?php echo date('Y'); ?> RCN Sistemas</p>
    </div>
</body>
</html>
