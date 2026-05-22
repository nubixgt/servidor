<?php
namespace App\Entities;

class BudgetItem
{
    public ?int $id = null;
    public int $project_id;
    public string $nombre_partida;
    public string $categoria;
    public string $unidad_medida;
    public float $cantidad_estimada = 0.0;
    public float $precio_unitario = 0.0;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
