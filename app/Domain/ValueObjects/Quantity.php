<?php

namespace App\Domain\ValueObjects;

// T-03: cantidad > 0. Sin tabla propia.
final readonly class Quantity
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }
    }
}
