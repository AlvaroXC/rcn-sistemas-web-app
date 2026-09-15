<?php

namespace RcnSistemas\Services\Tickets;

use DateTimeImmutable;
use InvalidArgumentException;

final class TicketScheduleService
{
    public function validateAppointment(string $date, string $time): void
    {
        $date = trim($date);
        $time = trim($time);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $time)) {
            throw new InvalidArgumentException('La fecha y hora de la cita no son válidas.');
        }

        $time = substr($time, 0, 5);
        $value = $date . ' ' . $time;
        $appointment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$appointment || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $appointment->format('Y-m-d H:i') !== $value) {
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
