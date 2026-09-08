<?php

use RcnSistemas\Controllers\Public\HomeController;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliProductRepository;

$application = require __DIR__ . '/app/Bootstrap/app.php';
$connection = $application['database']->get();
(new HomeController(new MysqliProductRepository($connection), new ViewRenderer(__DIR__ . '/app/Views')))->index()->send();
