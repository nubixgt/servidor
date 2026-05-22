<?php
namespace App\Entities;

class Project
{
    public ?int $id = null;
    public string $codigo;
    public string $nombre;
    public ?int $cliente_id = null;
    public ?string $ubicacion = null;
    public ?string $coordenadas = null;
    public float $presupuesto = 0;
    public string $fecha_inicio;
    public ?string $fecha_fin_estimada = null;
    public ?string $fecha_fin_real = null;
    public string $estado = 'Borrador';
    public ?string $numero_contrato = null;
    public ?string $descripcion = null;
    public ?string $contactos = null;
    public ?int $gerente_id = null;
    public ?string $foto = null;
    public ?string $contratos_archivos = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
