<?php
namespace App\Services\ActividadesDespacho;

use App\Repositories\ActividadesDespacho\ActividadRepository;
use App\DTOs\ActividadesDespacho\ActividadDTO;

class ActividadService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ActividadRepository();
    }

    public function getActividades()
    {
        $data = $this->repository->getAll();
        return array_map(function($item) {
            return ActividadDTO::fromArray($item);
        }, $data);
    }

    public function getActividad($id)
    {
        $data = $this->repository->findById($id);
        return $data ? ActividadDTO::fromArray($data) : null;
    }

    public function createActividad($data)
    {
        return $this->repository->create($data);
    }

    public function updateActividad($id, $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteActividad($id)
    {
        return $this->repository->delete($id);
    }

    public function getTecnicos()
    {
        return $this->repository->getTecnicos();
    }

    public function createTecnico($data)
    {
        return $this->repository->createTecnico($data);
    }

    public function updateTecnico($id, $data)
    {
        return $this->repository->updateTecnico($id, $data);
    }

    public function deleteTecnico($id)
    {
        return $this->repository->deleteTecnico($id);
    }

    public function getStats()
    {
        $summary = $this->repository->getSummaryStats();
        return [
            'total' => (int)($summary['total'] ?? 0),
            'criticas' => (int)($summary['criticas'] ?? 0),
            'en_progreso' => (int)($summary['en_progreso'] ?? 0),
            'completadas' => (int)($summary['completadas'] ?? 0),
            'categorias' => $this->repository->getStatsByCategory(),
            'tecnicos' => $this->repository->getStatsByTecnico()
        ];
    }

    public function getSeguimiento($actividad_id)
    {
        return $this->repository->getSeguimiento($actividad_id);
    }

    public function addSeguimiento($data)
    {
        return $this->repository->createSeguimiento($data);
    }

    public function getAdjuntos($actividad_id)
    {
        return $this->repository->getAdjuntos($actividad_id);
    }

    public function addAdjunto($data)
    {
        return $this->repository->createAdjunto($data);
    }
}
