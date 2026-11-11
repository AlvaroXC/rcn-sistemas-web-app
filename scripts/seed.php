<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use RcnSistemas\Repositories\MysqliProductRepository;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$source = __DIR__ . '/../productos.json';
$products = json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
$repository = new MysqliProductRepository($application['database']->get());
$created = 0;
foreach ($products as $product) {
    $repository->save([
        'name' => $product['nombre'] ?? '',
        'short_description' => $product['descrip_corta'] ?? '',
        'long_description' => $product['descrip_larga'] ?? '',
        'video_link' => $product['url_video'] ?? '',
        'pdf_path' => $product['url_ficha_tecnica'] ?? '',
        'image_path' => str_replace('src/img/', 'assets/images/', (string) ($product['url_imagen'] ?? '')),
    ], array_map(static fn (array $benefit): array => ['title' => $benefit['titulo'] ?? 'Beneficio', 'description' => $benefit['descripcion'] ?? ''], $product['beneficios'] ?? []));
    $created++;
}
printf("Productos sembrados: %d\n", $created);
