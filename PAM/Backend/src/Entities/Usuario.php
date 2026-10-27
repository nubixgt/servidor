<?php
namespace App\Entities;

class Usuario
{
    public function __construct(
        public int $id,
        public string $usuario,
        public string $nombre,
        public string $rol,
        public string $rolNombre,
        public array $permisos,
        public ?int $departamentoId = null,
        public ?int $municipioId = null,
        public bool $debeCambiarClave = false,
        public ?string $sesionId = null,
        public ?string $ip = null,
        public string $requestId = ''
    ) {
    }

    public function puede(string $permiso): bool
    {
        return in_array($permiso, $this->permisos, true);
    }

    public function toArray(): array
    {
        $permisos = $this->permisos;
        sort($permisos);
        return [
            'id' => $this->id,
            'usuario' => $this->usuario,
            'nombre' => $this->nombre,
            'rol' => $this->rol,
            'rol_nombre' => $this->rolNombre,
            'permisos' => $permisos,
            'departamento_id' => $this->departamentoId,
            'municipio_id' => $this->municipioId,
            'debe_cambiar_clave' => $this->debeCambiarClave,
        ];
    }
}
