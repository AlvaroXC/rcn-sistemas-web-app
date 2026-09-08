<?php

namespace RcnSistemas\Controllers\Public;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Security\CsrfService;
use RcnSistemas\Core\Security\SessionManager;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\TestimonialRepository;
use RcnSistemas\Services\TestimonialService;

final class TestimonialController extends Controller
{
    public function __construct(private readonly TestimonialRepository $testimonials, private readonly TestimonialService $service, ViewRenderer $views) { parent::__construct($views); }

    public function index(?Request $request = null): Response
    {
        $csrf = new CsrfService(new SessionManager());
        $error = null;
        $success = null;
        if ($request?->isMethod('POST')) {
            try {
                $csrf->validate((string) $request->input('_csrf_token', ''));
                $data = ['name' => $request->input('name', ''), 'message' => $request->input('message', ''), 'rating' => (int) $request->input('rating', 0)];
                $this->service->validate($data);
                $this->testimonials->create($data);
                $success = '¡Gracias! Tu testimonial ha sido recibido.';
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->view('public.testimonials.index', ['pageTitle' => 'Testimoniales', 'testimonials' => $this->testimonials->all(), 'csrf' => $csrf->field(), 'error' => $error, 'success' => $success], 'layouts.public');
    }
}
