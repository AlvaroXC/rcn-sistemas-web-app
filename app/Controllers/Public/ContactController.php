<?php

namespace RcnSistemas\Controllers\Public;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Services\Communications\ContactService;
use RcnSistemas\Services\Communications\UploadService;
use RcnSistemas\Repositories\ProductRepository;
use RcnSistemas\Repositories\PromotionRepository;

final class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contact, private readonly CsrfService $csrf, private readonly UploadService $uploads, private readonly string $uploadDirectory, ViewRenderer $views, private readonly ?ProductRepository $products = null, private readonly ?PromotionRepository $promotions = null) { parent::__construct($views); }

    public function index(Request $request): Response
    {
        $productId = (int) $request->query('product', $request->input('product_id', 0));
        $promoId = (int) $request->query('promo', $request->input('promo_id', 0));
        $context = trim((string) $request->query('context_label', ''));
        if ($context === '' && $productId > 0 && $this->products?->find($productId)) $context = 'Cotización para producto: ' . $this->products->find($productId)->name;
        if ($context === '' && $promoId > 0 && $this->promotions?->find($promoId)) $context = 'Interés en promoción: ' . $this->promotions->find($promoId)->title;
        $error = null;
        $sent = false;
        $values = ['nombre' => '', 'email' => '', 'telefono' => '', 'opcion' => 'Informacion', 'mensaje' => ''];
        if ($request->isMethod('POST')) {
            try {
                $this->csrf->validate((string) $request->input('_csrf_token', ''));
                foreach ($values as $key => $value) $values[$key] = trim((string) $request->input($key, $value));
                $file = $request->file('imagen');
                $stored = $file ? $this->uploads->store($file, $this->uploadDirectory) : '';
                $this->contact->createTicket(['name' => $values['nombre'], 'email' => $values['email'], 'phone' => $values['telefono'], 'consultation_type' => $values['opcion'], 'message' => $values['mensaje'], 'product_id' => $productId, 'promo_id' => $promoId, 'context_label' => $context, 'origin_type' => $productId > 0 ? 'product' : ($promoId > 0 ? 'promotion' : 'contact'), 'attachment_path' => $stored ? 'uploads/tickets/' . $stored : null, 'attachment_full_path' => $stored ? $this->uploadDirectory . DIRECTORY_SEPARATOR . $stored : null], null);
                $sent = true;
                $values = ['nombre' => '', 'email' => '', 'telefono' => '', 'opcion' => 'Informacion', 'mensaje' => ''];
            } catch (InvalidArgumentException | \RuntimeException $exception) { $error = $exception->getMessage(); }
        }
        return $this->view('public.contact.index', ['pageTitle' => 'Contacto', 'csrf' => $this->csrf->field(), 'context' => $context, 'productId' => $productId, 'promoId' => $promoId, 'error' => $error, 'sent' => $sent, 'values' => $values], 'layouts.public');
    }
}
