<?php
namespace App\DTOs;

class UsuarioDTO
{
    public function __construct(
        public string $nombre = '',
        public string $usuario = '',
        public string $email = '',
        public ?string $password = null,
        public string $role = 'tecnico',
        public string $regionAsignada = '',
        public string $telefono = '',
        public ?bool $activo = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim((string)($data['nombre'] ?? '')),
            trim((string)($data['usuario'] ?? '')),
            trim((string)($data['email'] ?? '')),
            isset($data['password']) && $data['password'] !== '' ? (string)$data['password'] : null,
            (string)($data['role'] ?? 'tecnico'),
            trim((string)($data['regionAsignada'] ?? '')),
            trim((string)($data['telefono'] ?? '')),
            array_key_exists('activo', $data) ? (bool)$data['activo'] : null,
        );
    }
}
