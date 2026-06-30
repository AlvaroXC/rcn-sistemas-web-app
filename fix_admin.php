<?php
require_once 'includes/db.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

// Check if user exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Update existing
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hash, $username);
    $action = "actualizada";
} else {
    // Create new
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hash);
    $action = "creada";
}

if ($stmt->execute()) {
    echo "<h1>Éxito</h1>";
    echo "<p>La cuenta de administrador ha sido $action con éxito.</p>";
    echo "<p><strong>Usuario:</strong> $username</p>";
    echo "<p><strong>Contraseña:</strong> $password</p>";
    echo "<br><a href='admin/login.php'>Ir al Login</a>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Hubo un problema: " . $conn->error . "</p>";
}
?>
