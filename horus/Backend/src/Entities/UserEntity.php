<?php
namespace App\Entities;

class UserEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $role = null,
        public ?int $activo = 1
    ) {
    }
}
