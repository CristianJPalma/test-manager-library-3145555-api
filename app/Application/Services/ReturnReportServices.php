<?php

namespace App\Application\Services;

use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanFinder;
use App\Application\Ports\Outbound\ReportReader;
use App\Domain\ValueObjects\DateRange;

// T-10 HU-07: devolver una línea. Pone returned_at +1 disponible.
// Ya devuelta -> 409 ITEM_ALREADY_RETURNED, nunca suma dos veces.
final class ReturnItemService
{
    public function __construct(private LoanFinder $store, private Clock $clock) {}

    public function execute(string $loanId, string $itemId): array
    {
        $loan = $this->store->findLoan($loanId) ?? throw new \DomainException('NOT_FOUND');
        $now = $this->clock->now();

        // Libro de la línea para restock (barrera: jamás supera total, la pone Book).
        $target = null;
        foreach ($loan->items() as $it) {
            if ($it->id === $itemId) {
                $target = $it;
                break;
            }
        }
        if ($target === null) {
            throw new \DomainException('NOT_FOUND');
        }
        $book = $this->store->findBook($target->bookId) ?? throw new \DomainException('NOT_FOUND');
        $loan->returnItem($itemId, $book, $now);

        $this->store->saveBook($book);
        $this->store->saveLoan($loan);

        return ['item_id' => $itemId, 'returned_at' => $now->format(DATE_ATOM), 'status' => $loan->status($now)];
    }
}

// T-10 HU-09: vencidos. Solo admin/librarian (lo aplica HTTP).
final class OverdueService
{
    public function __construct(private ReportReader $reader, private Clock $clock) {}

    public function execute(): array
    {
        return $this->reader->overdue($this->clock->now());
    }
}

// T-11 HU-10: más prestados. Rango obligatorio, fin<inicio 422,
// vacío sin error, estable ante renombres (usa congelados), solo admin.
final class MostBorrowedReportService
{
    public function __construct(private ReportReader $reader) {}

    public function execute(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        try {
            new DateRange($from, $to); // lanza si invertido -> 422 INVALID_DATE_RANGE en HTTP
            $rows = $this->reader->mostBorrowed($from, $to);
        } catch (\InvalidArgumentException $e) {
            throw new \DomainException('INVALID_DATE_RANGE');
        }
        return ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'rows' => $rows];
    }
}
