<?php

namespace RcnSistemas\Core\View;

use InvalidArgumentException;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\Routing\Url;

final class ViewRenderer
{
    public function __construct(private readonly string $viewsPath)
    {
    }

    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $viewData = [...$data, 'baseUrl' => rtrim(Url::to('/'), '/')];
        $viewContent = $this->renderFile($view, $viewData);

        if ($layout === null) {
            return $viewContent;
        }

        return $this->renderFile($layout, [...$viewData, 'content' => $viewContent]);
    }

    public function response(string $view, array $data = [], ?string $layout = null, int $status = 200): Response
    {
        return Response::html($this->render($view, $data, $layout), $status);
    }

    private function renderFile(string $view, array $data): string
    {
        $path = rtrim($this->viewsPath, '/') . '/' . trim(str_replace('.', '/', $view), '/') . '.php';
        if (!is_file($path)) {
            throw new InvalidArgumentException("La vista '{$view}' no existe.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
