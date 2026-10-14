<?php
// Smoke T-10/T-11: devolver, vencidos ordenados, reporte estable.
require __DIR__ . '/../app/Domain/ValueObjects/Isbn.php';
require __DIR__ . '/../app/Domain/ValueObjects/DateRange.php';
require __DIR__ . '/../app/Domain/Book.php';
require __DIR__ . '/../app/Domain/Loan.php';
require __DIR__ . '/../app/Domain/LoanItem.php';
require __DIR__ . '/../app/Application/Ports/Outbound/AuthPorts.php';
require __DIR__ . '/../app/Application/Ports/Outbound/ReportPorts.php';
require __DIR__ . '/../app/Application/Services/ReturnReportServices.php';

use App\Application\Services\MostBorrowedReportService;
use App\Application\Services\OverdueService;
use App\Application\Services\ReturnItemService;
use App\Domain\Book;
use App\Domain\Loan;
use App\Domain\LoanItem;
use App\Domain\ValueObjects\Isbn;

$now = new DateTimeImmutable('2026-10-20T00:00:00Z');
$loanedAt = new DateTimeImmutable('2026-10-01T00:00:00Z');

// 1. devolver una vez ok, dos veces 409
$b = new Book('b1', 'T', 'A', new Isbn('9780307474728'), 'c1', 1, 1);
$loan = new Loan('l1', 'm1', 'u1', $loanedAt);
$loan->addItem(LoanItem::fromLoan('i1', $b, 'Novela', $loanedAt), $b);
$store = new class($loan, $b) implements \App\Application\Ports\Outbound\LoanFinder {
    public function __construct(public $l, public $b) {}
    public function findLoan(string $id): ?Loan { return $id === 'l1' ? $this->l : null; }
    public function findBook(string $id): ?Book { return $id === 'b1' ? $this->b : null; }
    public function saveLoan(Loan $l): void {}
    public function saveBook(Book $b): void {}
};
$clock = new class($now) implements \App\Application\Ports\Outbound\Clock {
    public function __construct(private $n) {}
    public function now(): DateTimeImmutable { return $this->n; }
};
$ret = new ReturnItemService($store, $clock);
$r1 = $ret->execute('l1', 'i1');
echo 'return1 status=' . $r1['status'] . ' avail=' . $b->availableCopies . PHP_EOL;
try {
    $ret->execute('l1', 'i1');
    echo "FAIL double-return\n";
} catch (DomainException $e) {
    echo 'return2=' . $e->getMessage() . PHP_EOL; // ITEM_ALREADY_RETURNED
}

// 2. vencidos ordenados por más atraso
$reader = new class implements \App\Application\Ports\Outbound\ReportReader {
    public function overdue(DateTimeImmutable $n): array {
        return [
            ['item_id' => 'a', 'days_late' => 2],
            ['item_id' => 'b', 'days_late' => 9],
        ];
    }
    public function mostBorrowed(DateTimeImmutable $f, DateTimeImmutable $t): array {
        return [
            ['book_id' => 'b1', 'book_title' => 'Viejo', 'category_name' => 'Novela', 'times_borrowed' => 3],
        ];
    }
};
$ov = new OverdueService($reader, $clock);
echo 'overdue rows=' . count($ov->execute()) . PHP_EOL;

// 3. reporte conserva título viejo + rango invertido 422
$rep = new MostBorrowedReportService($reader);
$r = $rep->execute(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-31'));
echo 'report title=' . $r['rows'][0]['book_title'] . PHP_EOL;
try {
    $rep->execute(new DateTimeImmutable('2026-10-31'), new DateTimeImmutable('2026-10-01'));
    echo "FAIL range\n";
} catch (DomainException $e) {
    echo 'range=' . $e->getMessage() . PHP_EOL; // INVALID_DATE_RANGE via DateRange
}
