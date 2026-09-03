<?php
namespace App\Entities;

class Novedad
{
    public function __construct(
        public ?int $id = null,
        public string $titulo = '',
        public string $categoria = 'Noticias',
        public ?string $cuerpo = null,
        public ?string $portada_ruta = null,
        public ?string $pdf_ruta = null,
        public bool $fijado = false,
        public string $estado = 'borrador',
        public ?string $fecha_emision = null,
        public ?string $publicado_en = null,
        public ?int $autor_id = null
    ) {
    }
}
