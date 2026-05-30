<?php
namespace App\Entities;

class Piloto
{
    public $id;
    public $nombre;
    public $telefono;
    public $status;
    public $maquinas;
    public $created_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
        $this->telefono = $data['telefono'] ?? null;
        $this->status = $data['status'] ?? 'activo';
        $this->created_at = $data['created_at'] ?? null;
        
        if (isset($data['maquinas'])) {
            if (is_array($data['maquinas'])) {
                $this->maquinas = $data['maquinas'];
            } else if (is_string($data['maquinas']) && !empty($data['maquinas'])) {
                $this->maquinas = explode(',', $data['maquinas']);
            } else {
                $this->maquinas = [];
            }
        } else {
            $this->maquinas = [];
        }
    }
}
