<?php
namespace App\Entities;

class Foto
{
    public function __construct(
        public ?int $id = null,
        public int $parcelaId = 0,
        public string $archivo = '',
        public ?string $miniatura = null,
        public string $caption = '',
        public string $subidoPor = '',
        public ?string $fecha = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'archivo' => $this->archivo,
            'miniatura' => $this->miniatura,
            'caption' => $this->caption,
            'subidoPor' => $this->subidoPor,
            'fecha' => $this->fecha,
        ];
    }
}
