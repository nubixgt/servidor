<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\EquipoDTO;
use App\Repositories\EquipoRepository;
use App\Utils\FileUpload;

class EquipoService
{
    private const CONFERENCIAS = ['Norte', 'Sur'];
    private const RAMAS = ['Masculina Mayor', 'Femenina Libre', 'Juvenil Sub-18'];
    private const LOGO_MAX_BYTES = 2 * 1024 * 1024;

    private EquipoRepository $repository;

    public function __construct()
    {
        $this->repository = new EquipoRepository();
    }

    public function list(array $filters = []): array
    {
        return array_map([$this, 'present'], $this->repository->findAll($filters));
    }

    public function get(int $id): array
    {
        $row = $this->repository->findById($id);
        if (!$row) {
            throw HttpException::notFound('Equipo no encontrado');
        }
        return $this->present($row);
    }

    public function create(EquipoDTO $dto): array
    {
        $this->validate($dto);
        $id = $this->repository->create((array) $dto);
        return $this->get($id);
    }

    public function update(int $id, EquipoDTO $dto): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Equipo no encontrado');
        }
        $this->validate($dto);
        $this->repository->update($id, (array) $dto);
        return $this->get($id);
    }

    public function delete(int $id): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Equipo no encontrado');
        }

        if ($this->repository->countPartidos($id) > 0) {
            // Tiene historial: baja lógica para preservar los partidos
            $this->repository->softDelete($id);
            return ['modo' => 'baja_logica'];
        }

        $this->repository->hardDelete($id);
        return ['modo' => 'eliminado'];
    }

    public function uploadLogo(int $id, array $file): array
    {
        if (!$this->repository->findById($id)) {
            throw HttpException::notFound('Equipo no encontrado');
        }
        $ruta = FileUpload::save($file, 'equipos', $id, 'logo', 'image', self::LOGO_MAX_BYTES);
        $this->repository->setLogo($id, $ruta);
        return $this->get($id);
    }

    private function validate(EquipoDTO $dto): void
    {
        if ($dto->nombre === '') {
            throw HttpException::validation('El nombre del equipo es obligatorio');
        }
        if ($dto->sede === '') {
            throw HttpException::validation('La sede es obligatoria');
        }
        if (!in_array($dto->conferencia, self::CONFERENCIAS, true)) {
            throw HttpException::validation('Conferencia inválida');
        }
        if (!in_array($dto->rama, self::RAMAS, true)) {
            throw HttpException::validation('Rama deportiva inválida');
        }
        if ($dto->director_tecnico === '') {
            throw HttpException::validation('El director técnico es obligatorio');
        }
        if ($dto->telefono_delegado === '') {
            throw HttpException::validation('El teléfono del delegado es obligatorio');
        }
        if ($dto->color_hex !== null && !preg_match('/^#[0-9A-Fa-f]{6}$/', $dto->color_hex)) {
            throw HttpException::validation('El color debe tener formato #RRGGBB');
        }
    }

    /** Formatea una fila para la API (agrega métricas derivadas). */
    private function present(array $row): array
    {
        $pj = (int) ($row['pj'] ?? 0);
        $pf = (int) ($row['pf'] ?? 0);
        $pc = (int) ($row['pc'] ?? 0);
        $pg = (int) ($row['pg'] ?? 0);

        return [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'sede' => $row['sede'],
            'conferencia' => $row['conferencia'],
            'rama' => $row['rama'],
            'logo_ruta' => $row['logo_ruta'],
            'director_tecnico' => $row['director_tecnico'],
            'telefono_delegado' => $row['telefono_delegado'],
            'color_hex' => $row['color_hex'],
            'activo' => (bool) $row['activo'],
            'jugadores_count' => (int) ($row['jugadores_count'] ?? 0),
            'clasificacion' => [
                'pj' => $pj,
                'pg' => $pg,
                'pp' => (int) ($row['pp'] ?? 0),
                'pf' => $pf,
                'pc' => $pc,
                'dif' => $pf - $pc,
                'racha' => $row['racha'],
                'puntos_liga' => (int) ($row['puntos_liga'] ?? 0),
                'sancion' => $row['sancion'],
                'record' => $pg . ' - ' . ((int) ($row['pp'] ?? 0)),
                'pct' => $pj > 0 ? round($pg / $pj, 3) : 0,
                'ppg' => $pj > 0 ? round($pf / $pj, 1) : 0,
                'oppg' => $pj > 0 ? round($pc / $pj, 1) : 0,
            ],
        ];
    }
}
