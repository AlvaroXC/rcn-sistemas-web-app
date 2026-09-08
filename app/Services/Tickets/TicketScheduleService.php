<?php

namespace RcnSistemas\Services\Tickets;

use DateTimeImmutable;
use InvalidArgumentException;

final class TicketScheduleService
{
    public function validateAppointment(string $date, string $time): void
    {
        $appointment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
        if (!$appointment || $appointment->format('Y-m-d H:i') !== $date . ' ' . $time) {
            throw new InvalidArgumentException('La fecha y hora de la cita no son válidas.');
        }
    }

    public function durationMinutes(string $start, string $end): int
    {
        $startDate = DateTimeImmutable::createFromFormat('!H:i', $start);
        $endDate = DateTimeImmutable::createFromFormat('!H:i', $end);
        if (!$startDate || !$endDate || $endDate < $startDate) {
            throw new InvalidArgumentException('El horario de servicio no es válido.');
        }
        return (int) (($endDate->getTimestamp() - $startDate->getTimestamp()) / 60);
    }
}
