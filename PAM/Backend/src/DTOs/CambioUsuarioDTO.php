<?php
namespace App\DTOs;

/**
 * Cambio parcial: solo se aplican los campos que vienen en la petición.
 */
class CambioUsuarioDTO
{
    public const CAMPOS = ['nombre', 'correo', 'rol_id', 'departamento_id', 'municipio_id', 'activo', 'ambito_nacional'];

    public function __construct(
        public array $campos
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(array_intersect_key($data, array_flip(self::CAMPOS)));
    }
}
