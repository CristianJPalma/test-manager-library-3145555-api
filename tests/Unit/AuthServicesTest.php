<?php
// T-07 evidencia con fakes: HU-01 login 8h + 401 genérico, HU-02 alta 409/422.
// Los tests de aplicación corren sin Infrastructure (Art. II).

it('logs in and issues 8h token, generic 401 otherwise', function () {
    $user = new \App\Domain\User('u1', 'Ana ', 'hashed', 'librarian');

    $users = new class($user) implements \App\Application\Ports\Outbound\UserRepository {
        public function __construct(private $u) {}
        public function findByUsername(string $u): ?\App\Domain\User { return $u === 'ana' ? $this->u : null; }
        public function save(\App\Domain\User $u): void {}
    };
    $hasher = new class implements \App\Application\Ports\Outbound\PasswordHasher {
        public function hash(string $p): string { return 'hashed'; }
        public function verify(string $p, string $h): bool { return $p === 'ok' && $h === 'hashed'; }
    };
    $tokens = new class implements \App\Application\Ports\Outbound\TokenIssuer {
        public function issue(string $id, string $r): array {
            return ['access_token' => 'jwt', 'token_type' => 'Bearer', 'expires_in' => 28800, 'role' => $r];
        }
    };

    $svc = new \App\Application\Services\LoginService($users, $hasher, $tokens);
    expect($svc->execute('ANA ', 'ok')['expires_in'])->toBe(28800);
    expect(fn () => $svc->execute('ANA ', 'bad'))->toThrow(\DomainException::class, 'INVALID_CREDENTIALS');
    expect(fn () => $svc->execute('nadie', 'ok'))->toThrow(\DomainException::class, 'INVALID_CREDENTIALS');
});

it('rejects duplicate username and admin role', function () {
    $users = new class implements \App\Application\Ports\Outbound\UserRepository {
        public function findByUsername(string $u): ?\App\Domain\User {
            return $u === 'ana' ? new \App\Domain\User('u1', 'ana', 'h', 'librarian') : null;
        }
        public function save(\App\Domain\User $u): void {}
    };
    $hasher = new class implements \App\Application\Ports\Outbound\PasswordHasher {
        public function hash(string $p): string { return 'hashed'; }
        public function verify(string $p, string $h): bool { return true; }
    };
    $svc = new \App\Application\Services\CreateLibrarianService($users, $hasher);
    expect(fn () => $svc->execute('ana', 'x', 'librarian'))->toThrow(\DomainException::class, 'USERNAME_TAKEN');
    expect(fn () => $svc->execute('bob', 'x', 'admin'))->toThrow(\DomainException::class, 'VALIDATION_ERROR');
    expect($svc->execute(' Bob ', 'x', 'librarian')->username)->toBe('bob');
});
