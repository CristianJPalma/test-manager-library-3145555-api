<?php

namespace App\Domain;

use App\Domain\ValueObjects\Isbn;

// T-04: Book — Withdraw/Restock + baja lógica.
// RN-1: 0 <= disponibles <= total. Total >= 1. Título no vacío.
// Descontar y crear línea son una operación (la orquesta Loan.AddItem).
final class Book
{
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        public readonly string $id,
        public string $title,
        public string $author,
        public Isbn $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey = null,
    ) {
        $title = trim($title);
        if ($title === '') {
            throw new \InvalidArgumentException('El título no puede estar vacío.');
        }
        if ($totalCopies < 1) {
            throw new \InvalidArgumentException('El total debe ser mayor a 0.');
        }
        if ($availableCopies < 0 || $availableCopies > $totalCopies) {
            throw new \InvalidArgumentException('Disponibles fuera de rango.');
        }
        $this->title = $title;
    }

    public function withdraw(): void
    {
        $this->assertActive();
        if ($this->availableCopies < 1) {
            throw new \DomainException('BOOK_NOT_AVAILABLE');
        }
        $this->availableCopies--;
    }

    public function restock(): void
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new \DomainException('Disponibles no pueden superar el total.');
        }
        $this->availableCopies++;
    }

    // CA-03.3: cambiar total ajusta disponibles en la misma diferencia, nunca < 0.
    public function changeTotal(int $newTotal): void
    {
        if ($newTotal < 1) {
            throw new \InvalidArgumentException('El total debe ser mayor a 0.');
        }
        $diff = $newTotal - $this->totalCopies;
        if ($this->availableCopies + $diff < 0) {
            throw new \DomainException('No puede dejar disponibles en negativo.');
        }
        $this->totalCopies = $newTotal;
        $this->availableCopies += $diff;
    }

    public function delete(): void
    {
        // CA-03.5: la regla "con préstamos abiertos no se da de baja (409)"
        // la verifica el caso de uso; aquí solo se marca.
        $this->deletedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function assertActive(): void
    {
        if ($this->isDeleted()) {
            throw new \DomainException('BOOK_DELETED');
        }
    }
}
