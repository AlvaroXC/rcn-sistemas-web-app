<?php

namespace RcnSistemas\Services\Documents;

use RcnSistemas\Models\Ticket;

final class ServiceOrderDocument
{
    public function html(Ticket $ticket): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        $value = static fn (mixed $value, string $fallback = 'No proporcionado'): string => trim((string) ($value ?? '')) !== '' ? trim((string) $value) : $fallback;
        $date = static function (?string $date, string $fallback = 'No disponible'): string { if (!$date || strtotime($date) === false) return $fallback; return date('d/m/Y H:i', strtotime($date)); };
        $day = static function (?string $date): string { if (!$date || strtotime($date) === false) return 'No programada'; return date('d/m/Y', strtotime($date)); };
        $time = static function (?string $time): string { if (!$time) return 'No registrada'; $timestamp = strtotime($time); return $timestamp === false ? $time : date('H:i', $timestamp); };
        $duration = static function (?string $start, ?string $end): string {
            if (!$start || !$end || strtotime($start) === false || strtotime($end) === false) return 'Pendiente de registrar';
            $startMinutes = ((int) date('H', strtotime($start))) * 60 + (int) date('i', strtotime($start));
            $endMinutes = ((int) date('H', strtotime($end))) * 60 + (int) date('i', strtotime($end));
            if ($endMinutes < $startMinutes) return 'Pendiente de registrar';
            $minutes = $endMinutes - $startMinutes; $hours = intdiv($minutes, 60); $remaining = $minutes % 60;
            return $hours > 0 && $remaining > 0 ? $hours . ' h ' . $remaining . ' min' : ($hours > 0 ? $hours . ' h' : $remaining . ' min');
        };
        $responsible = $value($ticket->assignedDisplayName ?: $ticket->assignedUsername, 'Sin responsable asignado');
        $business = $value($ticket->customerBusinessName, 'No proporcionada');
        $email = $value($ticket->customerEmail); $phone = $value($ticket->customerPhone);
        $subject = $value($ticket->subject, 'Sin asunto'); $context = $value($ticket->contextLabel, 'Sin contexto adicional');
        $currency = $ticket->priceCurrency ?: 'MXN'; $price = $ticket->priceTotalAmount ?? $ticket->priceAmount ?? $ticket->priceBaseAmount;
        $priceLabel = $price === null ? 'Sin precio asignado' : $currency . ' ' . number_format($price, 2, '.', ',');
        $serviceDuration = $duration($ticket->serviceStartTime, $ticket->serviceEndTime);

