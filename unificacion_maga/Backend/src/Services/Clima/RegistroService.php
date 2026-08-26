<?php
namespace App\Services\Clima;

use App\Repositories\Clima\RegistroRepository;
use App\DTOs\Clima\RegistroDTO;

class RegistroService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new RegistroRepository();
    }

    public function getRegistros($userId = null, $role = 'Administrador')
    {
        $data = $this->repository->getAll($userId, $role);
        return array_map(function($item) {
            return RegistroDTO::fromArray($item);
        }, $data);
    }

    public function getRegistro($id)
    {
        $data = $this->repository->findById($id);
        return $data ? RegistroDTO::fromArray($data) : null;
    }

    public function createRegistro($data)
    {
        $id = $this->repository->create($data);
        
        // Manejar subida de fotografías si las hay
        if (isset($data['fotografias']) && is_array($data['fotografias'])) {
            foreach ($data['fotografias'] as $index => $foto) {
                $this->repository->addFotografia([
                    'id_registro' => $id,
                    'nombre_archivo' => $foto['nombre_archivo'],
                    'ruta_archivo' => $foto['ruta_archivo'],
                    'orden' => $index
                ]);
            }
        }
        
        return $id;
    }

    public function deleteRegistro($id)
    {
        return $this->repository->delete($id);
    }
}
