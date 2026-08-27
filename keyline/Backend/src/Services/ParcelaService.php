<?php
namespace App\Services;

use App\Repositories\ParcelaRepository;
use App\DTOs\ParcelaDTO;
use App\Entities\Parcela;

class ParcelaService
{
    private ParcelaRepository $repository;

    private const DEPARTAMENTOS_VALIDOS = [
        'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula', 'El Progreso',
        'Escuintla', 'Guatemala', 'Huehuetenango', 'Izabal', 'Jalapa', 'Jutiapa',
        'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu', 'Sacatepéquez',
        'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez', 'Totonicapán', 'Zacapa',
    ];

    private const ESTADOS_VALIDACION = ['Pendiente de revisión', 'Validado', 'Requiere corrección'];

    public function __construct()
    {
        $this->repository = new ParcelaRepository();
    }

    /** @return Parcela[] */
    public function listar(array $query, array $currentUser): array
    {
        $criteria = [
            'q' => $query['q'] ?? null,
            'departamento' => $query['departamento'] ?? null,
            'estado' => $query['estado'] ?? null,
            'estadoValidacion' => $query['estadoValidacion'] ?? null,
            'encharca' => $query['encharca'] ?? null,
            'desde' => $query['desde'] ?? null,
            'hasta' => $query['hasta'] ?? null,
        ];

        $this->aplicarScope($criteria, $currentUser);

        return $this->repository->findByFilters($criteria);
    }

    public function obtener(int $id, array $currentUser): Parcela
    {
        $parcela = $this->repository->findById($id);
        if (!$parcela) {
            throw new \Exception('Parcela no encontrada.', 404);
        }
        $this->verificarAcceso($parcela, $currentUser);
        return $parcela;
    }

    public function crear(ParcelaDTO $dto, array $currentUser): Parcela
    {
        $f = $dto->fields;
        if (empty($f['nombreParcela']) || empty($f['departamento']) || empty($f['municipio']) || empty($f['areaHa']) || empty($f['estado'])) {
            throw new \Exception('Nombre de parcela, departamento, municipio, área y estado son obligatorios.', 400);
        }
        if (!in_array($f['departamento'], self::DEPARTAMENTOS_VALIDOS, true)) {
            throw new \Exception('Departamento inválido.', 400);
        }

        $parcela = new Parcela();
        foreach (ParcelaDTO::EDITABLE_FIELDS as $key) {
            if (array_key_exists($key, $f)) {
                $parcela->{$key} = $f[$key];
            }
        }
        if (empty($parcela->fechaRegistro)) {
            $parcela->fechaRegistro = date('Y-m-d');
        }

        $year = (int)date('Y');
        $siguiente = $this->repository->countThisYear($year) + 1;
        $parcela->codigo = sprintf('KL-%d-%05d', $year, $siguiente);
        $parcela->tecnicoId = (int)$currentUser['id'];
        $parcela->estadoValidacion = 'Pendiente de revisión';

        return $this->repository->create($parcela);
    }

    public function actualizar(int $id, ParcelaDTO $dto, array $currentUser): Parcela
    {
        $parcela = $this->repository->findById($id);
        if (!$parcela) {
            throw new \Exception('Parcela no encontrada.', 404);
        }
        if ($currentUser['role'] === 'tecnico' && $parcela->tecnicoId !== (int)$currentUser['id']) {
            throw new \Exception('No autorizado.', 403);
        }

        $fields = $dto->fields;
        $this->repository->update($id, $fields);

        // Si un técnico edita una parcela ya validada, vuelve a pedir revisión.
        if ($currentUser['role'] === 'tecnico' && $parcela->estadoValidacion === 'Validado') {
            $this->repository->actualizarValidacion($id, 'Pendiente de revisión', $parcela->comentarioSupervisor, $parcela->revisadoPor);
        }

        return $this->repository->findById($id);
    }

    public function revisar(int $id, string $estadoValidacion, string $comentario, array $currentUser): Parcela
    {
        $parcela = $this->repository->findById($id);
        if (!$parcela) {
            throw new \Exception('Parcela no encontrada.', 404);
        }
        if (!in_array($estadoValidacion, self::ESTADOS_VALIDACION, true)) {
            throw new \Exception('Estado de validación inválido.', 400);
        }
        $this->repository->actualizarValidacion($id, $estadoValidacion, $comentario, $currentUser['nombre'] ?? '');
        return $this->repository->findById($id);
    }

    public function eliminar(int $id, array $currentUser): void
    {
        $parcela = $this->repository->findById($id);
        if (!$parcela) {
            throw new \Exception('Parcela no encontrada.', 404);
        }
        $this->repository->delete($id);
    }

    private function aplicarScope(array &$criteria, array $currentUser): void
    {
        if ($currentUser['role'] === 'tecnico') {
            $criteria['tecnicoId'] = (int)$currentUser['id'];
        } elseif ($currentUser['role'] === 'supervisor' && !empty($currentUser['regionAsignada'])) {
            $criteria['departamentoScope'] = $currentUser['regionAsignada'];
        }
    }

    private function verificarAcceso(Parcela $parcela, array $currentUser): void
    {
        if ($currentUser['role'] === 'tecnico' && $parcela->tecnicoId !== (int)$currentUser['id']) {
            throw new \Exception('No autorizado.', 403);
        }
        if ($currentUser['role'] === 'supervisor' && !empty($currentUser['regionAsignada']) && $parcela->departamento !== $currentUser['regionAsignada']) {
            throw new \Exception('No autorizado.', 403);
        }
    }
}
