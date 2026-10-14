<?php

namespace App\Application\Services;

use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\MemberRepository;
use App\Domain\Loan;
use App\Domain\LoanItem;

// T-09 HU-06: prestar 1-3 líneas, un socio, lo registra el autenticado.
// CA-06.1 1-3, CA-06.2 sin repetido 422, CA-06.3 1 ejemplar por línea
//   sin stock 409 sin registrar nada, CA-06.4 máx 3 sin devolver 422,
// CA-06.5 con vencidos 409, CA-06.6 due+14d + congelados, CA-06.7 inmutable.
final class LendBooksService
{
    public function __construct(
        private MemberRepository $members,
        private BookRepository $books,
        private LoanRepository $loans,
        private Clock $clock,
    ) {
    }

    public function execute(string $memberId, array $bookIds, string $loanedByUserId): Loan
    {
        $n = count($bookIds);
        if ($n < 1 || $n > 3) {
            throw new \DomainException('VALIDATION_ERROR');
        }
        if (count(array_unique($bookIds)) !== $n) {
            throw new \DomainException('DUPLICATE_BOOK_IN_LOAN'); // 422
        }
        if ($this->members->find($memberId) === null) {
            throw new \DomainException('NOT_FOUND');
        }

        $now = $this->clock->now();
        $open = $this->loans->openItemsByMember($memberId);

        foreach ($open as $it) {
            if ($it->isOverdue($now)) {
                throw new \DomainException('MEMBER_HAS_OVERDUE'); // 409
            }
        }
        if (count($open) + $n > 3) {
            throw new \DomainException('MEMBER_LIMIT_EXCEEDED'); // 422 con conteo
        }

        $books = $this->books->findMany($bookIds);
        if (count($books) !== $n) {
            throw new \DomainException('NOT_FOUND'); // id inexistente o baja -> 404
        }

        // Sin ejemplares: 409 y no se registra nada (se valida antes de mutar).
        foreach ($bookIds as $id) {
            if ($books[$id]->availableCopies < 1 || $books[$id]->isDeleted()) {
                throw new \DomainException('BOOK_NOT_AVAILABLE');
            }
        }

        $loan = new Loan(bin2hex(random_bytes(16)), $memberId, $loanedByUserId, $now);
        foreach ($bookIds as $id) {
            // Categoría congelada: en Eloquent viene de book->category->name;
            // aquí el test la inyecta vía mapa. Nunca se lee del catálogo vivo en reportes.
            $loan->addItem(LoanItem::fromLoan(bin2hex(random_bytes(16)), $books[$id], $books[$id]->categoryId, $now), $books[$id]);
        }

        // Una transacción en el adaptador (Eloquent DB::transaction): préstamo + libros.
        foreach ($bookIds as $id) {
            $this->books->save($books[$id]);
        }
        $this->loans->save($loan);

        return $loan;
    }
}
