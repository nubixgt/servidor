<?php
namespace App\Services;

use App\Repositories\PilotoRepository;
use App\Entities\Piloto;

class PilotoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PilotoRepository();
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function create($data)
    {
        $piloto = new Piloto($data);
        $id = $this->repository->create($piloto);

        if (!$id) {
            return ['success' => false, 'message' => 'Error al guardar el piloto.'];
        }
        return ['success' => true, 'id' => $id];
    }

    public function update($id, $data)
    {
        $piloto = $this->repository->findById($id);
        if (!$piloto) {
            return ['success' => false, 'message' => 'Piloto no encontrado.'];
        }

        $piloto->nombre = $data['nombre'] ?? $piloto->nombre;
        $piloto->telefono = $data['telefono'] ?? $piloto->telefono;
        $piloto->status = $data['status'] ?? $piloto->status;
        $piloto->maquinas = $data['maquinas'] ?? $piloto->maquinas;

        $this->repository->update($piloto);
        return ['success' => true];
    }

    public function delete($id)
    {
        $success = $this->repository->delete($id);
        return ['success' => $success];
    }
}
