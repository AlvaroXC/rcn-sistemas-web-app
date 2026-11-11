<?php

namespace RcnSistemas\Core;

use InvalidArgumentException;

final class Config
{
    private array $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->values;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function require(string $key): mixed
    {
        $value = $this->get($key);

        if ($value === null) {
            throw new InvalidArgumentException("La configuración '{$key}' no está definida.");
        }

        return $value;
    }
}
