<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password, display_name, active FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if ((int) ($user['active'] ?? 1) === 1 && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_user'] = trim((string) ($user['display_name'] ?? '')) !== '' ? $user['display_name'] : $user['username'];
            header("Location: index.php");
            exit;
        }
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
        
        <?php if($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; font-size: 1.4rem;">
                <?php echo $error; ?>
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
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Entrar al Panel</button>
        </form>
        <p style="text-align: center; margin-top: 3rem; font-size: 1.3rem; color: var(--text-muted);">
            &copy; <?php echo date('Y'); ?> RCN Sistemas
        </p>
    </div>
</body>
</html>
