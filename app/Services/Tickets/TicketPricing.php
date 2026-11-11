<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;
use RcnSistemas\Services\Customers\CustomerCatalog;

final class TicketPricing
{
    private readonly array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require dirname(__DIR__, 3) . '/config/billing.php';
    }

    public function calculate(float $baseAmount, string $customerType = 'persona_fisica'): array
    {
        if ($baseAmount < 0) {
            throw new InvalidArgumentException('El precio base no puede ser negativo.');
        }
        if (!CustomerCatalog::isValid($customerType)) {
            throw new InvalidArgumentException('El tipo de cliente no es válido.');
        }

        $iva = round($baseAmount * (float) $this->config['iva_rate'], 2);
        $withholding = $customerType === 'persona_moral' ? round($baseAmount * (float) $this->config['isr_withholding_rate'], 2) : 0.0;

        return [
            'base' => round($baseAmount, 2),
            'iva' => $iva,
            'isr_withholding' => $withholding,
            'total' => round($baseAmount + $iva - $withholding, 2),
            'currency' => (string) $this->config['currency'],
        ];
    }
}
