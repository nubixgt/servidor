<?php
namespace App\Services;

use App\Repositories\RutaRepository;
use App\Repositories\CursoRepository;
use App\DTOs\GuardarRutaDTO;
use App\Entities\RutaAprendizaje;
use App\Utils\UrlHelper;

class RutaService
{
    private const COLORES_VALIDOS = ['esmeralda', 'verde', 'azul', 'oro'];

    private $rutaRepository;
    private $cursoRepository;

    public function __construct()
    {
        $this->rutaRepository = new RutaRepository();
        $this->cursoRepository = new CursoRepository();
    }

    public function crear(GuardarRutaDTO $dto): array
    {
        $this->validar($dto);
        $entidad = new RutaAprendizaje(null, $dto->icono, $dto->titulo, $dto->descripcion, $dto->color, $dto->cursoIds);
        $id = $this->rutaRepository->create($entidad);
        return $this->obtener($id);
    }

    public function actualizar(int $id, GuardarRutaDTO $dto): array
    {
        if (!$this->rutaRepository->existsById($id)) {
            throw new \Exception('Ruta no encontrada.');
        }
        $this->validar($dto);
        $entidad = new RutaAprendizaje($id, $dto->icono, $dto->titulo, $dto->descripcion, $dto->color, $dto->cursoIds);
        $this->rutaRepository->update($id, $entidad);
        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        if (!$this->rutaRepository->existsById($id)) {
            throw new \Exception('Ruta no encontrada.');
        }
        $this->rutaRepository->delete($id);
    }

    public function listar(): array
    {
        return array_map(fn(RutaAprendizaje $r) => $this->toArray($r), $this->rutaRepository->findAll());
    }

    public function obtener(int $id): array
    {
        $ruta = $this->rutaRepository->findById($id);
        if (!$ruta) {
            throw new \Exception('Ruta no encontrada.');
        }
        return $this->toArray($ruta);
    }

    private function validar(GuardarRutaDTO $dto): void
    {
        if ($dto->icono === '') {
            throw new \Exception('El ícono de la ruta es obligatorio.');
        }
        if (mb_strlen($dto->titulo) < 3) {
            throw new \Exception('El título de la ruta es obligatorio.');
        }
        if ($dto->descripcion === '') {
            throw new \Exception('La descripción de la ruta es obligatoria.');
        }
        if (!in_array($dto->color, self::COLORES_VALIDOS, true)) {
            throw new \Exception('Selecciona un color válido para la ruta.');
        }
        if (count($dto->cursoIds) < 1) {
            throw new \Exception('Asigna al menos un curso a la ruta.');
        }
        foreach ($dto->cursoIds as $cursoId) {
            if ($this->cursoRepository->findById($cursoId) === null) {
                throw new \Exception('Uno de los cursos seleccionados ya no existe.');
            }
        }
    }

    private function toArray(RutaAprendizaje $ruta): array
    {
        $cursos = array_values(array_filter(array_map(
            fn(int $id) => $this->cursoResumen($id),
            $ruta->cursoIds
        )));

        return [
            'id' => $ruta->id,
            'icono' => $ruta->icono,
            'titulo' => $ruta->titulo,
            'descripcion' => $ruta->descripcion,
            'color' => $ruta->color,
            'cursos' => $cursos,
        ];
    }

    private function cursoResumen(int $cursoId): ?array
    {
        $curso = $this->cursoRepository->findById($cursoId);
        if ($curso === null) {
            return null;
        }
        return [
            'id' => $curso->id,
            'icono' => $curso->icono,
            'titulo' => $curso->titulo,
            'imagen_url' => UrlHelper::toAbsolute($curso->imagenPath),
            'total_lecciones' => count($curso->lecciones),
        ];
    }
}
