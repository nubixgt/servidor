<?php
namespace App\Services;

use App\DTOs\DanoInfraestructuraDTO;
use App\Entities\DanoInfraestructura;
use App\Repositories\DanoInfraestructuraRepository;
use App\Utils\Periodo;

class DanoInfraestructuraService
{
    private DanoInfraestructuraRepository $repository;

    public function __construct()
    {
        $this->repository = new DanoInfraestructuraRepository();
    }

    /** @return DanoInfraestructura[] */
    public function listar(array $query, array $user = []): array
    {
        $criteria = [
            'q' => $query['q'] ?? null,
            'desde' => $query['desde'] ?? null,
            'hasta' => $query['hasta'] ?? null,
        ];
        // El técnico solo ve sus propios registros; admin y supervisor ven todos.
        if (($user['role'] ?? '') === 'tecnico') {
            $criteria['usuarioId'] = (int)($user['sub'] ?? 0);
        }
        return $this->repository->findByFilters($criteria);
    }

    public function crear(DanoInfraestructuraDTO $dto, array $user = []): DanoInfraestructura
    {
        $f = $dto->fields;
        if ($f['fechaInforme'] === '' || $f['tipoInfraestructura'] === '' || $f['causa'] === '') {
            throw new \Exception('Fecha de informe, tipo de infraestructura y causa son obligatorios.', 400);
        }
        if ($f['departamento'] === '' || $f['municipio'] === '' || $f['centroPoblado'] === '') {
            throw new \Exception('Departamento, municipio y centro poblado son obligatorios.', 400);
        }
        if ($f['tipoInfraestructura'] === 'Otra' && $f['tipoOtra'] === '') {
            throw new \Exception('Especifica el tipo de infraestructura.', 400);
        }
        if ($f['causa'] === 'Otra' && $f['causaOtra'] === '') {
            throw new \Exception('Especifica la causa del daño.', 400);
        }
        if (($f['gpsLat'] !== null) !== ($f['gpsLon'] !== null)) {
            throw new \Exception('Indica latitud y longitud.', 400);
        }
        if (($f['gpsLat'] !== null && abs($f['gpsLat']) > 90) || ($f['gpsLon'] !== null && abs($f['gpsLon']) > 180)) {
            throw new \Exception('Coordenadas GPS inválidas.', 400);
        }
        Periodo::fecha($f['fechaInforme'], 'La fecha de informe');
        Periodo::rango($f['monitoreoInicio'], $f['monitoreoFin']);
        foreach (['famAfectadas' => 'Familias afectadas', 'perdidaEconomica' => 'La pérdida económica'] as $k => $etiqueta) {
            Periodo::noNegativo($f[$k], $etiqueta);
        }
        $f['famAfectadas'] = Periodo::entero($f['famAfectadas']);
        [$f['semana'], $f['anioSemana']] = Periodo::semana($f['fechaInforme']);

        $id = $this->repository->create($f, isset($user['sub']) ? (int)$user['sub'] : null, $user['usuario'] ?? null);
        return $this->repository->findById($id);
    }

    public function eliminar(int $id): void
    {
        $dano = $this->repository->findById($id);
        if (!$dano) {
            throw new \Exception('Registro no encontrado.', 404);
        }
        $this->repository->delete($dano->id);
    }
}
