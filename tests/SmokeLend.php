<?php
// Smoke funcional T-05/T-09 sin vendor: php tests/SmokeLend.php
require __DIR__ . '/../app/Domain/ValueObjects/Isbn.php';
require __DIR__ . '/../app/Domain/Book.php';
require __DIR__ . '/../app/Domain/Loan.php';
require __DIR__ . '/../app/Domain/LoanItem.php';

use App\Domain\Book;
use App\Domain\Loan;
use App\Domain\LoanItem;
use App\Domain\ValueObjects\Isbn;

$b = new Book('b1', 'T', 'A', new Isbn('9780307474728'), 'Novela', 1, 1);
$l = new Loan('l1', 'm1', 'u1', new DateTimeImmutable('2026-10-14T00:00:00Z'));
$l->addItem(LoanItem::fromLoan('i1', $b, 'Novela', new DateTimeImmutable('2026-10-14T00:00:00Z')), $b);
echo 'avail=' . $b->availableCopies . ' status=' . $l->status(new DateTimeImmutable('2026-10-14T00:00:00Z')) . PHP_EOL;
