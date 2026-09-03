<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\PartidoDTO;
use App\Repositories\PartidoRepository;

class PartidoService
{
    private const ESTADOS = ['Programado', 'En Vivo', 'Finalizado', 'Pospuesto'];

    private PartidoRepository $repository;

    public function __construct()
    {
        $this->repository = new PartidoRepository();
    }

    public function list(array $filters = []): array
    {
        return array_map([$this, 'present'], $this->repository->findAll($filters));
    }

    public function get(int $id): array
    {
        $row = $this->repository->findById($id);
        if (!$row) {
            throw HttpException::notFound('Partido no encontrado');
        }
        return $this->present($row);
    }

    public function create(PartidoDTO $dto): array
    {
        $this->validate($dto);
        $id = $this->repository->create((array) $dto);
        return $this->get($id);
    }

    public function update(int $id, PartidoDTO $dto): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Partido no encontrado');
        }
        $this->validate($dto);
        $this->repository->update($id, (array) $dto);
        return $this->get($id);
    }

    public function delete(int $id): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Partido no encontrado');
        }
        $this->repository->delete($id);
        return ['modo' => 'eliminado'];
    }

    private function validate(PartidoDTO $dto): void
    {
        if ($dto->equipo_local_id <= 0 || $dto->equipo_visitante_id <= 0) {
            throw HttpException::validation('Debes elegir equipo local y visitante');
        }
        if ($dto->equipo_local_id === $dto->equipo_visitante_id) {
            throw HttpException::validation('El local y el visitante no pueden ser el mismo equipo');
        }
        if (!in_array($dto->estado, self::ESTADOS, true)) {
            throw HttpException::validation('Estado de partido inválido');
        }
        if ($dto->marcador_local < 0 || $dto->marcador_visitante < 0) {
            throw HttpException::validation('El marcador no puede ser negativo');
        }
    }

    private function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'jornada' => $row['jornada'] !== null ? (int) $row['jornada'] : null,
            'fase' => $row['fase'],
            'estado' => $row['estado'],
            'fecha' => $row['fecha'],
            'hora' => $row['hora'] ? substr($row['hora'], 0, 5) : null,
            'sede' => $row['sede'],
            'marcador_local' => (int) $row['marcador_local'],
            'marcador_visitante' => (int) $row['marcador_visitante'],
            'arbitro_principal' => $row['arbitro_principal'],
            'juez_mesa' => $row['juez_mesa'],
            'acta_cerrada' => (bool) $row['acta_cerrada'],
            'local' => [
                'id' => (int) $row['equipo_local_id'],
                'nombre' => $row['equipo_local'],
                'logo_ruta' => $row['logo_local'],
            ],
            'visitante' => [
                'id' => (int) $row['equipo_visitante_id'],
                'nombre' => $row['equipo_visitante'],
                'logo_ruta' => $row['logo_visitante'],
            ],
        ];
    }
}
