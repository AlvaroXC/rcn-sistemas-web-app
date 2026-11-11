<?php

namespace RcnSistemas\Core;

use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;

abstract class Controller
{
    public function __construct(protected readonly ViewRenderer $views)
    {
    }

    protected function view(string $view, array $data = [], ?string $layout = null, int $status = 200): Response
    {
        return $this->views->response($view, $data, $layout, $status);
    }
}
