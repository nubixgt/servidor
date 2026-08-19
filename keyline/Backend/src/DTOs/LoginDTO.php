<?php
namespace App\DTOs;

class LoginDTO
{
    public function __construct(
        public string $usuario,
        public string $password
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim((string)($data['usuario'] ?? '')),
            (string)($data['password'] ?? '')
        );
    }
}
