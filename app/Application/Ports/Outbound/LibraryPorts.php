<?php

namespace App\Application\Ports\Outbound;

// T-08/T-09: puertos de catálogo y préstamo (Art. II, sin framework).
interface BookRepository
{
    /** @return array<string, \App\Domain\Book> mapa id => Book */
    public function findMany(array $ids): array;
    public function save(\App\Domain\Book $book): void;
}

interface MemberRepository
{
    public function find(string $id): ?\App\Domain\Member;
}

interface LoanRepository
{
    public function save(\App\Domain\Loan $loan): void;
    /** Líneas sin devolver de un socio (Q7: cuenta y vencidos). */
    public function openItemsByMember(string $memberId): array; // LoanItem[]
}
