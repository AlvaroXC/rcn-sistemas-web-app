<?php

namespace RcnSistemas\Services\Tickets;

use InvalidArgumentException;

final class TicketObservationService
{
    public function validate(string $content): string
    {
        $content = trim($content);
        if ($content === '') throw new InvalidArgumentException('La observación es obligatoria.');
        return $content;
    }
}
