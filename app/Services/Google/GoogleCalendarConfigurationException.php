<?php

namespace RcnSistemas\Services\Google;

use RuntimeException;

final class GoogleCalendarConfigurationException extends RuntimeException
{
    public function __construct(string $message = 'La configuración de Google Calendar requiere atención.')
    {
        parent::__construct($message, 500);
    }
}
