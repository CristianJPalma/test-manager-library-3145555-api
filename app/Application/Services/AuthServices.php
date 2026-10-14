<?php

namespace App\Application\Services;

use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\TokenIssuer;
use App\Application\Ports\Outbound\UserRepository;

// T-07 HU-01: Login. CA-01.2 credenciales inválidas -> 401 INVALID_CREDENTIALS
// sin decir si falló usuario o clave. Solo el adaptador HTTP mapea a JSON.
final class LoginService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenIssuer $tokens,
    ) {
    }

    public function execute(string $username, string $password): array
    {
        $norm = mb_strtolower(trim($username));
        $user = $this->users->findByUsername($norm);
        if ($user === null || !$this->hasher->verify($password, $user->passwordHash)) {
            throw new \DomainException('INVALID_CREDENTIALS');
        }
        return $this->tokens->issue($user->id, $user->role);
    }
}

// T-07 HU-02: alta de bibliotecarios. CA-02.2 único 409, CA-02.3 pedir admin -> 422.
final class CreateLibrarianService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {
    }

    public function execute(string $username, string $password, string $role): \App\Domain\User
    {
        if ($role !== 'librarian') {
            throw new \DomainException('VALIDATION_ERROR'); // pedir admin por API -> 422
        }
        $norm = mb_strtolower(trim($username));
        if ($this->users->findByUsername($norm) !== null) {
            throw new \DomainException('USERNAME_TAKEN'); // 409
        }
        // La clave nunca se guarda en claro ni sale en respuestas/logs (RNF-3, Art. IX).
        $user = new \App\Domain\User(bin2hex(random_bytes(16)), $norm, $this->hasher->hash($password), 'librarian');
        $this->users->save($user);
        return $user;
    }
}
