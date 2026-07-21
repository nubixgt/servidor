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
            trim($data['usuario'] ?? ''),
            $data['password'] ?? ''
        );
    }
}
