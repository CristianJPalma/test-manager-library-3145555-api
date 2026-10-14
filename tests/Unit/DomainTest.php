<?php
// T-03/T-04 evidencia: VOs + Book sin base ni framework.
// CA-03.2 ISBN 13 dígitos, RN-1 rango, CA-03.3 total.

it('validates value objects', function () {
    expect(fn () => new \App\Domain\ValueObjects\Isbn('123'))->toThrow(\InvalidArgumentException::class);
    expect((string) new \App\Domain\ValueObjects\Isbn('9780307474728'))->toBe('9780307474728');
    expect(fn () => new \App\Domain\ValueObjects\Quantity(0))->toThrow(\InvalidArgumentException::class);
    expect(fn () => new \App\Domain\ValueObjects\DocumentNumber('  '))->toThrow(\InvalidArgumentException::class);
    expect(
        fn () => new \App\Domain\ValueObjects\DateRange(
            new DateTimeImmutable('2026-10-31'),
            new DateTimeImmutable('2026-10-01')
        )
    )->toThrow(\InvalidArgumentException::class);
});

it('never goes below 0 nor above total', function () {
    $b = new \App\Domain\Book('id-1', 'Cien años', 'García', new \App\Domain\ValueObjects\Isbn('9780307474728'), 'cat-1', 1, 1);
    $b->withdraw();
    expect($b->availableCopies)->toBe(0);
    expect(fn () => $b->withdraw())->toThrow(\DomainException::class); // BOOK_NOT_AVAILABLE -> 409
    $b->restock();
    expect($b->availableCopies)->toBe(1);
    expect(fn () => $b->restock())->toThrow(\DomainException::class); // nunca > total
});
