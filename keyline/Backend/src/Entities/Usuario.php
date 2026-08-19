<?php
namespace App\Entities;

class Usuario
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = '',
        public string $usuario = '',
        public ?string $email = null,
        public string $passwordHash = '',
        public string $role = 'tecnico',
        public ?string $regionAsignada = null,
        public ?string $telefono = null,
        public bool $activo = true,
        public ?string $createdAt = null,
        public ?string $ultimoAcceso = null,
    ) {
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'role' => $this->role,
            'regionAsignada' => $this->regionAsignada,
            'telefono' => $this->telefono,
            'activo' => $this->activo,
            'createdAt' => $this->createdAt,
            'ultimoAcceso' => $this->ultimoAcceso,
        ];
    }
}
