<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\PromotionManagementRepository;
use RcnSistemas\Services\PromotionManagementService;
use RcnSistemas\Services\Communications\UploadService;

final class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionManagementRepository $promotions,
        private readonly PromotionManagementService $service,
        private readonly UploadService $uploads,
        ViewRenderer $views
    ) {
        parent::__construct($views);
    }

    public function index(Request $request): Response
    {
        $action = in_array($request->query('action', 'list'), ['list', 'new', 'edit'], true)
            ? $request->query('action', 'list')
            : 'list';
        $promotionId = (int) $request->query('id', 0);
        $error = null;

        if ($request->isMethod('POST')) {
            $formAction = (string) $request->input('form_action', 'save');
            $promotionId = (int) $request->input('id', $promotionId);

            if ($formAction === 'delete') {
                if ($this->service->delete($promotionId)) {
                    return Response::redirect('promotions.php');
                }
                $error = 'No se pudo eliminar la promoción.';
            } else {
                $data = [
                    'title' => $request->input('title', ''),
                    'short_description' => $request->input('short_description', ''),
                    'full_description' => $request->input('full_description', ''),
                    'terms_conditions' => $request->input('terms_conditions', ''),
                    'image_path' => $request->input('current_img', $request->input('image_path', '')),
                    'start_date' => $request->input('start_date', ''),
                    'end_date' => $request->input('end_date', ''),
                    'active' => $request->input('active') !== null ? 1 : 0,
                ];
                $image = (array) $request->file('image', []);
                if (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $stored = $this->uploads->store($image, __DIR__ . '/../../../uploads/images');
                    $data['image_path'] = 'uploads/images/' . $stored;
                }

                try {
                    $this->service->save($data, $promotionId);
                    return Response::redirect('promotions.php');
                } catch (InvalidArgumentException $exception) {
                    $error = $exception->getMessage();
                }
            }
        }

        $current = $promotionId > 0 ? $this->promotions->find($promotionId, false) : null;
        if ($action === 'edit' && !$current) {
            return $this->view('errors.404', [], 'layouts.admin', 404);
        }

        return $this->view('admin.promotions.index', [
            'pageTitle' => 'Gestionar promociones',
            'action' => $action,
            'promotions' => $action === 'list' ? $this->promotions->all(false) : [],
            'promotion' => $current,
            'error' => $error,
            'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'),
        ], 'layouts.admin');
    }
}
