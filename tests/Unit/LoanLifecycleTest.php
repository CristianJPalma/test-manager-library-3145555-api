<?php
// T-05/T-06 evidencia: Loan 1-3 líneas, sin repetido, 14 días, open/overdue/closed con Clock falso.
// HU-06/HU-07/HU-08 + CA-02.2 ("Ana " -> "ana").

it('lends 1-3 lines without duplicates and computes due in 14 days', function () {
    $loanedAt = new DateTimeImmutable('2026-10-14T14:15:00Z');
    $loan = new \App\Domain\Loan('l1', 'm1', 'u1', $loanedAt);
    $b1 = new \App\Domain\Book('b1', 'T1', 'A', new \App\Domain\ValueObjects\Isbn('9780307474728'), 'c1', 2, 2);
    $b2 = new \App\Domain\Book('b2', 'T2', 'A', new \App\Domain\ValueObjects\Isbn('9780307474729'), 'c1', 2, 2);

    $loan->addItem(\App\Domain\LoanItem::fromLoan('i1', $b1, 'Novela', $loanedAt), $b1);
    expect($loan->items()[0]->dueAt->format('Y-m-d'))->toBe('2026-10-28');
    expect($b1->availableCopies)->toBe(1);

    expect(fn () => $loan->addItem(\App\Domain\LoanItem::fromLoan('iX', $b1, 'Novela', $loanedAt), $b1))
        ->toThrow(\DomainException::class, 'DUPLICATE_BOOK_IN_LOAN');

    $loan->addItem(\App\Domain\LoanItem::fromLoan('i2', $b2, 'Novela', $loanedAt), $b2);
    expect($loan->status($loanedAt))->toBe('open');
});

it('computes overdue/closed and never double-restocks', function () {
    $loanedAt = new DateTimeImmutable('2026-10-01T00:00:00Z');
    $loan = new \App\Domain\Loan('l1', 'm1', 'u1', $loanedAt);
    $b = new \App\Domain\Book('b1', 'T', 'A', new \App\Domain\ValueObjects\Isbn('9780307474728'), 'c1', 1, 1);
    $loan->addItem(\App\Domain\LoanItem::fromLoan('i1', $b, 'Novela', $loanedAt), $b);

    expect($loan->status(new DateTimeImmutable('2026-10-20T00:00:00Z')))->toBe('overdue');
    $loan->returnItem('i1', $b, new DateTimeImmutable('2026-10-20T00:00:00Z'));
    expect($loan->status(new DateTimeImmutable('2026-10-20T00:00:00Z')))->toBe('closed');
    expect($b->availableCopies)->toBe(1);
    expect(fn () => $loan->returnItem('i1', $b, new DateTimeImmutable('2026-10-20T00:00:00Z')))
        ->toThrow(\DomainException::class, 'ITEM_ALREADY_RETURNED');
});

it('normalizes usernames', function () {
    $u = new \App\Domain\User('u1', 'Ana ', 'hash', 'librarian');
    expect($u->username)->toBe('ana');
});
