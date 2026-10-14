<?php
// T-09 evidencia HU-06 con fakes: límites, vencidos, sin stock atómico.

it('lends with limits, overdue block and atomic stock', function () {
    $now = new DateTimeImmutable('2026-10-14T00:00:00Z');
    $clock = new class($now) implements \App\Application\Ports\Outbound\Clock {
        public function __construct(private $n) {}
        public function now(): DateTimeImmutable { return $this->n; }
    };
    $mkBook = fn ($id, $av) => new \App\Domain\Book($id, 'T', 'A', new \App\Domain\ValueObjects\Isbn('9780307474728'), 'Novela', 2, $av);
    $member = new \App\Domain\Member('m1', 'Ana', new \App\Domain\ValueObjects\DocumentNumber('123'));

    $newCtx = function () use ($member, $mkBook, $clock) {
        $books = ['b1' => $mkBook('b1', 2), 'b2' => $mkBook('b2', 0)];
        $open = [];
        $saved = [];
        $members = new class($member) implements \App\Application\Ports\Outbound\MemberRepository {
            public function __construct(private $m) {}
            public function find(string $id): ?\App\Domain\Member { return $id === 'm1' ? $this->m : null; }
        };
        $bookRepo = new class($books) implements \App\Application\Ports\Outbound\BookRepository {
            public array $b;
            public function __construct(array $b) { $this->b = $b; }
            public function findMany(array $ids): array {
                return array_intersect_key($this->b, array_flip($ids));
            }
            public function save(\App\Domain\Book $book): void { $this->b[$book->id] = $book; }
        };
        $loanRepo = new class($open, $saved) implements \App\Application\Ports\Outbound\LoanRepository {
            public function __construct(public array $open, public array $saved) {}
            public function save(\App\Domain\Loan $l): void { $this->saved[] = $l; }
            public function openItemsByMember(string $m): array { return $this->open; }
        };
        return [$members, $bookRepo, $loanRepo, $clock];
    };

    // ok: 1 línea descuenta 1
    [$mm, $bb, $ll, $cc] = $newCtx();
    $svc = new \App\Application\Services\LendBooksService($mm, $bb, $ll, $cc);
    $loan = $svc->execute('m1', ['b1'], 'u1');
    expect($loan->items())->toHaveCount(1)->and($bb->b['b1']->availableCopies)->toBe(1);

    // sin ejemplares: 409 y no registra nada
    [$mm, $bb, $ll, $cc] = $newCtx();
    $svc = new \App\Application\Services\LendBooksService($mm, $bb, $ll, $cc);
    expect(fn () => $svc->execute('m1', ['b2'], 'u1'))->toThrow(\DomainException::class, 'BOOK_NOT_AVAILABLE');
    expect($ll->saved)->toHaveCount(0);

    // duplicado: 422
    [$mm, $bb, $ll, $cc] = $newCtx();
    $svc = new \App\Application\Services\LendBooksService($mm, $bb, $ll, $cc);
    expect(fn () => $svc->execute('m1', ['b1', 'b1'], 'u1'))->toThrow(\DomainException::class, 'DUPLICATE_BOOK_IN_LOAN');

    // límite 3: 422
    [$mm, $bb, $ll, $cc] = $newCtx();
    $ll->open = [
        new \App\Domain\LoanItem('o1', 'bx', 'T', '9780307474728', 'Novela', $now->modify('+1 day')),
        new \App\Domain\LoanItem('o2', 'by', 'T', '9780307474728', 'Novela', $now->modify('+1 day')),
        new \App\Domain\LoanItem('o3', 'bz', 'T', '9780307474728', 'Novela', $now->modify('+1 day')),
    ];
    $svc = new \App\Application\Services\LendBooksService($mm, $bb, $ll, $cc);
    expect(fn () => $svc->execute('m1', ['b1'], 'u1'))->toThrow(\DomainException::class, 'MEMBER_LIMIT_EXCEEDED');

    // vencido: 409
    [$mm, $bb, $ll, $cc] = $newCtx();
    $ll->open = [new \App\Domain\LoanItem('o1', 'bx', 'T', '9780307474728', 'Novela', $now->modify('-1 day'))];
    $svc = new \App\Application\Services\LendBooksService($mm, $bb, $ll, $cc);
    expect(fn () => $svc->execute('m1', ['b1'], 'u1'))->toThrow(\DomainException::class, 'MEMBER_HAS_OVERDUE');
});
