<?php

namespace App\Domain\ValueObjects;

// T-03: ISBN 13 dígitos. Vive en la fila de su dueño, sin tabla (plan D-07).
final readonly class Isbn
{
    public function __construct(public string $value)
    {
        $v = trim($value);
        if (!preg_match('/^\d{13}$/', $v)) {
            throw new \InvalidArgumentException('ISBN debe tener 13 dígitos.');
        }
        $this->value = $v;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
