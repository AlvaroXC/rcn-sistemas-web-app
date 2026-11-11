<?php

namespace RcnSistemas\Services\Tickets;

final class TicketListService
{
    public function filters(array $input): array
    {
        $filters = [
            'scope' => in_array($input['scope'] ?? 'open', ['open', 'all'], true) ? ($input['scope'] ?? 'open') : 'open',
            'customer' => trim((string) ($input['customer'] ?? '')),
            'status' => trim((string) ($input['status'] ?? '')),
            'consultation_type' => trim((string) ($input['consultation_type'] ?? '')),
            'assigned_user_id' => trim((string) ($input['assigned_user_id'] ?? '')),
            'date_from' => trim((string) ($input['date_from'] ?? '')),
            'date_to' => trim((string) ($input['date_to'] ?? '')),
        ];
        return $filters;
    }
}
