<?php

use RcnSistemas\Controllers\Public\PromotionController;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliPromotionRepository;

$application = require __DIR__ . '/app/Bootstrap/app.php';
$views = new ViewRenderer(__DIR__ . '/app/Views');
$promotions = new MysqliPromotionRepository($application['database']->get());
(new PromotionController($promotions, $views))->show((int) ($_GET['id'] ?? 0))->send();
