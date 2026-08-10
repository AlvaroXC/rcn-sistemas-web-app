<?php

function get_ticket_service_order_responsible_label(array $ticket) {
    $displayName = trim((string) ($ticket['assigned_display_name'] ?? ''));
    $username = trim((string) ($ticket['assigned_username'] ?? ''));

    if ($displayName !== '') {
        return $displayName;
    }

    if ($username !== '') {
        return $username;
    }

    return 'Sin responsable asignado';
}

function get_ticket_service_order_summary_lines(array $ticket) {
    return [
        'Orden de servicio',
        'Ticket #' . (int) $ticket['id'],
        'Fecha de emision: ' . date('d/m/Y H:i'),
        '',
        'Datos del cliente',
        'Nombre: ' . trim((string) ($ticket['customer_name'] ?? 'No proporcionado')),
        'Razon social: ' . trim((string) (($ticket['customer_business_name'] ?? '') !== '' ? $ticket['customer_business_name'] : 'No proporcionada')),
        'Correo: ' . trim((string) (($ticket['customer_email'] ?? '') !== '' ? $ticket['customer_email'] : 'No proporcionado')),
        'Telefono: ' . trim((string) (($ticket['customer_phone'] ?? '') !== '' ? $ticket['customer_phone'] : 'No proporcionado')),
        '',
        'Resumen del ticket',
        'Tipo de consulta: ' . trim((string) ($ticket['consultation_type'] ?? '')),
        'Estado actual: ' . trim((string) ($ticket['status'] ?? '')),
        'Responsable: ' . get_ticket_service_order_responsible_label($ticket),
        'Fecha de creacion: ' . date('d/m/Y H:i', strtotime((string) $ticket['created_at'])),
        'Asunto: ' . trim((string) (($ticket['subject'] ?? '') !== '' ? $ticket['subject'] : 'Sin asunto')),
        'Contexto: ' . trim((string) (($ticket['context_label'] ?? '') !== '' ? $ticket['context_label'] : 'Sin contexto adicional')),
        'Precio: ' . format_ticket_price($ticket['price_amount'] ?? null, $ticket['price_currency'] ?? 'MXN'),
        '',
        'Ejecucion del servicio',
        'Fecha programada: ' . format_ticket_service_date($ticket['service_date'] ?? null),
        'Hora de inicio: ' . format_ticket_service_time($ticket['service_start_time'] ?? null),
        'Hora de finalizacion: ' . format_ticket_service_time($ticket['service_end_time'] ?? null),
        'Duracion: ' . format_ticket_service_duration($ticket['service_start_time'] ?? null, $ticket['service_end_time'] ?? null),
        '',
        'Solicitud del cliente',
    ];
}

function build_ticket_service_order_text_lines(array $ticket) {
    $lines = get_ticket_service_order_summary_lines($ticket);
    $message = preg_split('/\R/u', trim((string) ($ticket['message'] ?? '')));

    foreach ($message as $messageLine) {
        $messageLine = trim((string) $messageLine);
        if ($messageLine === '') {
            $lines[] = '';
            continue;
        }

        foreach (wordwrap($messageLine, 92, "\n", true) ? explode("\n", wordwrap($messageLine, 92, "\n", true)) : [$messageLine] as $wrappedLine) {
            $lines[] = $wrappedLine;
        }
    }

    $lines[] = '';
    $lines[] = 'Firma del cliente: ________________________________';
    $lines[] = '';
    $lines[] = 'Firma o sello interno: ____________________________';

    return $lines;
}

