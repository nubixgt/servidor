<?php
namespace App\Entities;

class RegistroMaquinaria
{
    public function __construct(
        public ?int $id = null,
        public string $operador,
        public string $maquina_id,
        public string $tipo_registro,
        public float $valor_horometro,
        public string $foto_horometro,
        public float $latitud,
        public float $longitud,
        public ?string $fecha_registro = null
    ) {
    }
}
