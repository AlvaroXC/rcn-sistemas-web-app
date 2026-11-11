<?php

namespace RcnSistemas\Services;

use InvalidArgumentException;

final class PromotionService
{
    public function validate(array $data): void
    {
        $title = trim((string) ($data['title'] ?? ''));
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $endDate = trim((string) ($data['end_date'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('El título de la promoción es obligatorio.');
        }

        if (!$this->validDate($startDate) || !$this->validDate($endDate)) {
            throw new InvalidArgumentException('Las fechas de la promoción no son válidas.');
        }

        if ($startDate > $endDate) {
            throw new InvalidArgumentException('La fecha de inicio no puede ser posterior a la fecha final.');
        }
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
