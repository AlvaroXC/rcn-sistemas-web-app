<?php

namespace RcnSistemas\Models;

final class Ticket
{
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $consultationType,
        public readonly string $status,
        public readonly string $originType,
        public readonly string $message,
        public readonly ?int $assignedUserId = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerBusinessName = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $customerType = null,
        public readonly ?string $contextLabel = null,
        public readonly ?string $subject = null,
        public readonly ?string $attachmentPath = null,
        public readonly ?float $priceBaseAmount = null,
        public readonly ?float $priceIvaAmount = null,
        public readonly ?float $priceIsrWithholdingAmount = null,
        public readonly ?float $priceTotalAmount = null,
        public readonly ?float $priceAmount = null,
        public readonly ?string $priceCurrency = null,
        public readonly ?string $appointmentDate = null,
        public readonly ?string $appointmentTime = null,
        public readonly ?string $serviceDate = null,
        public readonly ?string $serviceStartTime = null,
        public readonly ?string $serviceEndTime = null,
        public readonly ?string $assignedUsername = null,
        public readonly ?string $assignedDisplayName = null,
        public readonly ?string $assignedByUsername = null,
        public readonly ?string $assignedByDisplayName = null,
        public readonly ?string $assignedAt = null,
        public readonly ?string $priceUpdatedByUsername = null,
        public readonly ?string $priceUpdatedByDisplayName = null,
        public readonly ?string $priceUpdatedAt = null,
        public readonly ?string $messageUpdatedByUsername = null,
        public readonly ?string $messageUpdatedByDisplayName = null,
        public readonly ?string $messageUpdatedAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (int) ($data['customer_id'] ?? 0),
            trim((string) ($data['consultation_type'] ?? '')),
            trim((string) ($data['status'] ?? 'Nuevo')),
            trim((string) ($data['origin_type'] ?? 'contact')),
            trim((string) ($data['message'] ?? '')),
            isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null,
            self::nullableString($data['customer_name'] ?? null),
            self::nullableString($data['customer_business_name'] ?? null),
            self::nullableString($data['customer_email'] ?? null),
            self::nullableString($data['customer_phone'] ?? null),
            self::nullableString($data['customer_type'] ?? null),
            self::nullableString($data['context_label'] ?? null),
            self::nullableString($data['subject'] ?? null),
            self::nullableString($data['attachment_path'] ?? null),
            self::nullableFloat($data['price_base_amount'] ?? null),
            self::nullableFloat($data['price_iva_amount'] ?? null),
            self::nullableFloat($data['price_isr_withholding_amount'] ?? null),
            self::nullableFloat($data['price_total_amount'] ?? null),
            self::nullableFloat($data['price_amount'] ?? null),
            self::nullableString($data['price_currency'] ?? null),
            self::nullableString($data['appointment_date'] ?? null),
            self::nullableString($data['appointment_time'] ?? null),
            self::nullableString($data['service_date'] ?? null),
            self::nullableString($data['service_start_time'] ?? null),
            self::nullableString($data['service_end_time'] ?? null),
            self::nullableString($data['assigned_username'] ?? null),
            self::nullableString($data['assigned_display_name'] ?? null),
            self::nullableString($data['assigned_by_username'] ?? null),
            self::nullableString($data['assigned_by_display_name'] ?? null),
            self::nullableString($data['assigned_at'] ?? null),
            self::nullableString($data['price_updated_by_username'] ?? null),
            self::nullableString($data['price_updated_by_display_name'] ?? null),
            self::nullableString($data['price_updated_at'] ?? null),
            self::nullableString($data['message_updated_by_username'] ?? null),
            self::nullableString($data['message_updated_by_display_name'] ?? null),
            self::nullableString($data['message_updated_at'] ?? null),
            self::nullableString($data['created_at'] ?? null),
            self::nullableString($data['updated_at'] ?? null)
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
