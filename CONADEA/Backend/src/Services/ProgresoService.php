<?php
namespace App\Services;

use App\Repositories\ProgresoRepository;
use App\Entities\ProgresoLeccion;
use App\Entities\ProgresoCurso;

class ProgresoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ProgresoRepository();
    }

    public function obtenerTodo(int $usuarioId): array
    {
        $todo = $this->repository->obtenerTodoDeUsuario($usuarioId);

        return [
            'lecciones' => array_map(
                fn(ProgresoLeccion $p) => [
                    'leccion_id' => $p->leccionId,
                    'curso_id' => $p->cursoId,
                    'completada' => $p->completada,
                    'segundos_video' => $p->segundosVideo,
                ],
                $todo['lecciones']
            ),
            'cursos' => array_map(
                fn(ProgresoCurso $p) => [
                    'curso_id' => $p->cursoId,
                    'nota' => $p->nota,
                    'aprobado' => $p->aprobado,
                    'fecha_aprobado' => $p->fechaAprobado,
                ],
                $todo['cursos']
            ),
        ];
    }

    /**
     * Actualización parcial: solo toca los campos que vengan distintos de
     * null, igual que hace la app hoy con completarLeccion (solo completada)
     * y el guardado periódico de posición del video (solo segundos_video).
     */
    public function guardarLeccion(int $usuarioId, int $leccionId, ?bool $completada, ?int $segundosVideo): array
    {
        if (!$this->repository->leccionExiste($leccionId)) {
            throw new \Exception('Lección no encontrada.');
        }
        if ($segundosVideo !== null && $segundosVideo < 0) {
            throw new \Exception('segundos_video no puede ser negativo.');
        }

        $cursoId = $this->repository->cursoIdDeLeccion($leccionId);
        $actual = $this->repository->obtenerLeccion($usuarioId, $leccionId);

        $nuevo = new ProgresoLeccion(
            $usuarioId,
            $leccionId,
            $cursoId,
            $completada ?? $actual?->completada ?? false,
            $segundosVideo ?? $actual?->segundosVideo ?? 0
        );

        $this->repository->upsertLeccion($nuevo);

        return [
            'leccion_id' => $nuevo->leccionId,
            'curso_id' => $nuevo->cursoId,
            'completada' => $nuevo->completada,
            'segundos_video' => $nuevo->segundosVideo,
        ];
    }

    /**
     * Aprobado es "pegajoso": igual que ProgresoController.aprobarCurso en la
     * app (data/state/progreso_controller.dart), una vez aprobado no se
     * puede desaprobar reintentando el quiz, y la fecha de aprobación solo
     * se fija la primera vez.
     */
    public function guardarEvaluacion(int $usuarioId, int $cursoId, int $nota, int $total): array
    {
        if (!$this->repository->cursoExiste($cursoId)) {
            throw new \Exception('Curso no encontrado.');
        }
        if ($total < 0 || $nota < 0 || $nota > $total) {
            throw new \Exception('Nota inválida.');
        }

        $actual = $this->repository->obtenerCurso($usuarioId, $cursoId);
        $yaAprobado = $actual?->aprobado ?? false;
        $aprobadoAhora = $total > 0 && ($nota / $total) >= 0.6;

        $aprobado = $yaAprobado || $aprobadoAhora;
        $fechaAprobado = $actual?->fechaAprobado ?? null;
        if ($aprobado && !$yaAprobado) {
            $fechaAprobado = date('Y-m-d H:i:s');
        }

        $nuevo = new ProgresoCurso($usuarioId, $cursoId, $nota, $aprobado, $fechaAprobado);
        $this->repository->upsertCurso($nuevo);

        return [
            'curso_id' => $nuevo->cursoId,
            'nota' => $nuevo->nota,
            'aprobado' => $nuevo->aprobado,
            'fecha_aprobado' => $nuevo->fechaAprobado,
        ];
    }
}
