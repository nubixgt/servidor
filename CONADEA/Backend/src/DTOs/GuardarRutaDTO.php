<?php
namespace App\DTOs;

class GuardarRutaDTO
{
    /** @param int[] $cursoIds */
    public function __construct(
        public string $icono,
        public string $titulo,
        public string $descripcion,
        public string $color,
        public array $cursoIds
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $cursoIds = array_map('intval', is_array($data['curso_ids'] ?? null) ? $data['curso_ids'] : []);

        return new self(
            trim($data['icono'] ?? ''),
            trim($data['titulo'] ?? ''),
            trim($data['descripcion'] ?? ''),
            trim($data['color'] ?? 'esmeralda'),
            array_values(array_unique(array_filter($cursoIds, fn($id) => $id > 0)))
        );
    }
}
