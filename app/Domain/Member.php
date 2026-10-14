<?php

namespace App\Domain;

use App\Domain\ValueObjects\DocumentNumber;

// T-06: Member — documento obligatorio y único (unicidad la impone la base, HU-05.1).
final class Member
{
    public function __construct(
        public readonly string $id,
        public string $fullName,
        public DocumentNumber $documentNumber,
    ) {
        if (trim($fullName) === '') {
            throw new \InvalidArgumentException('El nombre del socio es obligatorio.');
        }
    }
}
