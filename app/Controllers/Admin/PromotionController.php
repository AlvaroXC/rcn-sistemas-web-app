<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;
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
                    $this->flash('promotion_notice', 'La promoción se eliminó correctamente.');
                    return Response::redirect(Url::to('/admin/promociones'));
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
                    $this->flash('promotion_notice', 'La promoción se guardó correctamente.');
                    return Response::redirect(Url::to('/admin/promociones'));
                } catch (InvalidArgumentException $exception) {
                    $error = $exception->getMessage();
                }
            }
        }

        $current = $promotionId > 0 ? $this->promotions->find($promotionId, false) : null;
        if ($action === 'edit' && !$current) {
            return $this->view('errors.404', [], 'layouts.admin', 404);
        }

        $notice = $_SESSION['_flash_messages']['promotion_notice'] ?? null;
        unset($_SESSION['_flash_messages']['promotion_notice']);
        return $this->view('admin.promotions.index', [
            'pageTitle' => 'Gestionar promociones',
            'action' => $action,
            'promotions' => $action === 'list' ? $this->promotions->all(false) : [],
            'promotion' => $current,
            'error' => $error,
            'notice' => $notice,
            'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'),
        ], 'layouts.admin');
    }

    private function flash(string $key, string $message): void
    {
        $_SESSION['_flash_messages'][$key] = ['message' => $message, 'type' => 'success'];
    }
}
