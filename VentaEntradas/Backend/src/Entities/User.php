<?php
namespace App\Entities;

class User
{
    public function __construct(
        public int $id,
        public string $username,
        public string $passwordHash,
        public string $fullName,
        public string $role,
        public bool $active,
        public string $createdAt
    ) {
    }

    /** Datos públicos (nunca incluye el hash). */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'fullName' => $this->fullName,
            'role' => $this->role,
            'active' => $this->active,
            'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z', strtotime($this->createdAt . ' UTC')),
        ];
    }
}
