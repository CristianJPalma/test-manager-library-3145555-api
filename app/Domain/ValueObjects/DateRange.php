<?php

namespace App\Domain\ValueObjects;

// T-03: fin >= inicio. Para rangos from/to del reporte (CA-10.2: fin anterior = 422).
final readonly class DateRange
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
        if ($to < $from) {
            throw new \InvalidArgumentException('El fin no puede ser anterior al inicio.');
        }
    }
}
