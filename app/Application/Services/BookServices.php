<?php

namespace App\Application\Services;

use App\Application\Ports\Outbound\CatalogBookRepository;
use App\Application\Ports\Outbound\CoverStorage;
use App\Domain\Book;
use App\Domain\ValueObjects\Isbn;

// T-08 HU-03/HU-04 con fakes. HTTP mapea a 409/422/404 y cuerpo único.
final class CreateBookService
{
    public function __construct(private CatalogBookRepository $books) {}

    public function execute(string $title, string $author, string $isbn, string $categoryId, int $total): Book
    {
        $isbnVo = new Isbn($isbn); // 13 dígitos o VALIDATION_ERROR -> 422
        if ($total < 1) {
            throw new \DomainException('VALIDATION_ERROR');
        }
        // ISBN único entre activos: si existe y no está dado de baja -> 409.
        $found = $this->books->findByIsbn((string) $isbnVo);
        if ($found !== null && !$found->isDeleted()) {
            throw new \DomainException('ISBN_TAKEN');
        }
        $b = new Book(bin2hex(random_bytes(16)), $title, $author, $isbnVo, $categoryId, $total, $total);
        $this->books->save($b);
        return $b;
    }
}

final class UpdateBookService
{
    public function __construct(private CatalogBookRepository $books) {}

    // PUT reemplaza título, autor, categoría y total. ISBN inmutable.
    public function execute(string $id, string $title, string $author, string $categoryId, int $total): Book
    {
        $b = $this->books->find($id) ?? throw new \DomainException('NOT_FOUND');
        if ($b->isDeleted()) {
            throw new \DomainException('NOT_FOUND'); // baja -> 404
        }
        $b->title = trim($title) !== '' ? trim($title) : throw new \DomainException('VALIDATION_ERROR');
        $b->author = $author;
        $b->categoryId = $categoryId;
        $b->changeTotal($total); // nunca disponibles < 0 o 422
        $this->books->save($b);
        return $b;
    }
}

final class DeleteBookService
{
    public function __construct(private CatalogBookRepository $books) {}

    // CA-03.5 baja lógica; con abiertos 409. Sigue en préstamos pasados.
    public function execute(string $id): void
    {
        $b = $this->books->find($id) ?? throw new \DomainException('NOT_FOUND');
        if ($b->isDeleted()) {
            throw new \DomainException('NOT_FOUND');
        }
        if ($this->books->hasOpenLoans($id)) {
            throw new \DomainException('BOOK_HAS_OPEN_LOANS');
        }
        $b->delete();
        $this->books->save($b);
    }
}

final class CoverService
{
    public function __construct(private CatalogBookRepository $books, private CoverStorage $covers) {}

    public function upload(string $bookId, string $bytes, string $mime): string
    {
        $b = $this->books->find($bookId) ?? throw new \DomainException('NOT_FOUND');
        if (!in_array($mime, ['image/jpeg', 'image/png'], true) || strlen($bytes) > 2 * 1024 * 1024) {
            throw new \DomainException('VALIDATION_ERROR'); // tipo por contenido, máx 2MB -> 422
        }
        $key = $this->covers->put($bytes, $mime); // clave opaca
        $b->coverKey = $key;
        $this->books->save($b);
        return $key;
    }

    public function remove(string $bookId): void
    {
        // Orden obligatorio data-model §7: primero anular cover_key + commit, después borrar binario.
        $b = $this->books->find($bookId) ?? throw new \DomainException('NOT_FOUND');
        $key = $b->coverKey ?? null;
        $b->coverKey = null;
        $this->books->save($b);
        if ($key !== null) {
            $this->covers->delete($key);
        }
    }
}
