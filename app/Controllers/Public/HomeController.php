<?php

namespace RcnSistemas\Controllers\Public;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\ProductRepository;

final class HomeController extends Controller
{
    public function __construct(private readonly ProductRepository $products, ViewRenderer $views) { parent::__construct($views); }

    public function index(): Response
    {
        return $this->view('public.home.index', ['pageTitle' => 'Inicio', 'products' => $this->products->all(3)], 'layouts.public');
    }
}
