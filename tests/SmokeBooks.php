<?php
// Smoke T-08: crear duplicado 409, total inválido 422, cambio total sin negativo,
// baja con abiertos 409, portada 2MB/3MB y orden de borrado.
require __DIR__ . '/../app/Domain/ValueObjects/Isbn.php';
require __DIR__ . '/../app/Domain/Book.php';
require __DIR__ . '/../app/Application/Ports/Outbound/CatalogPorts.php';
require __DIR__ . '/../app/Application/Services/BookServices.php';

use App\Application\Services\CoverService;
use App\Application\Services\CreateBookService;
use App\Application\Services\DeleteBookService;
use App\Application\Services\UpdateBookService;
use App\Domain\Book;
use App\Domain\ValueObjects\Isbn;

$repo = new class implements \App\Application\Ports\Outbound\CatalogBookRepository {
    public array $b = [];
    public bool $open = false;
    public function find(string $id): ?Book { return $this->b[$id] ?? null; }
    public function findByIsbn(string $isbn): ?Book {
        foreach ($this->b as $x) {
            if ((string) $x->isbn === $isbn) {
                return $x;
            }
        }
        return null;
    }
    public function save(Book $b): void { $this->b[$b->id] = $b; }
    public function search(?string $q, ?string $c, int $p, int $s): array { return ['items' => [], 'total' => 0]; }
    public function hasOpenLoans(string $id): bool { return $this->open; }
};
$covers = new class implements \App\Application\Ports\Outbound\CoverStorage {
    public array $deleted = [];
    public function put(string $bytes, string $mime): string { return 'opaque-key-1'; }
    public function delete(string $key): void { $this->deleted[] = $key; }
};

$create = new CreateBookService($repo);
$b = $create->execute('T', 'A', '9780307474728', 'c1', 4);
echo 'created avail=' . $b->availableCopies . ' cover=' . var_export($b->coverKey, true) . PHP_EOL;
try {
    $create->execute('T2', 'A', '9780307474728', 'c1', 1);
    echo "FAIL isbn\n";
} catch (DomainException $e) { echo 'isbn=' . $e->getMessage() . PHP_EOL; }

$update = new UpdateBookService($repo);
$update->execute($b->id, 'T2', 'A', 'c1', 2);
echo 'updated total=2 avail=' . $b->availableCopies . PHP_EOL;
try {
    $update->execute($b->id, 'T2', 'A', 'c1', 10);
    $b->availableCopies = 0;
    $update->execute($b->id, 'T2', 'A', 'c1', 0);
} catch (DomainException | InvalidArgumentException $e) { echo 'total-neg=422-kind' . PHP_EOL; }

$del = new DeleteBookService($repo);
$repo->open = true;
try { $del->execute($b->id); echo "FAIL open\n"; } catch (DomainException $e) { echo 'open=' . $e->getMessage() . PHP_EOL; }
$repo->open = false;
$del->execute($b->id);
echo 'deleted=' . var_export($b->isDeleted(), true) . PHP_EOL;

$b2 = $create->execute('T3', 'A', '9780307474735', 'c1', 1);
$cov = new CoverService($repo, $covers);
echo 'cover=' . $cov->upload($b2->id, str_repeat('x', 1024), 'image/png') . PHP_EOL;
try { $cov->upload($b2->id, str_repeat('x', 3 * 1024 * 1024), 'image/png'); } catch (DomainException $e) { echo 'cover-3mb=' . $e->getMessage() . PHP_EOL; }
$cov->remove($b2->id);
echo 'cover-null=' . var_export($b2->coverKey, true) . ' bin-deleted=' . count($covers->deleted) . PHP_EOL;
