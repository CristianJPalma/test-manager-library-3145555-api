<?php

namespace App\Application\Ports\Outbound;

// T-07: puertos que el núcleo define, el mundo implementa (Art. II).
// Ningún tipo de framework/Eloquent en firmas.
interface UserRepository
{
    public function findByUsername(string $username): ?\App\Domain\User;
    public function save(\App\Domain\User $user): void;
}

interface PasswordHasher
{
    public function hash(string $plain): string;
    public function verify(string $plain, string $hash): bool;
}

interface TokenIssuer
{
    // JWT HS256 8h con sub+role, sin datos personales (api-contract §4).
    public function issue(string $userId, string $role): array; // ['access_token','token_type','expires_in','role']
}

interface Clock
{
    public function now(): \DateTimeImmutable;
}
