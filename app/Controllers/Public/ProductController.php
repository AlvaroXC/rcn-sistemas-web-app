<?php

namespace RcnSistemas\Controllers\Public;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Repositories\ProductRepository;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        \RcnSistemas\Core\View\ViewRenderer $views
    ) {
        parent::__construct($views);
    }

    public function index(): Response
    {
        return $this->view('public.products.index', [
            'pageTitle' => 'Productos',
            'products' => $this->products->all(),
        ], 'layouts.public');
    }

    public function show(int $productId): Response
    {
        $product = $this->products->find($productId);
        if (!$product) {
            return $this->view('errors.404', [], 'layouts.public', 404);
        }

        return $this->view('public.products.show', [
            'pageTitle' => $product->name,
            'product' => $product,
        ], 'layouts.public');
    }
}
