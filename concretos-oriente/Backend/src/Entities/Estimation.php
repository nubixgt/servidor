<?php
namespace App\Entities;

class Estimation
{
    public ?int $id = null;
    public int $project_id;
    public string $periodo;
    public ?string $observaciones = null;
    public string $estado = 'En Revisión';
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
