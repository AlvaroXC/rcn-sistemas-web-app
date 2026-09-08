<?php

use RcnSistemas\Controllers\Public\ProductController;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliProductRepository;

$application = require __DIR__ . '/app/Bootstrap/app.php';
$views = new ViewRenderer(__DIR__ . '/app/Views');
$products = new MysqliProductRepository($application['database']->get());
$response = (new ProductController($products, $views))->show((int) ($_GET['id'] ?? 0));
$response->send();
