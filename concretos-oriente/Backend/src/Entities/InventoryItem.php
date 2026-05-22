<?php
namespace App\Entities;

class InventoryItem
{
    public ?int $id = null;
    public string $tipo_item;
    public string $codigo_sku;
    public string $nombre;
    public ?string $descripcion = null;
    public string $unidad_medida;
    public ?string $codigo_qr = null;
    public ?string $codigo_barras = null;
    public float $costo_unitario = 0.00;
    public float $stock_minimo = 0.00;
    public float $stock_actual = 0.00;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
