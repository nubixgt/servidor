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
                    'nota' => $p->nota,
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
    public function guardarLeccion(int $usuarioId, int $leccionId, ?bool $completada, ?int $segundosVideo, ?int $nota, ?int $total): array
    {
        if (!$this->repository->leccionExiste($leccionId)) {
            throw new \Exception('Lección no encontrada.');
        }
        if ($segundosVideo !== null && $segundosVideo < 0) {
            throw new \Exception('segundos_video no puede ser negativo.');
        }
        if ($nota !== null && $total !== null) {
            if ($total < 0 || $nota < 0 || $nota > $total) {
                throw new \Exception('Nota inválida.');
            }
            // Approve lesson only if nota is >= 60%
            if ($total > 0 && ($nota / $total) >= 0.6) {
                $completada = true;
            } else {
                $completada = false;
            }
        }

        $cursoId = $this->repository->cursoIdDeLeccion($leccionId);
        $actual = $this->repository->obtenerLeccion($usuarioId, $leccionId);

        // Si ya estaba completada, no des-completarla por un quiz fallido
        $esCompletada = $completada ?? $actual?->completada ?? false;
        if ($actual?->completada && $completada === false) {
            $esCompletada = true;
        }

        $nuevo = new ProgresoLeccion(
            $usuarioId,
            $leccionId,
            $cursoId,
            $esCompletada,
            $segundosVideo ?? $actual?->segundosVideo ?? 0,
            $nota ?? $actual?->nota
        );

        $this->repository->upsertLeccion($nuevo);

        return [
            'leccion_id' => $nuevo->leccionId,
            'curso_id' => $nuevo->cursoId,
            'completada' => $nuevo->completada,
            'segundos_video' => $nuevo->segundosVideo,
            'nota' => $nuevo->nota,
        ];
    }


}
