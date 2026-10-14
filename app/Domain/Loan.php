<?php

namespace App\Domain;

// T-05: Loan — AddItem/ReturnItem, 1-3 líneas, sin repetido, inmutable salvo devolución.
// Estado calculado (Art. VII): open/overdue/closed. $now se inyecta (Clock falso en tests).
final class Loan
{
    /** @var LoanItem[] */
    private array $items = [];

    public function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly \DateTimeImmutable $loanedAt,
    ) {
    }

    // Descontar ejemplar y crear línea son una operación: AddItem -> Book::withdraw.
    public function addItem(LoanItem $item, Book $book): void
    {
        if (count($this->items) >= 3) {
            throw new \DomainException('MEMBER_LIMIT_EXCEEDED');
        }
        foreach ($this->items as $it) {
            if ($it->bookId === $item->bookId) {
                throw new \DomainException('DUPLICATE_BOOK_IN_LOAN');
            }
        }
        $book->withdraw();
        $this->items[] = $item;
    }

    public function returnItem(string $itemId, Book $book, \DateTimeImmutable $now): void
    {
        foreach ($this->items as $it) {
            if ($it->id === $itemId) {
                $it->return($now);
                $book->restock(); // una sola vez: return() lanza si ya estaba devuelta
                return;
            }
        }
        throw new \DomainException('NOT_FOUND');
    }

    /** @return LoanItem[] */
    public function items(): array
    {
        return $this->items;
    }

    public function openCount(): int
    {
        return count(array_filter($this->items, fn ($i) => !$i->isReturned()));
    }

    // CA-08.1: open (sin devolver y ninguna vencida), overdue (alguna vencida), closed (todas devueltas).
    public function status(\DateTimeImmutable $now): string
    {
        if ($this->items === []) {
            throw new \DomainException('Préstamo sin líneas.');
        }
        if ($this->openCount() === 0) {
            return 'closed';
        }
        foreach ($this->items as $it) {
            if ($it->isOverdue($now)) {
                return 'overdue';
            }
        }
        return 'open';
    }
}
