<?php

namespace RcnSistemas\Services\Google;

use RuntimeException;

final class GoogleCalendarAuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'La conexión de Google requiere reconexión.')
    {
        parent::__construct($message, 401);
    }
}
