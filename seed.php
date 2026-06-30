<?php
require_once 'includes/db.php';

$json = file_get_contents('./productos.json');
$products = json_decode($json, true);

foreach ($products as $p) {
    // Insert Product
    $stmt = $conn->prepare("INSERT INTO products (name, short_description, long_description, video_link, pdf_path, image_path) VALUES (?, ?, ?, ?, ?, ?)");
    
    $name = $p['nombre'];
    $short = $p['descrip_corta'];
    $long = $p['descrip_larga'];
    $video = $p['url_video'];
    $pdf = $p['url_ficha_tecnica'];
    // Adjust image path (currently src/img/...) to match new structure or use full path
    $img = "../" . $p['url_imagen']; 
    
    $stmt->bind_param("ssssss", $name, $short, $long, $video, $pdf, $img);
    $stmt->execute();
    
    $productId = $conn->insert_id;
    
    // Insert Benefits
    if (isset($p['beneficios'])) {
        foreach ($p['beneficios'] as $b) {
            $b_stmt = $conn->prepare("INSERT INTO benefits (product_id, title, description) VALUES (?, ?, ?)");
            $title = $b['titulo'] ?? "Beneficio";
            $desc = $b['descripcion'];
            $b_stmt->bind_param("iss", $productId, $title, $desc);
            $b_stmt->execute();
        }
    }
}

echo "Database seeded successfully with " . count($products) . " products.";
?>
