<?php
namespace App\Services\VIDER;

use App\Repositories\VIDER\ViderRepository;
use App\Repositories\VIDER\TobanikRepository;

class ViderService
{
    private $viderRepo;
    private $tobanikRepo;

    public function __construct()
    {
        $this->viderRepo = new ViderRepository();
        $this->tobanikRepo = new TobanikRepository();
    }

    public function getDashboardData($filters = [])
    {
        return $this->viderRepo->getDashboardStats($filters);
    }

    public function getMapData($filters = [])
    {
        return $this->viderRepo->getMapData($filters);
    }

    public function getCatalogos($dependenciaId = null)
    {
        return $this->viderRepo->getCatalogos($dependenciaId);
    }

    public function getTobanikData($filters = [])
    {
        return $this->tobanikRepo->getSummary($filters);
    }

    public function getRecords($filters = [])
    {
        return $this->viderRepo->getRecords($filters);
    }

    public function createEjecucion($data)
    {
        return $this->viderRepo->create($data);
    }

    public function updateEjecucion($id, $data)
    {
        return $this->viderRepo->update($id, $data);
    }

    public function deleteEjecucion($id)
    {
        return $this->viderRepo->delete($id);
    }

    public function createTobanik($data)
    {
        return $this->tobanikRepo->create($data);
    }

    public function updateTobanik($id, $data)
    {
        return $this->tobanikRepo->update($id, $data);
    }

    public function deleteTobanik($id)
    {
        return $this->tobanikRepo->delete($id);
    }
}
