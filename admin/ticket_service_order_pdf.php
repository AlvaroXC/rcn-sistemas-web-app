<?php
require_once '../includes/security.php';
start_secure_session(true);

require_once '../includes/db.php';
require_once __DIR__ . '/includes/ticket_service_order_document.php';

$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ticket = $ticketId > 0 ? getTicketById($conn, $ticketId) : null;

if (!$ticket || !can_admin_user_access_ticket($ticket, current_admin_id(), current_admin_role())) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'El ticket solicitado no existe o no está disponible para tu cuenta.';
    exit;
}

$filename = 'orden-servicio-ticket-' . (int) $ticket['id'] . '.pdf';
$html = render_ticket_service_order_html($ticket);

if (class_exists(\Dompdf\Dompdf::class)) {
    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => false,
        'defaultPaperSize' => 'letter',
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter');
    $dompdf->render();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    exit;
}

$pdf = render_simple_pdf_from_lines('Orden de servicio Ticket #' . (int) $ticket['id'], build_ticket_service_order_text_lines($ticket));

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
