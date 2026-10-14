<?php

namespace App\Domain\ValueObjects;

// T-03: documento obligatorio. Lo omitían architecture/tasks,
// pero plan D-07 lo exige y member.document_number es único (HU-05.1).
final readonly class DocumentNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $v = trim($value);
        if ($v === '' || mb_strlen($v) > 30) {
            throw new \InvalidArgumentException('El documento es obligatorio (máx. 30).');
        }
        $this->value = $v;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
