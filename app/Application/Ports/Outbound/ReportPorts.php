<?php

namespace App\Application\Ports\Outbound;

// T-10/T-11: lectura para vencidos y reporte (ADR-004: agregada, congelados, sin FK).
interface ReportReader
{
    // Vencidos globales: líneas sin devolver con due_at < now.
    // [{loan_id,item_id,book_title,member_name,due_at,days_late}] orden más atraso primero.
    // Aquí sí va member_name (operativo); el reporte §3.7 no (privacidad).
    public function overdue(\DateTimeImmutable $now): array;

    // Reporte: una fila por book_id+book_title+category_name congelados,
    // times_borrowed desc luego título. Sin socios ni bibliotecarios.
    public function mostBorrowed(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
}

interface LoanFinder
{
    public function findLoan(string $id): ?\App\Domain\Loan;
    public function findBook(string $id): ?\App\Domain\Book;
    public function saveLoan(\App\Domain\Loan $loan): void;
    public function saveBook(\App\Domain\Book $book): void;
}
