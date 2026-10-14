<?php

namespace App\Domain;

// T-06: User — nombre normalizado (minúsculas + recorte, CA-02.2), rol válido.
// La regla "nadie otorga admin por API (422)" la aplica el caso de uso; aquí se defiende el valor.
final class User
{
    public readonly string $username;

    public function __construct(
        public readonly string $id,
        string $username,
        public readonly string $passwordHash,
        public readonly string $role,
    ) {
        $norm = mb_strtolower(trim($username));
        if ($norm === '') {
            throw new \InvalidArgumentException('El nombre de usuario es obligatorio.');
        }
        if (!in_array($role, ['admin', 'librarian'], true)) {
            throw new \InvalidArgumentException('Rol inválido.');
        }
        $this->username = $norm;
    }
}
