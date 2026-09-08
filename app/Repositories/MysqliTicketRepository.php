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
        $productId = $data['product_id'] !== null ? (int) $data['product_id'] : null; $promoId = $data['promo_id'] !== null ? (int) $data['promo_id'] : null;
        $contextLabel = (string) ($data['context_label'] ?? ''); $subject = (string) ($data['subject'] ?? ''); $message = (string) ($data['message'] ?? ''); $attachment = $data['attachment_path'] ?? null;
        $statement = $this->connection->prepare('INSERT INTO tickets (customer_id, consultation_type, status, origin_type, product_id, promo_id, context_label, subject, message, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param('isssiissss', $customerId, $consultationType, $status, $originType, $productId, $promoId, $contextLabel, $subject, $message, $attachment);
        if (!$statement->execute()) return null;
        return $this->find((int) $this->connection->insert_id);
    }

    public function find(int $ticketId): ?Ticket
    {
        $statement = $this->connection->prepare('SELECT * FROM tickets WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $ticketId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        return $row ? Ticket::fromArray($row) : null;
    }

    public function updateStatus(int $ticketId, string $status): bool
    {
        $statement = $this->connection->prepare('UPDATE tickets SET status = ? WHERE id = ?');
        $statement->bind_param('si', $status, $ticketId);
        return $statement->execute();
    }
}
