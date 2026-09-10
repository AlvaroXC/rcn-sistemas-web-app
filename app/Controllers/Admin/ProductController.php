<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\ProductRepository;
use RcnSistemas\Services\ProductService;
use RcnSistemas\Services\Communications\UploadService;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductService $productService,
        ViewRenderer $views,
        private readonly ?UploadService $uploads = null,
        private readonly string $imageDirectory = '',
        private readonly string $pdfDirectory = ''
    ) {
        parent::__construct($views);
    }

    public function index(Request $request): Response
    {
        $action = in_array($request->query('action', 'list'), ['list', 'new', 'edit'], true) ? $request->query('action', 'list') : 'list';
        $productId = (int) $request->query('id', 0);
        if ($request->isMethod('POST')) {
            $productId = (int) $request->input('id', $productId);
            if ($request->input('form_action') === 'delete') {
                if ($this->products->delete($productId)) {
                    $this->flash('product_notice', 'El producto se eliminó correctamente.');
                    return Response::redirect(Url::to('/admin/productos'));
                }
                return $this->view('admin.products.index', ['pageTitle' => 'Productos', 'products' => $this->products->all(), 'error' => 'No se pudo eliminar el producto.', 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8')], 'layouts.admin', 422);
            }
            return $this->save($request, $productId);
        }
        $product = $productId > 0 ? $this->products->find($productId) : null;
        if ($action === 'edit' && !$product) return $this->view('errors.404', [], 'layouts.admin', 404);
        if ($action !== 'list') return $this->view('admin.products.form', ['pageTitle' => $product ? 'Editar producto' : 'Nuevo producto', 'product' => $product, 'error' => null, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8')], 'layouts.admin');
        $notice = $_SESSION['_flash_messages']['product_notice'] ?? null;
        unset($_SESSION['_flash_messages']['product_notice']);
        return $this->view('admin.products.index', [
            'pageTitle' => 'Productos',
            'products' => $this->products->all(), 'notice' => $notice, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'),
        ], 'layouts.admin');
    }

    public function save(Request $request, int $productId = 0): Response
    {
        try {
            $data = [
                'name' => $request->input('name', ''),
                'short_description' => $request->input('short_description', ''),
                'long_description' => $request->input('long_description', ''),
                'video_link' => $request->input('video_link', ''),
                'pdf_path' => $request->input('current_pdf', ''),
                'image_path' => $request->input('current_img', ''),
            ];
            $image = (array) $request->file('image', []);
            $pdf = (array) $request->file('pdf', []);
            if ($this->uploads && $this->imageDirectory !== '' && ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $stored = $this->uploads->store($image, $this->imageDirectory);
                $data['image_path'] = 'uploads/images/' . $stored;
            }
            if ($this->uploads && $this->pdfDirectory !== '' && ($pdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $stored = $this->uploads->store($pdf, $this->pdfDirectory, ['extensions' => ['pdf'], 'max_size' => 10 * 1024 * 1024]);
                $data['pdf_path'] = 'uploads/pdfs/' . $stored;
            }
            $benefitTitles = (array) $request->input('benefit_titles', []);
            $benefitDescriptions = (array) $request->input('benefit_descs', []);
            $benefits = [];
            foreach ($benefitTitles as $index => $title) {
                $benefits[] = ['title' => $title, 'description' => $benefitDescriptions[$index] ?? ''];
            }
            $product = $this->productService->save($data, $benefits, $productId);

            $this->flash('product_notice', 'El producto se guardó correctamente.');
            return Response::redirect(Url::to('/admin/productos'));
        } catch (InvalidArgumentException $exception) {
            return $this->view('admin.products.form', [
                'pageTitle' => 'Nuevo producto',
                'error' => $exception->getMessage(),
                'product' => $productId > 0 ? $this->products->find($productId) : null,
                'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'),
            ], 'layouts.admin', 422);
        }
    }

    private function flash(string $key, string $message): void
    {
        $_SESSION['_flash_messages'][$key] = ['message' => $message, 'type' => 'success'];
    }
}
