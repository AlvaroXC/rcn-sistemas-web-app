<?php
require_once 'includes/db.php';

echo "<h1>Debug de Base de Datos</h1>";

// Check files
echo "<h2>Archivos detectados:</h2>";
echo ".env: " . (file_exists('.env') ? "<span style='color:green'>SI</span>" : "<span style='color:red'>NO</span>") . "<br>";
echo ".env.local: " . (file_exists('.env.local') ? "<span style='color:orange'>SI (¡Ojo! Puede estar sobrescribiendo la configuración de producción)</span>" : "<span style='color:green'>NO</span>") . "<br>";

echo "<h2>Configuración Activa:</h2>";
echo "Base de Datos en uso: <strong>" . getEnvVar('DB_NAME', 'ax_soluciones') . "</strong><br>";
echo "Host: <strong>" . getEnvVar('DB_HOST', 'localhost') . "</strong><br>";

echo "<h2>Resultados de Query:</h2>";
$res = $conn->query("SELECT COUNT(*) FROM products");
if ($res) {
    $count = $res->fetch_row()[0];
    echo "Total de productos en tabla 'products': <strong>$count</strong><br>";
    if ($count == 0) {
        echo "<p style='color:red'>¡Advertencia! La tabla de productos está vacía en esta base de datos.</p>";
    }
} else {
    echo "Error querying products: " . $conn->error . "<br>";
}

$res = getProducts($conn);
if ($res) {
    echo "getProducts() devolvió: <strong>" . $res->num_rows . "</strong> productos.<br>";
} else {
    echo "getProducts() falló.<br>";
}

echo "<br><hr>";
echo "<a href='fix_paths.php'>1. Si ves productos pero las imágenes están rotas, haz clic aquí para corregir rutas.</a><br>";
echo "<a href='productos.php'>2. Ir a la página de productos del cliente.</a>";
?>
