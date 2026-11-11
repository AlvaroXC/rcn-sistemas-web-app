<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Ticket;

final class MysqliTicketRepository implements TicketRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function create(array $data): ?Ticket
    {
        $customerId = (int) ($data['customer_id'] ?? 0); $consultationType = (string) ($data['consultation_type'] ?? 'Informacion');
        $status = (string) ($data['status'] ?? 'Nuevo'); $originType = (string) ($data['origin_type'] ?? 'contact');
        $productId = ($data['product_id'] ?? null) !== null ? (int) $data['product_id'] : null; $promoId = ($data['promo_id'] ?? null) !== null ? (int) $data['promo_id'] : null;
        $contextLabel = (string) ($data['context_label'] ?? ''); $subject = (string) ($data['subject'] ?? ''); $message = (string) ($data['message'] ?? ''); $attachment = $data['attachment_path'] ?? null;
        $assignedUserId = ($data['assigned_user_id'] ?? null) !== null ? (int) $data['assigned_user_id'] : null;
        $assignedByUserId = ($data['assigned_by_user_id'] ?? null) !== null ? (int) $data['assigned_by_user_id'] : null;
        $statement = $this->connection->prepare('INSERT INTO tickets (customer_id, consultation_type, status, origin_type, product_id, promo_id, context_label, subject, message, attachment_path, assigned_user_id, assigned_by_user_id, assigned_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? IS NULL THEN NULL ELSE CURRENT_TIMESTAMP END)');
        $statement->bind_param('isssiissssiii', $customerId, $consultationType, $status, $originType, $productId, $promoId, $contextLabel, $subject, $message, $attachment, $assignedUserId, $assignedByUserId, $assignedUserId);
        if (!$statement->execute()) return null;
        $createdTicketId = (int) $this->connection->insert_id;
        if (isset($data['price']) && is_array($data['price'])) {
            $price = $data['price']; $currency = (string) ($price['currency'] ?? 'MXN'); $updatedAt = date('Y-m-d H:i:s'); $updatedBy = ($data['assigned_by_user_id'] ?? null) !== null ? (int) $data['assigned_by_user_id'] : null;
            $priceStatement = $this->connection->prepare('UPDATE tickets SET price_base_amount=?, price_iva_amount=?, price_isr_withholding_amount=?, price_total_amount=?, price_amount=?, price_currency=?, price_updated_by=?, price_updated_at=? WHERE id=?');
            $priceBase = (string) $price['base']; $priceIva = (string) $price['iva']; $priceIsr = (string) $price['isr_withholding']; $priceTotal = (string) $price['total']; $priceStatement->bind_param('ssssssisi', $priceBase, $priceIva, $priceIsr, $priceTotal, $priceTotal, $currency, $updatedBy, $updatedAt, $createdTicketId); $priceStatement->execute();
        }
        if (($data['appointment_date'] ?? '') !== '' || ($data['service_date'] ?? '') !== '') {
            $appointmentDate = ($data['appointment_date'] ?? '') !== '' ? $data['appointment_date'] : null; $appointmentTime = ($data['appointment_time'] ?? '') !== '' ? $data['appointment_time'] : null; $serviceDate = ($data['service_date'] ?? '') !== '' ? $data['service_date'] : null; $serviceStart = ($data['service_start_time'] ?? '') !== '' ? $data['service_start_time'] : null; $serviceEnd = ($data['service_end_time'] ?? '') !== '' ? $data['service_end_time'] : null; $scheduleStatement = $this->connection->prepare('UPDATE tickets SET appointment_date=?, appointment_time=?, service_date=?, service_start_time=?, service_end_time=? WHERE id=?'); $scheduleStatement->bind_param('sssssi', $appointmentDate, $appointmentTime, $serviceDate, $serviceStart, $serviceEnd, $createdTicketId); $scheduleStatement->execute();
        }
        return $this->find($createdTicketId);
    }

    public function find(int $ticketId): ?Ticket
    {
        $statement = $this->connection->prepare('SELECT t.*, c.name AS customer_name, c.business_name AS customer_business_name, c.customer_type, c.email AS customer_email, c.phone AS customer_phone, assigned.username AS assigned_username, assigned.display_name AS assigned_display_name, assigned_by.username AS assigned_by_username, assigned_by.display_name AS assigned_by_display_name, price_updated_by.username AS price_updated_by_username, price_updated_by.display_name AS price_updated_by_display_name, message_updated_by.username AS message_updated_by_username, message_updated_by.display_name AS message_updated_by_display_name FROM tickets t INNER JOIN customers c ON c.id = t.customer_id LEFT JOIN users assigned ON assigned.id = t.assigned_user_id LEFT JOIN users assigned_by ON assigned_by.id = t.assigned_by_user_id LEFT JOIN users price_updated_by ON price_updated_by.id = t.price_updated_by LEFT JOIN users message_updated_by ON message_updated_by.id = t.message_updated_by WHERE t.id = ? LIMIT 1');
        $statement->bind_param('i', $ticketId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? Ticket::fromArray($row) : null;
    }

    public function updateStatus(int $ticketId, string $status): bool
    {
        $statement = $this->connection->prepare('UPDATE tickets SET status = ? WHERE id = ?');
        $statement->bind_param('si', $status, $ticketId);
        return $statement->execute();
    }

    public function updatePrice(int $ticketId, array $price, int $userId): bool
    {
        $base = (string) $price['base']; $iva = (string) $price['iva']; $isr = (string) $price['isr_withholding']; $total = (string) $price['total']; $currency = (string) ($price['currency'] ?? 'MXN');
        $statement = $this->connection->prepare('UPDATE tickets SET price_base_amount=?, price_iva_amount=?, price_isr_withholding_amount=?, price_total_amount=?, price_amount=?, price_currency=?, price_updated_by=?, price_updated_at=CURRENT_TIMESTAMP WHERE id=? AND status <> "Cerrado"');
        $statement->bind_param('ssssssii', $base, $iva, $isr, $total, $total, $currency, $userId, $ticketId);
        return $statement->execute();
    }

    public function updateSchedule(int $ticketId, ?string $date, ?string $time, ?string $serviceDate, ?string $serviceStart, ?string $serviceEnd): bool
    {
        $statement = $this->connection->prepare('UPDATE tickets SET appointment_date=?, appointment_time=?, service_date=?, service_start_time=?, service_end_time=? WHERE id=? AND status <> "Cerrado"');
        $statement->bind_param('sssssi', $date, $time, $serviceDate, $serviceStart, $serviceEnd, $ticketId);
        return $statement->execute();
    }

    public function updateAssignee(int $ticketId, int $userId, int $actorId): bool
    {
        $statement = $this->connection->prepare('UPDATE tickets SET assigned_user_id=?, assigned_by_user_id=?, assigned_at=CURRENT_TIMESTAMP WHERE id=? AND status <> "Cerrado"');
        $statement->bind_param('iii', $userId, $actorId, $ticketId);
        return $statement->execute();
    }
}
