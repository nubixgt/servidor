<?php
namespace App\Services;

use App\Repositories\DepartamentoRepository;
use App\Repositories\MunicipioRepository;

class LocationService
{
    private $departamentoRepository;
    private $municipioRepository;

    public function __construct()
    {
        $this->departamentoRepository = new DepartamentoRepository();
        $this->municipioRepository = new MunicipioRepository();
    }

    public function listDepartamentos(): array
    {
        $departamentos = $this->departamentoRepository->findAll();

        return array_map(
            fn($d) => ['id' => $d->id, 'nombre' => $d->nombre],
            $departamentos
        );
    }

    public function listMunicipios(int $departamentoId): array
    {
        if (!$this->departamentoRepository->existsById($departamentoId)) {
            throw new \Exception('Departamento no encontrado.');
        }

        $municipios = $this->municipioRepository->findByDepartamento($departamentoId);

        return array_map(
            fn($m) => ['id' => $m->id, 'departamento_id' => $m->departamentoId, 'nombre' => $m->nombre],
            $municipios
        );
    }
}
