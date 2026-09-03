<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\JugadorDTO;
use App\Repositories\JugadorRepository;
use App\Utils\FileUpload;

class JugadorService
{
    private const POSICIONES = ['Base', 'Escolta', 'Alero', 'Ala-Pívot', 'Pívot'];
    private const ESTADOS = ['Habilitado', 'Suspendido'];
    private const FOTO_MAX_BYTES = 5 * 1024 * 1024;

    private JugadorRepository $repository;

    public function __construct()
    {
        $this->repository = new JugadorRepository();
    }

    public function list(array $filters = []): array
    {
        $result = $this->repository->findAll($filters);
        return [
            'items' => array_map([$this, 'present'], $result['items']),
            'total' => $result['total'],
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'perPage' => (int) ($filters['perPage'] ?? 0),
        ];
    }

    public function get(int $id): array
    {
        $row = $this->repository->findById($id);
        if (!$row) {
            throw HttpException::notFound('Jugador no encontrado');
        }
        return $this->present($row);
    }

    public function create(JugadorDTO $dto): array
    {
        $this->validate($dto);
        if ($dto->dpi !== null && $this->repository->dpiExists($dto->dpi)) {
            throw HttpException::conflict('Ya existe un jugador con ese CUI/DPI');
        }
        $id = $this->repository->create($this->toRow($dto));
        return $this->get($id);
    }

    public function update(int $id, JugadorDTO $dto): array
    {
        $current = $this->repository->findById($id);
        if (!$current) {
            throw HttpException::notFound('Jugador no encontrado');
        }
        $this->validate($dto);
        if ($dto->dpi !== null && $this->repository->dpiExists($dto->dpi, $id)) {
            throw HttpException::conflict('Ya existe un jugador con ese CUI/DPI');
        }

        $row = $this->toRow($dto);
        // No pisar estadísticas si el formulario no las envió
        foreach (['ppg', 'rpg', 'apg', 'tres_pct'] as $k) {
            if ($row[$k] === null) {
                $row[$k] = $current[$k];
            }
        }

        $this->repository->update($id, $row);
        return $this->get($id);
    }

    public function delete(int $id): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Jugador no encontrado');
        }
        $this->repository->softDelete($id);
        return ['modo' => 'inactivado'];
    }

    public function uploadFoto(int $id, array $file): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Jugador no encontrado');
        }
        $ruta = FileUpload::save($file, 'jugadores', $id, 'foto', 'image', self::FOTO_MAX_BYTES);
        $this->repository->setFoto($id, $ruta);
        return $this->get($id);
    }

    private function validate(JugadorDTO $dto): void
    {
        if ($dto->nombre_completo === '') {
            throw HttpException::validation('El nombre completo es obligatorio');
        }
        if ($dto->posicion !== null && !in_array($dto->posicion, self::POSICIONES, true)) {
            throw HttpException::validation('Posición inválida');
        }
        if (!in_array($dto->estado, self::ESTADOS, true)) {
            throw HttpException::validation('Estado inválido');
        }
        if ($dto->dorsal !== null && ($dto->dorsal < 0 || $dto->dorsal > 999)) {
            throw HttpException::validation('Dorsal fuera de rango');
        }
    }

    private function toRow(JugadorDTO $dto): array
    {
        return [
            'equipo_id' => $dto->equipo_id,
            'nombre_completo' => $dto->nombre_completo,
            'dorsal' => $dto->dorsal,
            'dpi' => $dto->dpi,
            'fecha_nacimiento' => $dto->fecha_nacimiento,
            'nacionalidad' => $dto->nacionalidad,
            'posicion' => $dto->posicion,
            'estatura_cm' => $dto->estatura_cm,
            'peso_kg' => $dto->peso_kg,
            'estado' => $dto->estado,
            'ppg' => $dto->ppg,
            'rpg' => $dto->rpg,
            'apg' => $dto->apg,
            'tres_pct' => $dto->tres_pct,
        ];
    }

    private function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'equipo_id' => $row['equipo_id'] !== null ? (int) $row['equipo_id'] : null,
            'equipo_nombre' => $row['equipo_nombre'] ?? null,
            'nombre_completo' => $row['nombre_completo'],
            'dorsal' => $row['dorsal'] !== null ? (int) $row['dorsal'] : null,
            'dpi' => $row['dpi'],
            'fecha_nacimiento' => $row['fecha_nacimiento'],
            'nacionalidad' => $row['nacionalidad'],
            'posicion' => $row['posicion'],
            'estatura_cm' => $row['estatura_cm'] !== null ? (int) $row['estatura_cm'] : null,
            'peso_kg' => $row['peso_kg'] !== null ? (int) $row['peso_kg'] : null,
            'estado' => $row['estado'],
            'foto_ruta' => $row['foto_ruta'],
            'activo' => (bool) $row['activo'],
            'stats' => [
                'pj' => (int) $row['pj'],
                'ppg' => (float) $row['ppg'],
                'rpg' => (float) $row['rpg'],
                'apg' => (float) $row['apg'],
                'tres_pct' => (float) $row['tres_pct'],
                'tl_pct' => (float) $row['tl_pct'],
                'eff' => (float) $row['eff'],
            ],
        ];
    }
}
