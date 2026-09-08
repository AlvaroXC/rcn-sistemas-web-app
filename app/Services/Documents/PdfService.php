<?php

namespace RcnSistemas\Services\Documents;

use Dompdf\Dompdf;

final class PdfService
{
    public function render(string $html, string $filename): void
    {
        $pdf = new Dompdf(['isRemoteEnabled' => false, 'defaultPaperSize' => 'letter']);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('letter');
        $pdf->render();
        $pdf->stream($filename, ['Attachment' => true]);
    }
}
