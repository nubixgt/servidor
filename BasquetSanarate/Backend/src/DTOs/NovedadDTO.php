<?php
namespace App\DTOs;

class NovedadDTO
{
    public function __construct(
        public string $titulo,
        public string $categoria,
        public ?string $cuerpo,
        public bool $fijado,
        public string $estado,
        public ?string $fecha_emision
    ) {
    }

    public static function fromRequest(array $d): self
    {
        return new self(
            trim($d['titulo'] ?? ''),
            isset($d['categoria']) && trim($d['categoria']) !== '' ? trim($d['categoria']) : 'Noticias',
            isset($d['cuerpo']) && $d['cuerpo'] !== '' ? $d['cuerpo'] : null,
            !empty($d['fijado']),
            in_array($d['estado'] ?? '', ['borrador', 'publicado'], true) ? $d['estado'] : 'borrador',
            isset($d['fecha_emision']) && $d['fecha_emision'] !== '' ? $d['fecha_emision'] : null
        );
    }
}
