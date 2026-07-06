<?php
namespace App\Services\VISAR;

use App\Repositories\VISAR\InspeccionRepository;
use App\Repositories\VISAR\LicenciaRepository;
use App\DTOs\VISAR\InspeccionDTO;

class VisarService
{
    private $inspeccionRepo;
    private $licenciaRepo;

    public function __construct()
    {
        $this->inspeccionRepo = new InspeccionRepository();
        $this->licenciaRepo = new LicenciaRepository();
    }

    // --- INSPECCIONES ---
    public function getInspecciones($filters = [])
    {
        $data = $this->inspeccionRepo->getAll($filters);
        return InspeccionDTO::mapCollection($data);
    }

    public function getInspeccionStats()
    {
        return $this->inspeccionRepo->getStats();
    }

    public function createInspeccion($data)
    {
        return $this->inspeccionRepo->create($data);
    }

    public function updateInspeccion($id, $data)
    {
        return $this->inspeccionRepo->update($id, $data);
    }

    public function deleteInspeccion($id)
    {
        return $this->inspeccionRepo->delete($id);
    }

    // --- LICENCIAS ---
    public function getLicencias($filters = [])
    {
        return $this->licenciaRepo->getAll($filters);
    }

    public function createLicencia($data)
    {
        return $this->licenciaRepo->create($data);
    }

    public function updateLicencia($id, $data)
    {
        return $this->licenciaRepo->update($id, $data);
    }

    public function deleteLicencia($id)
    {
        return $this->licenciaRepo->delete($id);
    }
}
