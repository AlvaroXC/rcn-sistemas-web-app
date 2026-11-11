<?php

namespace RcnSistemas\Controllers\Public;

use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\PromotionRepository;

final class PromotionController extends Controller
{
    public function __construct(private readonly PromotionRepository $promotions, ViewRenderer $views) { parent::__construct($views); }

    public function index(): Response
    {
        return $this->view('public.promotions.index', ['pageTitle' => 'Promociones', 'promotions' => $this->promotions->all()], 'layouts.public');
    }

    public function show(int $promotionId): Response
    {
        $promotion = $this->promotions->find($promotionId);
        return $promotion
            ? $this->view('public.promotions.show', ['pageTitle' => $promotion->title, 'promotion' => $promotion], 'layouts.public')
            : $this->view('errors.404', [], 'layouts.public', 404);
    }
}