function render_ticket_service_order_html(array $ticket) {
    $responsibleLabel = get_ticket_service_order_responsible_label($ticket);
    $priceLabel = format_ticket_price($ticket['price_amount'] ?? null, $ticket['price_currency'] ?? 'MXN');
    $serviceDateLabel = format_ticket_service_date($ticket['service_date'] ?? null);
    $serviceStartLabel = format_ticket_service_time($ticket['service_start_time'] ?? null);
    $serviceEndLabel = format_ticket_service_time($ticket['service_end_time'] ?? null);
    $serviceDurationLabel = format_ticket_service_duration($ticket['service_start_time'] ?? null, $ticket['service_end_time'] ?? null);
    $subjectLabel = trim((string) (($ticket['subject'] ?? '') !== '' ? $ticket['subject'] : 'Sin asunto'));
    $contextLabel = trim((string) (($ticket['context_label'] ?? '') !== '' ? $ticket['context_label'] : 'Sin contexto adicional'));
    $businessNameLabel = trim((string) (($ticket['customer_business_name'] ?? '') !== '' ? $ticket['customer_business_name'] : 'No proporcionada'));
    $emailLabel = trim((string) (($ticket['customer_email'] ?? '') !== '' ? $ticket['customer_email'] : 'No proporcionado'));
    $phoneLabel = trim((string) (($ticket['customer_phone'] ?? '') !== '' ? $ticket['customer_phone'] : 'No proporcionado'));

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de servicio Ticket #<?php echo (int) $ticket['id']; ?></title>
    <style>
        @page {
            size: letter;
            margin: 0;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #243247;
            margin: 0;
            padding: 22px 24px 24px;
            font-size: 9px;
            line-height: 1.35;
            background: #f4f7fb;
        }
        .document {
            background: #ffffff;
            border: 1px solid #dce4ee;
            padding: 0 18px 18px;
        }
        .header {
            background: #102a43;
            color: #ffffff;
            margin: 0 -18px 16px;
            padding: 0;
        }
        .header-table,
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            padding: 18px;
        }
        .header__left,
        .header__right {
            vertical-align: top;
        }
        .header__right {
            text-align: right;
            width: 34%;
            font-size: 8px;
            line-height: 1.5;
        }
        h1 {
            margin: 0 0 3px;
            font-size: 21px;
            letter-spacing: -0.02em;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 11px;
            color: #102a43;
        }
        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #2f80a8;
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 5px;
        }
        .header .eyebrow {
            color: #82d4f5;
        }
        .subtitle {
            color: #c8d6e5;
            font-size: 8px;
        }
        .folio {
            color: #82d4f5;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .meta-strip {
            width: 100%;
            margin-bottom: 16px;
        }
        .meta-card {
            width: 25%;
            background: #eaf6fb;
            border-left: 3px solid #2f80a8;
            padding: 8px 10px;
            vertical-align: middle;
        }
        .meta-card--accent {
            background: #fff4df;
            border-left-color: #e6a23c;
        }
        .meta-label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .meta-value {
            color: #102a43;
            font-size: 10px;
            font-weight: bold;
            margin-top: 3px;
        }
        .section {
            margin-top: 12px;
            page-break-inside: avoid;
        }
        .section__heading {
            border-bottom: 2px solid #dce4ee;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .grid {
            display: block;
            width: 100%;
            border-spacing: 0;
        }
        .panel {
            background: #f7f9fc;
            border: 1px solid #e3eaf2;
            padding: 10px 12px;
        }
        .grid__row {
            display: block;
        }
        .grid__cell {
            display: inline-block;
            width: 48%;
            padding: 0 12px 6px 0;
            vertical-align: top;
            box-sizing: border-box;
        }
        .label {
            font-weight: bold;
            color: #102a43;
        }
        .message {
            border-left: 4px solid #2f80a8;
            background: #f7f9fc;
            padding: 12px 14px;
            white-space: pre-wrap;
            min-height: 64px;
            max-height: 180px;
            overflow: hidden;
        }
        .service-table {
            background: #eef8fc;
            border: 1px solid #c9e6f1;
            padding: 10px 12px;
        }
        .service-table .grid__cell {
            padding-bottom: 0;
        }
        .service-value {
            color: #102a43;
            font-size: 10px;
            font-weight: bold;
        }
        .signatures {
            display: block;
            width: 100%;
            margin-top: 18px;
            page-break-inside: avoid;
        }
        .signatures__cell {
            display: inline-block;
            width: 46%;
            padding: 0 18px 0 0;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #8ea4b7;
            margin-top: 28px;
            padding-top: 6px;
            color: #64748b;
            font-size: 8px;
        }
        .footer-note { color: #8293a6; font-size: 7px; margin-top: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="document">
        <div class="header">
            <table class="header-table" role="presentation">
                <tr>
                    <td class="header__left">
                        <div class="eyebrow">RCN Sistemas</div>
                        <h1>Orden de servicio</h1>
                        <div class="subtitle">Documento de atención y seguimiento técnico</div>
                    </td>
                    <td class="header__right">
                        <div class="folio">#<?php echo (int) $ticket['id']; ?></div>
                        <div>Folio del ticket</div>
                        <div>Emitido: <?php echo e(date('d/m/Y H:i')); ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-strip">
            <table class="meta-table" role="presentation">
                <tr>
                    <td class="meta-card">
                        <div class="meta-label">Estado</div>
                        <div class="meta-value"><?php echo e($ticket['status']); ?></div>
                    </td>
                    <td class="meta-card">
                        <div class="meta-label">Responsable</div>
                        <div class="meta-value"><?php echo e($responsibleLabel); ?></div>
                    </td>
                    <td class="meta-card">
                        <div class="meta-label">Fecha programada</div>
                        <div class="meta-value"><?php echo e($serviceDateLabel); ?></div>
                    </td>
                    <td class="meta-card meta-card--accent">
                        <div class="meta-label">Duración</div>
                        <div class="meta-value"><?php echo e($serviceDurationLabel); ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="eyebrow">Cliente</div>
            <div class="section__heading"><h2>Datos de contacto</h2></div>
            <div class="panel">
                <div class="grid">
                    <div class="grid__row">
                        <div class="grid__cell"><span class="label">Nombre:</span> <?php echo e($ticket['customer_name']); ?></div>
                        <div class="grid__cell"><span class="label">Razón social:</span> <?php echo e($businessNameLabel); ?></div>
                    </div>
                    <div class="grid__row">
                        <div class="grid__cell"><span class="label">Correo:</span> <?php echo e($emailLabel); ?></div>
                        <div class="grid__cell"><span class="label">Teléfono:</span> <?php echo e($phoneLabel); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="eyebrow">Ticket</div>
            <div class="section__heading"><h2>Resumen del caso</h2></div>
            <div class="panel">
                <div class="grid">
                    <div class="grid__row">
                        <div class="grid__cell"><span class="label">Tipo de consulta:</span> <?php echo e($ticket['consultation_type']); ?></div>
                        <div class="grid__cell"><span class="label">Creado:</span> <?php echo e(date('d/m/Y H:i', strtotime((string) $ticket['created_at']))); ?></div>
                    </div>
                    <div class="grid__row">
                        <div class="grid__cell"><span class="label">Precio:</span> <?php echo e($priceLabel); ?></div>
                        <div class="grid__cell"><span class="label">Asunto:</span> <?php echo e($subjectLabel); ?></div>
                    </div>
                    <div class="grid__row">
                        <div class="grid__cell" colspan="2"><span class="label">Contexto:</span> <?php echo e($contextLabel); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="eyebrow">Servicio</div>
            <div class="section__heading"><h2>Ejecución programada</h2></div>
            <div class="service-table">
                <div class="grid">
                <div class="grid__row">
                    <div class="grid__cell"><span class="label">Fecha programada</span><br><span class="service-value"><?php echo e($serviceDateLabel); ?></span></div>
                    <div class="grid__cell"><span class="label">Duración</span><br><span class="service-value"><?php echo e($serviceDurationLabel); ?></span></div>
                </div>
                <div class="grid__row">
                    <div class="grid__cell"><span class="label">Hora de inicio</span><br><span class="service-value"><?php echo e($serviceStartLabel); ?></span></div>
                    <div class="grid__cell"><span class="label">Hora de finalización</span><br><span class="service-value"><?php echo e($serviceEndLabel); ?></span></div>
                </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="eyebrow">Solicitud</div>
            <div class="section__heading"><h2>Detalle del cliente</h2></div>
            <div class="message"><?php echo e($ticket['message']); ?></div>
        </div>

        <div class="signatures">
            <div class="table-row">
                <div class="signatures__cell">
                    <div class="signature-line">Firma del cliente</div>
                </div>
                <div class="signatures__cell">
                    <div class="signature-line">Firma o sello interno</div>
                </div>
            </div>
        </div>
        <div class="footer-note">RCN Sistemas · Orden de servicio #<?php echo (int) $ticket['id']; ?></div>
    </div>
</body>
</html>
    <?php

    return ob_get_clean();
}

