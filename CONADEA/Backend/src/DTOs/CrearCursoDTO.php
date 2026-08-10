<?php
namespace App\DTOs;

class CrearCursoDTO
{
    /**
     * @param array $lecciones  [['titulo' => string, 'contenido' => string], ...]
     * @param array $quiz       [['pregunta' => string, 'opciones' => [['texto' => string, 'es_correcta' => bool], ...]], ...]
     */
    public function __construct(
        public string $icono,
        public string $titulo,
        public string $descripcion,
        public string $imagenUrl,
        public array $lecciones,
        public array $quiz
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim($data['icono'] ?? ''),
            trim($data['titulo'] ?? ''),
            trim($data['descripcion'] ?? ''),
            trim($data['imagen_url'] ?? ''),
            is_array($data['lecciones'] ?? null) ? $data['lecciones'] : [],
            is_array($data['quiz'] ?? null) ? $data['quiz'] : []
        );
    }
}