        ob_start();
        ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de servicio Ticket #<?php echo $ticket->id; ?></title>
    <style>
        @page { size: letter; margin: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #243247; margin: 0; padding: 22px 24px 24px; font-size: 9px; line-height: 1.35; background: #f4f7fb; }
        .document { background: #fff; border: 1px solid #dce4ee; padding: 0 18px 18px; }
        .header { background: #102a43; color: #fff; margin: 0 -18px 16px; } table { width: 100%; border-collapse: collapse; }
        .header td { padding: 18px; vertical-align: top; } .header-right { width: 34%; text-align: right; font-size: 8px; line-height: 1.5; }
        h1 { margin: 0 0 3px; font-size: 21px; } h2 { margin: 0 0 8px; font-size: 11px; color: #102a43; }
        .eyebrow { text-transform: uppercase; letter-spacing: .08em; color: #2f80a8; font-weight: bold; font-size: 8px; margin-bottom: 5px; }
        .header .eyebrow { color: #82d4f5; } .subtitle { color: #c8d6e5; font-size: 8px; } .folio { color: #82d4f5; font-size: 15px; font-weight: bold; }
        .meta td { width: 25%; background: #eaf6fb; border-left: 3px solid #2f80a8; padding: 8px 10px; vertical-align: middle; }
        .meta td:last-child { background: #fff4df; border-left-color: #e6a23c; } .meta-label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .meta-value { color: #102a43; font-size: 10px; font-weight: bold; margin-top: 3px; } .section { margin-top: 12px; page-break-inside: avoid; }
        .section-heading { border-bottom: 2px solid #dce4ee; padding-bottom: 5px; margin-bottom: 8px; } .panel, .service { background: #f7f9fc; border: 1px solid #e3eaf2; padding: 10px 12px; }
        .cell { display: inline-block; width: 48%; padding: 0 12px 7px 0; vertical-align: top; box-sizing: border-box; } .label { font-weight: bold; color: #102a43; }
        .service { background: #eef8fc; border-color: #c9e6f1; } .service-value { color: #102a43; font-size: 10px; font-weight: bold; }
        .message { border-left: 4px solid #2f80a8; background: #f7f9fc; padding: 12px 14px; white-space: pre-wrap; min-height: 64px; }
        .signatures { margin-top: 18px; page-break-inside: avoid; } .signature { display: inline-block; width: 46%; padding-right: 18px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #8ea4b7; margin-top: 28px; padding-top: 6px; color: #64748b; font-size: 8px; } .footer { color: #8293a6; font-size: 7px; margin-top: 12px; text-align: center; }
    </style>
</head>
<body><div class="document">
    <div class="header"><table><tr><td><div class="eyebrow">RCN Sistemas</div><h1>Orden de servicio</h1><div class="subtitle">Documento de atención y seguimiento técnico</div></td><td class="header-right"><div class="folio">#<?php echo $ticket->id; ?></div><div>Folio del ticket</div><div>Emitido: <?php echo date('d/m/Y H:i'); ?></div></td></tr></table></div>
    <table class="meta"><tr><td><div class="meta-label">Estado</div><div class="meta-value"><?php echo $escape($ticket->status); ?></div></td><td><div class="meta-label">Responsable</div><div class="meta-value"><?php echo $escape($responsible); ?></div></td><td><div class="meta-label">Fecha programada</div><div class="meta-value"><?php echo $escape($day($ticket->serviceDate)); ?></div></td><td><div class="meta-label">Duración</div><div class="meta-value"><?php echo $escape($serviceDuration); ?></div></td></tr></table>
    <div class="section"><div class="eyebrow">Cliente</div><div class="section-heading"><h2>Datos de contacto</h2></div><div class="panel"><div class="cell"><span class="label">Nombre:</span> <?php echo $escape($value($ticket->customerName)); ?></div><div class="cell"><span class="label">Razón social:</span> <?php echo $escape($business); ?></div><div class="cell"><span class="label">Correo:</span> <?php echo $escape($email); ?></div><div class="cell"><span class="label">Teléfono:</span> <?php echo $escape($phone); ?></div></div></div>
    <div class="section"><div class="eyebrow">Ticket</div><div class="section-heading"><h2>Resumen del caso</h2></div><div class="panel"><div class="cell"><span class="label">Tipo de consulta:</span> <?php echo $escape($ticket->consultationType); ?></div><div class="cell"><span class="label">Creado:</span> <?php echo $escape($date($ticket->createdAt)); ?></div><div class="cell"><span class="label">Precio:</span> <?php echo $escape($priceLabel); ?></div><div class="cell"><span class="label">Asunto:</span> <?php echo $escape($subject); ?></div><div><span class="label">Contexto:</span> <?php echo $escape($context); ?></div></div></div>
    <div class="section"><div class="eyebrow">Servicio</div><div class="section-heading"><h2>Ejecución programada</h2></div><div class="service"><div class="cell"><span class="label">Fecha programada</span><br><span class="service-value"><?php echo $escape($day($ticket->serviceDate)); ?></span></div><div class="cell"><span class="label">Duración</span><br><span class="service-value"><?php echo $escape($serviceDuration); ?></span></div><div class="cell"><span class="label">Hora de inicio</span><br><span class="service-value"><?php echo $escape($time($ticket->serviceStartTime)); ?></span></div><div class="cell"><span class="label">Hora de finalización</span><br><span class="service-value"><?php echo $escape($time($ticket->serviceEndTime)); ?></span></div></div></div>
    <div class="section"><div class="eyebrow">Solicitud</div><div class="section-heading"><h2>Detalle del cliente</h2></div><div class="message"><?php echo nl2br($escape($ticket->message)); ?></div></div>
    <div class="signatures"><div class="signature"><div class="signature-line">Firma del cliente</div></div><div class="signature"><div class="signature-line">Firma o sello interno</div></div></div>
    <div class="footer">RCN Sistemas · Orden de servicio #<?php echo $ticket->id; ?></div>
</div></body></html>
        <?php
        return (string) ob_get_clean();
    }
}
