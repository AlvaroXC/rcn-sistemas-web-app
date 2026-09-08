<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;

final class TicketPricing
{
    public function calculate(float $baseAmount, string $customerType = 'persona_fisica'): array
    {
        if ($baseAmount < 0) {
            throw new InvalidArgumentException('El precio base no puede ser negativo.');
        }
        if (!in_array($customerType, ['persona_fisica', 'persona_moral'], true)) {
            throw new InvalidArgumentException('El tipo de cliente no es válido.');
        }

        $iva = round($baseAmount * 0.16, 2);
        $withholding = $customerType === 'persona_moral' ? round($baseAmount * 0.0125, 2) : 0.0;

        return [
            'base' => round($baseAmount, 2),
            'iva' => $iva,
            'isr_withholding' => $withholding,
            'total' => round($baseAmount + $iva - $withholding, 2),
            'currency' => 'MXN',
        ];
    }
}
