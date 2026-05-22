<?php
namespace App\Entities;

class MachineryLog
{
    public ?int $id = null;
    public int $maquina_id;
    public ?int $proyecto_id = null;
    public string $fecha;
    public int $horometro_inicial;
    public int $horometro_final;
    public ?float $combustible_consumido = null;
    public ?string $observaciones = null;
    public ?int $operador_id = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
