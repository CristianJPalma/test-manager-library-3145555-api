<?php

namespace App\Domain;

// T-05: línea de préstamo. Congela title/isbn/category (RN-4, ADR-004).
// due = loaned + 14 días. Solo cambia returned_at.
final class LoanItem
{
    public ?\DateTimeImmutable $returnedAt = null;

    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly \DateTimeImmutable $dueAt,
    ) {
    }

    public static function fromLoan(string $id, Book $book, string $categoryName, \DateTimeImmutable $loanedAt): self
    {
        return new self(
            $id,
            $book->id,
            $book->title,
            (string) $book->isbn,
            $categoryName,
            $loanedAt->modify('+14 days'),
        );
    }

    public function return(\DateTimeImmutable $now): void
    {
        if ($this->returnedAt !== null) {
            throw new \DomainException('ITEM_ALREADY_RETURNED');
        }
        $this->returnedAt = $now;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdue(\DateTimeImmutable $now): bool
    {
        return !$this->isReturned() && $this->dueAt < $now;
    }
}
