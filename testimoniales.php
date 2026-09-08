<?php

use RcnSistemas\Controllers\Public\TestimonialController;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\MysqliTestimonialRepository;
use RcnSistemas\Services\TestimonialService;

$application = require __DIR__ . '/app/Bootstrap/app.php';
$views = new ViewRenderer(__DIR__ . '/app/Views');
$testimonials = new MysqliTestimonialRepository($application['database']->get());
(new TestimonialController($testimonials, new TestimonialService(), $views))->index(Request::fromGlobals())->send();
