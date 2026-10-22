<?php
namespace App\Services;

use App\DTOs\DanoPecuarioDTO;
use App\Entities\DanoPecuario;
use App\Repositories\DanoPecuarioRepository;
use App\Utils\Periodo;

class DanoPecuarioService
{
    private DanoPecuarioRepository $repository;

    public function __construct()
    {
        $this->repository = new DanoPecuarioRepository();
    }

    /** @return DanoPecuario[] */
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

    public function crear(DanoPecuarioDTO $dto, array $user = []): DanoPecuario
    {
        $f = $dto->fields;
        if ($f['fechaInforme'] === '' || $f['especie'] === '' || $f['causa'] === '') {
            throw new \Exception('Fecha de informe, especie y causa son obligatorias.', 400);
        }
        if ($f['departamento'] === '' || $f['municipio'] === '' || $f['centroPoblado'] === '') {
            throw new \Exception('Departamento, municipio y centro poblado son obligatorios.', 400);
        }
        if ($f['especie'] === 'Otra' && $f['especieOtra'] === '') {
            throw new \Exception('Especifica la especie.', 400);
        }
        if ($f['causa'] === 'Otra' && $f['causaOtra'] === '') {
            throw new \Exception('Especifica la causa del daño.', 400);
        }
        Periodo::fecha($f['fechaInforme'], 'La fecha de informe');
        Periodo::rango($f['monitoreoInicio'], $f['monitoreoFin']);
        foreach (['famAfectadas' => 'Familias afectadas', 'animalesPerdidos' => 'Animales perdidos', 'perdidaEconomica' => 'La pérdida económica'] as $k => $etiqueta) {
            Periodo::noNegativo($f[$k], $etiqueta);
        }
        $f['famAfectadas'] = Periodo::entero($f['famAfectadas']);
        $f['animalesPerdidos'] = Periodo::entero($f['animalesPerdidos']);
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
