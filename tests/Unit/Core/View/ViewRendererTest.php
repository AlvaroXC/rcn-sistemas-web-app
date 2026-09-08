<?php

namespace Tests\Unit\Core\View;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Core\View\ViewRenderer;

final class ViewRendererTest extends TestCase
{
    public function test_it_renders_a_view_inside_a_layout(): void
    {
        $renderer = new ViewRenderer(__DIR__ . '/../../../../app/Views');

        $output = $renderer->render('errors.404', ['pageTitle' => 'No encontrado'], 'layouts.public');

        self::assertStringContainsString('<title>No encontrado</title>', $output);
        self::assertStringContainsString('Página no encontrada', $output);
    }
}