function ticket_service_order_pdf_escape_text($text) {
    $text = (string) $text;
    $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    if ($encoded === false) {
        $encoded = preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
}

function render_simple_pdf_from_lines($title, array $lines) {
    $title = ticket_service_order_pdf_escape_text($title);
    $chunks = array_chunk($lines, 42);
    $objects = [];
    $pageIds = [];
    $fontObjectId = 3;
    $nextObjectId = 4;

    foreach ($chunks as $pageLines) {
        $pageObjectId = $nextObjectId++;
        $contentObjectId = $nextObjectId++;
        $pageIds[] = $pageObjectId;

        $content = "BT\n/F1 11 Tf\n50 744 Td\n14 TL\n(" . $title . ") Tj\nT*\nT*\n";
        foreach ($pageLines as $line) {
            $content .= '(' . ticket_service_order_pdf_escape_text($line) . ") Tj\nT*\n";
        }
        $content .= "ET\n";

        $objects[$pageObjectId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 {$fontObjectId} 0 R >> >> /Contents {$contentObjectId} 0 R >>";
        $objects[$contentObjectId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
    }

    $kids = implode(' ', array_map(static function ($id) {
        return $id . ' 0 R';
    }, $pageIds));

    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "<< /Type /Pages /Kids [ {$kids} ] /Count " . count($pageIds) . " >>";
    $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $objectId => $objectBody) {
        $offsets[$objectId] = strlen($pdf);
        $pdf .= $objectId . " 0 obj\n" . $objectBody . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($objectId = 1; $objectId <= count($objects); $objectId++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}
