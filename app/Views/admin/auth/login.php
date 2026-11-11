<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | RCN Sistemas</title>
    <link rel="stylesheet" href="<?php echo \RcnSistemas\Core\Routing\Url::to('/css/styles.css'); ?>">
    <style>body { background: var(--bg-gray); display: flex; align-items: center; justify-content: center; min-height: 100vh; } .login-card { width: 100%; max-width: 400px; padding: 4rem; }</style>
</head>
<body>
    <div class="card login-card">
        <h1 style="text-align: center; margin-bottom: 4rem; font-size: 3rem;">Admin<span>RCN</span></h1>
        <?php if ($error): ?><div class="alert alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form action="<?php echo \RcnSistemas\Core\Routing\Url::to('/admin/login'); ?>" method="post" style="display: grid; gap: 2rem;">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div style="display: grid; gap: 0.8rem;"><label style="font-weight: 700; font-size: 1.3rem;">Usuario</label><input type="text" name="username" required autofocus style="padding: 1.2rem; border: 1px solid var(--border); border-radius: 8px;"></div>
            <div style="display: grid; gap: 0.8rem;"><label style="font-weight: 700; font-size: 1.3rem;">Contraseña</label><input type="password" name="password" required style="padding: 1.2rem; border: 1px solid var(--border); border-radius: 8px;"></div>
            <label style="display: flex; gap: 0.8rem; align-items: center; font-size: 1.2rem;"><input type="checkbox" name="remember_me" value="1"> Mantener sesión iniciada</label>
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Entrar al Panel</button>
        </form>
        <p style="text-align: center; margin-top: 3rem; font-size: 1.3rem; color: var(--text-muted);">&copy; <?php echo date('Y'); ?> RCN Sistemas</p>
    </div>
</body>
</html>
