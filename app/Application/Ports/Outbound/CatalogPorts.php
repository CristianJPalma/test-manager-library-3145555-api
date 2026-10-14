<?php

namespace App\Application\Ports\Outbound;

// T-08: puertos de catálogo (Art. II).
interface CatalogBookRepository
{
    public function find(string $id): ?\App\Domain\Book;
    public function findByIsbn(string $isbn): ?\App\Domain\Book;
    public function save(\App\Domain\Book $book): void;
    // Q1: texto parcial título/autor, categoría, solo activos, orden título, page/size<=50 + total.
    public function search(?string $q, ?string $categoryId, int $page, int $size): array; // ['items'=>Book[],'total'=>int]
    public function hasOpenLoans(string $bookId): bool;
}

interface CoverStorage
{
    // Guarda binario y devuelve clave opaca. Valida image/jpeg|png por contenido, máx 2MB.
    // Sin portada -> null, nunca "". Solo el binario se borra físico (data-model §7).
    public function put(string $bytes, string $mime): string;
    public function delete(string $key): void;
}
