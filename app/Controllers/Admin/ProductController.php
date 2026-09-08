<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\ProductRepository;
use RcnSistemas\Services\ProductService;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductService $productService,
        ViewRenderer $views
    ) {
        parent::__construct($views);
    }

    public function index(): Response
    {
        return $this->view('admin.products.index', [
            'pageTitle' => 'Productos',
            'products' => $this->products->all(),
        ], 'layouts.admin');
    }

    public function save(Request $request): Response
    {
        try {
            $data = [
                'name' => $request->input('name', ''),
                'short_description' => $request->input('short_description', ''),
                'long_description' => $request->input('long_description', ''),
                'video_link' => $request->input('video_link', ''),
                'pdf_path' => $request->input('pdf_path', ''),
                'image_path' => $request->input('image_path', ''),
            ];
            $product = $this->productService->save($data);

            return Response::redirect('/admin/products.php?id=' . $product->id);
        } catch (InvalidArgumentException $exception) {
            return $this->view('admin.products.form', [
                'pageTitle' => 'Nuevo producto',
                'error' => $exception->getMessage(),
                'form' => $request,
            ], 'layouts.admin', 422);
        }
    }
}
