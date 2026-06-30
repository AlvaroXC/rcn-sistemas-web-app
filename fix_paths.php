<?php
/**
 * Utility script to fix product image and PDF paths
 * This script removes the "../" prefix from paths that were likely added by an incorrect seeder
 */

require_once 'includes/db.php';

echo "<h1>Actualizando rutas de productos...</h1>";

$count = 0;

// Update image_path
$sql_img = "UPDATE products SET image_path = REPLACE(image_path, '../', '') WHERE image_path LIKE '../%'";
if ($conn->query($sql_img)) {
    echo "<p>Imágenes actualizadas: " . $conn->affected_rows . "</p>";
    $count += $conn->affected_rows;
} else {
    echo "<p style='color:red'>Error actualizando imágenes: " . $conn->error . "</p>";
}

// Update pdf_path
$sql_pdf = "UPDATE products SET pdf_path = REPLACE(pdf_path, '../', '') WHERE pdf_path LIKE '../%'";
if ($conn->query($sql_pdf)) {
    echo "<p>PDFs actualizados: " . $conn->affected_rows . "</p>";
    $count += $conn->affected_rows;
} else {
    echo "<p style='color:red'>Error actualizando PDFs: " . $conn->error . "</p>";
}

echo "<h2>Proceso terminado. Total de cambios: $count</h2>";
echo "<a href='productos.php'>Ir a ver los productos</a>";
?>
