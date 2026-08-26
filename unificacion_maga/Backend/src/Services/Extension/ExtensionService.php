<?php
namespace App\Services\Extension;

use App\Repositories\Extension\ExtensionRepository;
use App\DTOs\Extension\VisitaDTO;

class ExtensionService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ExtensionRepository();
    }

    public function getVisitas($filters = [])
    {
        $data = $this->repository->getAll($filters);
        return VisitaDTO::mapCollection($data);
    }

    public function getExtensionistas()
    {
        return $this->repository->getExtensionistas();
    }

    public function createVisita($data)
    {
        return $this->repository->create($data);
    }

    public function updateVisita($id, $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteVisita($id)
    {
        return $this->repository->delete($id);
    }
}
